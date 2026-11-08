<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler harian rekonsiliasi integritas stok persediaan pukul 01:00 WIB (FR-SYS-01, PRD §5.14)
Schedule::command('stockhub:reconcile')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta');
