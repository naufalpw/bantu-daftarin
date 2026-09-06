@extends('layouts.admin')
@section('title', 'Detail Pengguna')
@section('admin_context', 'Pengguna')
@section('content')
    <a class="bd-admin-back-link" href="{{ route('admin.users.index') }}">Kembali ke pengguna</a>
    <x-admin.page-header kicker="PENGGUNA" :title="$user->name" description="Detail pelanggan bersifat baca-saja. Data pengajuan dapat dibuka melalui konteks masing-masing." />
    <div class="bd-admin-detail-grid mt-8">
        <section class="bd-admin-surface"><div class="bd-admin-surface__header"><div><h2>Ringkasan akun</h2><p>Informasi dasar yang relevan untuk operasional.</p></div></div><dl class="bd-admin-definition-grid"><div><dt>Email</dt><dd>{{ $user->email }}</dd></div><div><dt>Status akun</dt><dd><x-admin.status-badge :label="$user->is_active ? 'Aktif' : 'Tidak aktif'" :tone="$user->is_active ? 'success' : 'neutral'" /></dd></div><div><dt>Bergabung</dt><dd>{{ $user->created_at->translatedFormat('d M Y, H:i') }}</dd></div></dl></section>
        <section class="bd-admin-surface"><div class="bd-admin-surface__header"><div><h2>Pengajuan milik pelanggan</h2><p>Daftar ini tidak menampilkan dokumen atau identitas sensitif.</p></div></div>@if($applications->isNotEmpty())<div class="bd-admin-compact-list">@foreach($applications as $application)<a class="bd-admin-compact-row" href="{{ route('admin.applications.show', $application->public_id) }}"><span><strong>{{ $application->service->name }}</strong><small>…{{ strtoupper(substr($application->public_id, -6)) }} · Diperbarui {{ $application->updated_at->translatedFormat('d M Y') }}</small></span><x-admin.status-badge :status="$application->status" /></a>@endforeach</div><div class="bd-admin-pagination">{{ $applications->links() }}</div>@else<div class="bd-admin-empty-state bd-admin-empty-state--compact"><h3>Belum ada pengajuan</h3><p>Pengajuan pelanggan ini akan muncul di sini.</p></div>@endif</section>
    </div>
@endsection
