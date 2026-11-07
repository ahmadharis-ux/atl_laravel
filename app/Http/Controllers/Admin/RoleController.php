<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Support\Rbac;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View|JsonResponse
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where('name', 'like', $term);
            })
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'roles' => $roles,
            'listRoute' => route('admin.roles.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.roles._rows', $data)->render(),
                'footer' => view('admin.roles._footer', $data)->render(),
            ]);
        }

        return view('admin.roles.index', $data);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new Role,
            'groups' => Rbac::groups(),
            'assigned' => old('permissions', []),
            'mode' => 'create',
            'isSystem' => false,
            'locked' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Rbac::ensurePermissions();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Rbac::all())],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Peran "'.$role->name.'" berhasil dibuat.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'groups' => Rbac::groups(),
            'assigned' => old('permissions', $role->permissions->pluck('name')->all()),
            'mode' => 'edit',
            'isSystem' => in_array($role->name, Roles::all(), true),
            'locked' => $role->name === Roles::SUPERADMIN,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Rbac::ensurePermissions();

        $isSystem = in_array($role->name, Roles::all(), true);
        $locked = $role->name === Roles::SUPERADMIN;

        $data = $request->validate([
            'name' => $isSystem
                ? ['nullable']
                : ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Rbac::all())],
        ]);

        if (! $isSystem) {
            $role->update(['name' => $data['name']]);
        }

        if (! $locked) {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        return redirect()->route('admin.roles.index')
            ->with('status', 'Peran "'.$role->name.'" berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, Roles::all(), true)) {
            return back()->withErrors(['role' => 'Peran bawaan sistem tidak dapat dihapus.']);
        }

        $count = $role->users()->count();
        if ($count > 0) {
            return back()->withErrors(['role' => 'Peran masih dipakai oleh '.$count.' pengguna.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('status', 'Peran berhasil dihapus.');
    }
}
