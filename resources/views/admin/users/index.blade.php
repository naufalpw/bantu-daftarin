@extends('layouts.admin')
@section('title', 'Pengguna')
@section('admin_context', 'Pengguna')
@section('content')
    <x-admin.page-header kicker="PENGGUNA" title="Direktori pelanggan" description="Lihat data akun dan pengajuan milik pelanggan tanpa mengubah identitas atau akses mereka." />
    <livewire:admin.user-directory />
@endsection
