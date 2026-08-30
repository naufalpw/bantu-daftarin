@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <p class="text-sm font-medium uppercase tracking-wide text-indigo-700">Checkout lokal</p>
    <h1 class="mt-2 text-2xl font-semibold">Simulasi pembayaran</h1>
    <p class="mt-2 text-sm text-slate-600">Halaman ini hanya tersedia untuk pengujian lokal dan tidak terhubung ke Xendit.</p>

    <dl class="mt-6 space-y-3 rounded-lg bg-slate-50 p-4 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Layanan</dt><dd class="text-right font-medium">{{ $payment->application->service->name }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Nominal</dt><dd class="font-medium">{{ $payment->currency }} {{ number_format((float) $payment->amount, 0, ',', '.') }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd class="font-medium">Menunggu pembayaran</dd></div>
    </dl>

    <form method="post" action="{{ route('testing.fake-payments.complete', $payment->public_id) }}" class="mt-6">
        @csrf
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-medium text-white hover:bg-indigo-700">Simulasikan pembayaran berhasil</button>
    </form>
    <a href="{{ route('client.applications.show', $payment->application->public_id) }}" class="mt-4 block text-center text-sm text-indigo-700 hover:underline">Kembali ke aplikasi</a>
</div>
@endsection
