@extends('layouts.admin')
@section('title', 'Dukungan')
@section('admin_context', 'Dukungan')
@section('content')
    <div class="bd-admin-support-page">
        <x-admin.page-header kicker="DUKUNGAN" title="Kotak masuk dukungan" description="Bantuan Umum dan Dukungan Pengajuan dalam satu daftar." />

        <livewire:admin.support-inbox />
    </div>
@endsection
