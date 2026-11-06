@forelse ($users as $user)
    @php
        $role = $user->primaryRole();
        $canEdit = auth()->user()->can('users.update') && \App\Support\Rbac::canManageUser(auth()->user(), $user);
        $canDelete = auth()->user()->can('users.delete')
            && ! auth()->user()->is($user)
            && \App\Support\Rbac::canManageUser(auth()->user(), $user);

        $rowActions = [];
        if ($canEdit) {
            $rowActions[] = ['label' => 'Ubah', 'icon' => 'edit', 'href' => route('admin.users.edit', $user)];
        }
        $rowActions[] = ['label' => 'Detail', 'icon' => 'visibility', 'href' => route('admin.users.show', $user)];
        if ($canDelete) {
            $rowActions[] = [
                'label' => 'Hapus',
                'icon' => 'delete',
                'tone' => 'tx-err',
                'confirm' => 'Hapus pengguna "'.$user->name.'"?',
                'confirm_danger' => true,
                'form' => 'del-user-'.$user->id,
            ];
        }
    @endphp
    <tr data-role="{{ $role ?? '' }}">
        <td>
            <div class="d-flex align-items-center gap-2">
                <span class="ibox fw-bold rounded-circle" style="width:2rem;height:2rem;background:rgba(159,240,247,.4);color:var(--tealt)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $user->name }}</span>
                    @if (auth()->user()->is($user))
                        <span class="fs-11 tx-mute">Anda</span>
                    @endif
                </div>
            </div>
        </td>
        <td><x-ui.tooltip :text="$user->email" truncate="11rem" class="tx-mute fs-12">{{ $user->email }}</x-ui.tooltip></td>
        <td class="tx-mute fs-12">{{ $user->phone ?: '—' }}</td>
        <td>
            <div class="d-flex flex-wrap gap-1">
                <span class="pill {{ \App\Support\Roles::tone($role ?? \App\Support\Roles::USER) }}">{{ $user->roleLabel() }}</span>
                @if ($user->reseller_since !== null || $user->is_reseller)
                    <span class="pill bg-sky-l tx-navy">Reseller</span>
                @endif
            </div>
        </td>
        <td class="num text-end">Rp {{ number_format((int) $user->balance, 0, ',', '.') }}</td>
        <td class="num text-end">{{ number_format((int) $user->points, 0, ',', '.') }}</td>
        <td class="text-end">
            @if (count($rowActions))
                <x-ui.actions :actions="$rowActions" />
            @else
                <span class="fs-12 tx-mute">—</span>
            @endif
            @if ($canDelete)
                <form id="del-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <x-ui.empty-state icon="group" title="Tidak ada pengguna"
                description="Belum ada pengguna yang cocok dengan pencarian atau filter." />
        </td>
    </tr>
@endforelse
