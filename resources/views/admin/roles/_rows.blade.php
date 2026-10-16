@forelse ($roles as $role)
    @php
        $isSystem = in_array($role->name, \App\Support\Roles::all(), true);
        $isSuper = $role->name === \App\Support\Roles::SUPERADMIN;
        $label = $isSystem ? \App\Support\Roles::label($role->name) : ucfirst($role->name);

        $rowActions = [];
        if (auth()->user()->can('roles.update')) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('admin.roles.edit', $role)];
        }
        if (auth()->user()->can('roles.delete') && ! $isSystem) {
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus peran "'.$label.'"?',
                'confirm_danger' => true,
                'form' => 'del-role-'.$role->id,
            ];
        }
    @endphp
    <tr>
        <td>
            <div class="d-flex align-items-center gap-2">
                <span class="pill {{ $isSystem ? \App\Support\Roles::tone($role->name) : 'bg-tealc-m tx-tealt' }}">{{ $label }}</span>
                <code class="fs-11 tx-mute">{{ $role->name }}</code>
            </div>
        </td>
        <td>
            @if ($isSuper)
                <span class="pill bg-errc-l tx-err">Super</span>
            @elseif ($isSystem)
                <span class="pill bg-sky-l tx-navy">Sistem</span>
            @else
                <span class="pill bg-mint-l tx-mintt">Kustom</span>
            @endif
        </td>
        <td class="tx-mute fs-12">{{ $role->permissions_count }} izin</td>
        <td class="tx-mute fs-12">{{ $role->users_count }} pengguna</td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @else
                <span class="fs-12 tx-mute">—</span>
            @endif
            @if (auth()->user()->can('roles.delete') && ! $isSystem)
                <form id="del-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5">
            <x-ui.empty-state icon="admin_panel_settings" title="Tidak ada peran"
                description="Belum ada peran yang cocok dengan pencarian." />
        </td>
    </tr>
@endforelse
