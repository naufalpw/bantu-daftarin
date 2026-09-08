@extends('layouts.admin')
@section('title', 'Aktivitas')
@section('admin_context', 'Aktivitas')
@section('content')
    <x-admin.page-header kicker="AKTIVITAS" title="Aktivitas operasional" description="Pembaruan terkurasi dari pengajuan, dokumen, pembayaran, proses, dan hasil. Data audit mentah tidak ditampilkan." />
    <livewire:admin.activity-feed />
@endsection
