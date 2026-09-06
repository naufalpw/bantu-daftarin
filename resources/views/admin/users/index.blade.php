@extends('layouts.admin')
@section('title', 'Pengguna')
@section('admin_context', 'Pengguna')
@section('content')
    <x-admin.page-header kicker="PENGGUNA" title="Direktori pelanggan" description="Lihat data akun dan pengajuan milik pelanggan tanpa mengubah identitas atau akses mereka." />
    <section class="bd-admin-list-surface mt-8" aria-labelledby="user-list-title">
        <div class="bd-admin-list-toolbar"><div><h2 id="user-list-title">Pelanggan</h2><p>Direktori ini bersifat baca-saja.</p></div><form class="bd-admin-search" method="get" action="{{ route('admin.users.index') }}"><label class="sr-only" for="user-search">Cari pengguna</label><input id="user-search" name="q" type="search" value="{{ $search }}" placeholder="Cari nama atau email"><input type="hidden" name="filter" value="{{ $filter }}"><button class="bd-admin-button bd-admin-button--secondary" type="submit">Cari</button></form></div>
        <nav class="bd-admin-filter-bar" aria-label="Filter pengguna">@foreach(['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Tidak aktif'] as $key => $label)<a href="{{ route('admin.users.index', array_filter(['filter' => $key, 'q' => $search ?: null])) }}" @class(['is-active' => $filter === $key]) @if($filter === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
        @if($users->isNotEmpty())
            <div class="bd-admin-table-wrap"><table class="bd-admin-table"><thead><tr><th>Nama</th><th>Email</th><th>Status akun</th><th>Pengajuan</th><th>Bergabung</th><th><span class="sr-only">Detail</span></th></tr></thead><tbody>@foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td><td><x-admin.status-badge :label="$user->is_active ? 'Aktif' : 'Tidak aktif'" :tone="$user->is_active ? 'success' : 'neutral'" /></td><td>{{ $user->applications_count }}</td><td>{{ $user->created_at->translatedFormat('d M Y') }}</td><td><a class="bd-admin-table-link" href="{{ route('admin.users.show', $user->public_id) }}">Detail</a></td></tr>@endforeach</tbody></table></div><div class="bd-admin-pagination">{{ $users->links() }}</div>
        @else
            <div class="bd-admin-empty-state"><h3>{{ $search !== '' ? 'Pengguna tidak ditemukan' : 'Belum ada pelanggan' }}</h3><p>{{ $search !== '' ? 'Coba gunakan nama atau alamat email lain.' : 'Pelanggan terdaftar akan muncul di sini.' }}</p></div>
        @endif
    </section>
@endsection
