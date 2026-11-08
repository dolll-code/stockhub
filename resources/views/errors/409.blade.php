@extends('errors.layout')

@section('title', 'Konflik Status Transaksi')
@section('code', '409')
@section('message', $exception->getMessage() ?: 'Transaksi ini sudah diproses oleh pengguna lain atau berstatus berbeda. Silakan muat ulang halaman.')
