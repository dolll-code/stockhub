<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page {
            margin: 15mm 12mm 15mm 12mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
        }
        .company-info {
            font-size: 8pt;
            color: #64748b;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-top: 6px;
            margin-bottom: 2px;
            text-align: center;
        }
        .report-subtitle {
            font-size: 8.5pt;
            color: #475569;
            text-align: center;
            margin-bottom: 10px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 6px;
            font-size: 8pt;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: left;
            color: #334155;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier', monospace; font-size: 7.5pt; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-aman { background-color: #d1fae5; color: #065f46; }
        .badge-min { background-color: #fef3c7; color: #92400e; }
        .badge-habis { background-color: #ffe4e6; color: #9f1239; }
        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <!-- KOP PERUSAHAAN (FR-SET-01, FR-EXP-01) -->
    <table class="header-table">
        <tr>
            @if(!empty($companyLogoBase64))
                <td style="width: 60px; vertical-align: middle;">
                    <img src="{{ $companyLogoBase64 }}" style="max-height: 45px; max-width: 60px;" alt="Logo" />
                </td>
            @endif
            <td style="vertical-align: middle;">
                <div class="company-name">{{ $companyName ?? 'StockHub Inventory System' }}</div>
                @if(!empty($companyAddress))
                    <div class="company-info">{{ $companyAddress }}</div>
                @endif
                @if(!empty($companyPhone))
                    <div class="company-info">Telp: {{ $companyPhone }}</div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top; font-size: 8pt; color: #64748b;">
                <div>Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }} WIB</div>
                <div>Oleh: {{ $printedBy ?? 'Sistem' }}</div>
            </td>
        </tr>
    </table>

    <div class="report-title">@yield('report_title')</div>
    <div class="report-subtitle">@yield('report_subtitle')</div>

    @if(!empty($activeFilters))
        <div class="meta-box">
            <strong>Filter Diterapkan:</strong> {{ $activeFilters }}
        </div>
    @endif

    @yield('content')

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT} — StockHub";
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 7.5;
            $color = array(0.5, 0.5, 0.5);
            $width = $pdf->get_width();
            $pdf->page_text($width / 2 - 40, $pdf->get_height() - 25, $text, $font, $size, $color);
        }
    </script>
</body>
</html>
