@extends('layouts.admin')

@section('title', 'Pengajuan')
@section('admin_context', 'Pengajuan')

@section('content')
    <x-admin.page-header kicker="PENGAJUAN" title="Daftar pengajuan" description="Lihat pengajuan yang perlu ditindaklanjuti dan proses yang sedang berjalan." />

    <livewire:admin.application-queue />
@endsection
