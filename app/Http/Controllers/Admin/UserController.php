<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Rbac;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('name', $request->query('role'))))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'users' => $users,
            'listRoute' => route('admin.users.index'),
            'roleLabels' => Roles::labels(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.users._rows', $data)->render(),
                'footer' => view('admin.users._footer', $data)->render(),
            ]);
        }

        return view('admin.users.index', $data);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
            'currentRole' => null,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in($this->assignableRoles())],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Rbac::canAssign($request->user(), $data['role'])) {
            abort(403, 'Anda tidak berwenang menetapkan peran ini.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_admin' => in_array($data['role'], Roles::adminArea(), true),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.users.index')
            ->with('status', 'Pengguna "'.$user->name.'" berhasil dibuat.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless(Rbac::canManageUser($request->user(), $user), 403);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'currentRole' => $user->primaryRole(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(Rbac::canManageUser($request->user(), $user), 403);

        $isSelf = $request->user()->is($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => [$isSelf ? 'nullable' : 'required', Rule::in($this->assignableRoles())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        if (! $isSelf && ! empty($data['role'])) {
            if (! Rbac::canAssign($request->user(), $data['role'])) {
                abort(403, 'Anda tidak berwenang menetapkan peran ini.');
            }

            $user->syncRoles([$data['role']]);
            $user->update(['is_admin' => in_array($data['role'], Roles::adminArea(), true)]);
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'Pengguna "'.$user->name.'" berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless(Rbac::canManageUser($request->user(), $user), 403);

        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return array<int, string>
     */
    private function assignableRoles(): array
    {
        return Rbac::assignableRoles(auth()->user());
    }
}
