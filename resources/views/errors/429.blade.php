@extends('errors.layout')

@section('title', 'Terlalu Banyak Percobaan')
@section('code', '429')
@section('message')
    <div x-data="{ seconds: {{ $seconds ?? 60 }} }" x-init="if (seconds > 0) setInterval(() => { if (seconds > 0) seconds-- }, 1000)">
        <p class="mb-3">{{ $message ?? 'Terlalu banyak percobaan dalam waktu singkat.' }}</p>
        <template x-if="seconds > 0">
            <p class="font-medium text-slate-700">Silakan coba kembali dalam <span class="text-rose-600 font-bold" x-text="seconds"></span> detik.</p>
        </template>
        <template x-if="seconds <= 0">
            <p class="font-medium text-emerald-600">Anda dapat mencoba login kembali sekarang.</p>
        </template>
    </div>
@endsection
