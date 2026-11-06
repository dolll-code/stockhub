<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') — {{ config('app.name', 'StockHub') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full flex items-center justify-center p-6 text-slate-800">
        <div class="max-w-md w-full text-center bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-700 mb-4 text-xl font-bold">
                @yield('code')
            </div>
            <h1 class="text-xl font-bold text-slate-900 mb-2">@yield('title')</h1>
            <p class="text-sm text-slate-600 mb-6">@yield('message')</p>
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </body>
</html>
