@extends('layouts.admin')

@section('title', 'Dokumen')
@section('admin_context', 'Dokumen')

@section('content')
    <x-admin.page-header kicker="DOKUMEN" title="Dokumen yang memerlukan perhatian" description="Periksa dokumen yang perlu ditindaklanjuti pada setiap pengajuan." />

    <livewire:admin.document-queue />
@endsection
