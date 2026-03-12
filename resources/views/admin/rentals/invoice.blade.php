<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $rental->code }}</title>
    <style>
        /* ─── PAGE SETUP ───────────────────────────────────────────── */
        @page {
            size: A4 portrait;
            margin-top: 0;
            margin-bottom: 18mm;
            margin-left: 0;
            margin-right: 0;
        }

        @page :first {
            margin-top: 0;
            margin-bottom: 18mm;
            margin-left: 0;
            margin-right: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #ffffff;
        }

        /* ─── HEADER ───────────────────────────────────────────────── */
        .header {
            background-color: #0d1f35;
            color: #ffffff;
            padding: 0;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 42%;
            padding: 22px 24px 22px 28px;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        .header-logo-row {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .header-logo-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .header-logo-cell img {
            height: 150px;
            margin: -30px 0;
            max-width: 200px;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
            margin-bottom: 12px;
        }

        .invoice-title-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 58%;
            padding: 22px 28px;
        }

        .bill-to-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .bill-to-name {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .bill-to-detail {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }

        /* ─── META BAR ─────────────────────────────────────────────── */
        .meta-cell {
            display: flex;
            flex-direction: column;
            margin-bottom: 10px;
            width: 100%;
        }

        .meta-label {
            font-size: 8px;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        /* ─── CONTENT AREA ─────────────────────────────────────────── */
        .content {
            padding: 20px 28px;
        }

        /* ─── ITEMS TABLE ──────────────────────────────────────────── */
        .section-title {
            font-size: 8px;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }

        table.items thead tr {
            background-color: #0d1f35;
            color: #ffffff;
        }

        table.items thead th {
            padding: 9px 10px;
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #ffffff;
        }

        table.items thead th.right {
            text-align: right;
        }

        table.items thead th.center {
            text-align: center;
        }

        table.items tbody tr {
            border-bottom: 1px solid #f0f0f5;
        }

        table.items tbody tr:last-child {
            border-bottom: 2px solid #e5e7eb;
        }

        table.items tbody td {
            padding: 9px 10px;
            font-size: 11px;
            color: #374151;
            vertical-align: middle;
        }

        table.items tbody td.right {
            text-align: right;
        }

        table.items tbody td.center {
            text-align: center;
        }

        table.items tbody td .item-name {
            font-weight: 700;
            color: #111827;
            font-size: 11px;
        }

        table.items tbody td .item-note {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 1px;
        }

        /* ─── SUMMARY ──────────────────────────────────────────────── */
        .summary-outer {
            display: table;
            width: 100%;
            margin-top: 4px;
            padding: 0 28px 12px 28px;
        }

        .summary-spacer {
            display: table-cell;
            width: 52%;
        }

        .summary-block {
            display: table-cell;
            width: 48%;
        }

        .sum-row {
            display: table;
            width: 100%;
            padding: 3px 0;
        }

        .sum-key {
            display: table-cell;
            font-size: 10px;
            color: #6b7280;
        }

        .sum-val {
            display: table-cell;
            text-align: right;
            font-size: 10px;
            color: #374151;
            font-weight: 500;
        }

        .sum-row.discount .sum-key,
        .sum-row.discount .sum-val {
            color: #16a34a;
        }

        .sum-row.extra .sum-key,
        .sum-row.extra .sum-val {
            color: #2563eb;
        }

        .sum-row.fine .sum-key,
        .sum-row.fine .sum-val {
            color: #dc2626;
        }

        .sum-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 5px 0;
        }

        .sum-row.grand .sum-key,
        .sum-row.grand .sum-val {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .sum-row.paid .sum-key,
        .sum-row.paid .sum-val {
            color: #16a34a;
            font-weight: 600;
        }

        /* ─── SISA TAGIHAN BOX ─────────────────────────────────────── */
        .sisa-box {
            margin: 0 28px 16px 28px;
        }

        .sisa-inner {
            display: table;
            width: 100%;
        }

        .sisa-spacer {
            display: table-cell;
            width: 52%;
        }

        .sisa-content {
            display: table-cell;
            width: 48%;
        }

        .sisa-row {
            display: table;
            width: 100%;
            background-color: #0d1f35;
            border-radius: 4px;
        }

        .sisa-label {
            display: table-cell;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .sisa-value {
            display: table-cell;
            padding: 10px 14px;
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
        }

        .sisa-row.lunas .sisa-value {
            color: #86efac;
        }

        .sisa-row.belum-lunas .sisa-value {
            color: #fca5a5;
        }

        /* ─── STATUS SECTION ───────────────────────────────────────── */
        .status-section {
            padding: 0 28px 14px 28px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-badge.lunas {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .status-badge.belum-lunas {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        /* ─── NOTES ────────────────────────────────────────────────── */
        .notes-wrap {
            margin: 0 28px 16px 28px;
            background: #f9fafb;
            border-left: 3px solid #0d1f35;
            border-radius: 0 4px 4px 0;
            padding: 10px 14px;
        }

        .notes-label {
            font-size: 8px;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .notes-text {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.6;
        }

        /* ─── FOOTER ───────────────────────────────────────────────── */
        .footer {
            margin-top: 14px;
            /* border-top: 2px solid #0d1f35; */
            padding: 12px 28px;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            text-align: right;
        }

        .footer-brand {
            font-size: 11px;
            font-weight: 700;
            color: #0d1f35;
        }

        .footer-text {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 2px;
        }
    </style>
</head>

<body>

    {{-- ─── HEADER ──────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            <div class="header-logo-row">
                <div class="header-logo-cell">
                    <img src="{{ public_path('storage/bg/logo-white.png') }}" alt="Vazza Wedding Gallery">
                </div>
            </div>

            <div class="meta-bar">
                <div class="meta-cell">
                    <span class="meta-label">No. Invoice</span>
                    <span class="meta-value">{{ $rental->code }}</span>
                </div>

            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title-label">Dokumen</div>
            <div class="invoice-title">INVOICE</div>

            <div class="bill-to-label">Tagihan Kepada</div>
            <div class="bill-to-name">{{ $rental->customer->name }}</div>
            <div class="bill-to-detail">
                @if($rental->shipping_address ?? $rental->customer->address)
                    {{ $rental->shipping_address ?? $rental->customer->address }}<br>
                @endif
                @if($rental->customer->phone)
                    Telp: {{ $rental->customer->phone }}<br>
                @endif
                Tanggal: {{ $rental->start_date ? $rental->start_date->format('d/m/Y') : '-' }}<br>
                Tgl. Jatuh Tempo: {{ $rental->due_date ? $rental->due_date->format('d/m/Y') : '-' }}
            </div>
        </div>
    </div>

    {{-- ─── ITEMS TABLE ─────────────────────────────────────── --}}
    <div class="content">
        <div class="section-title">Daftar Item Disewa</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:3%">#</th>
                    <th style="width:48%">Deskripsi</th>
                    <th class="center" style="width:15%">Kuantitas</th>
                    <th class="right" style="width:17%">Harga Satuan</th>
                    <th class="right" style="width:17%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rental->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product->name ?? $item->product_name }}</div>
                            @if($item->notes)
                                <div class="item-note">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="center">{{ (float) $item->quantity }} {{ $item->unit }}</td>
                        <td class="right">Rp {{ number_format($item->price_at_rental, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:#9ca3af; font-style:italic; padding:18px;">
                            Tidak ada item.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ─── SUMMARY ─────────────────────────────────────────── --}}
    @php
        $grandTotal = $rental->total_fee + $rental->shipping_cost + $rental->additional_cost + $rental->total_fines;
        $isLunas = $rental->remaining_balance <= 0;
    @endphp

    <div class="summary-outer">
        <div class="summary-spacer"></div>
        <div class="summary-block">

            <div class="sum-row">
                <span class="sum-key">Subtotal (Bruto)</span>
                <span class="sum-val">Rp {{ number_format($rental->total_gross, 0, ',', '.') }}</span>
            </div>

            @if($rental->discount_order > 0)
                <div class="sum-row discount">
                    <span class="sum-key">Diskon</span>
                    <span class="sum-val">- Rp {{ number_format($rental->discount_order, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="sum-row">
                <span class="sum-key" style="font-weight:600; color:#374151;">Total Sewa (Net)</span>
                <span class="sum-val" style="font-weight:600; color:#374151;">Rp
                    {{ number_format($rental->total_fee, 0, ',', '.') }}</span>
            </div>

            @if($rental->shipping_cost > 0)
                <div class="sum-row">
                    <span class="sum-key">Ongkir PP</span>
                    <span class="sum-val">Rp {{ number_format($rental->shipping_cost, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($rental->additional_cost > 0)
                <div class="sum-row extra">
                    <span class="sum-key">Biaya
                        Tambahan{{ $rental->additional_cost_note ? ' (' . $rental->additional_cost_note . ')' : '' }}</span>
                    <span class="sum-val">+ Rp {{ number_format($rental->additional_cost, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($rental->total_fines > 0)
                <div class="sum-row fine">
                    <span class="sum-key">Denda</span>
                    <span class="sum-val">+ Rp {{ number_format($rental->total_fines, 0, ',', '.') }}</span>
                </div>
            @endif

            <hr class="sum-divider">

            <div class="sum-row grand">
                <span class="sum-key">Total</span>
                <span class="sum-val">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>

            <div class="sum-row paid">
                <span class="sum-key">Pembayaran Diterima</span>
                <span class="sum-val">Rp {{ number_format($rental->paid_amount, 0, ',', '.') }}</span>
            </div>

        </div>
    </div>

    {{-- ─── SISA TAGIHAN ────────────────────────────────────── --}}
    <div class="sisa-box">
        <div class="sisa-inner">
            <div class="sisa-spacer"></div>
            <div class="sisa-content">
                <div class="sisa-row {{ $isLunas ? 'lunas' : 'belum-lunas' }}">
                    <span class="sisa-label">Sisa Tagihan</span>
                    <span class="sisa-value">
                        @if($isLunas)
                            Rp 0
                        @else
                            Rp {{ number_format($rental->remaining_balance, 0, ',', '.') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── STATUS BADGE ────────────────────────────────────── --}}
    <div class="status-section">
        @if($isLunas)
            <span class="status-badge lunas">LUNAS</span>
        @else
            <span class="status-badge belum-lunas">BELUM LUNAS</span>
        @endif
    </div>

    {{-- ─── CATATAN ─────────────────────────────────────────── --}}
    @if($rental->notes)
        <div class="notes-wrap">
            <div class="notes-label">Catatan</div>
            <div class="notes-text">{{ $rental->notes }}</div>
        </div>
    @endif

    {{-- ─── FOOTER ──────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-left">
            <div class="footer-brand">Vazza Wedding Gallery</div>
            <div class="footer-text">vazzawedding.com | 0812-3456-7890</div>
        </div>
        <div class="footer-right">
            <div class="footer-text">
                Dicetak: {{ now()->format('d/m/Y, H:i') }}
            </div>
        </div>
    </div>

</body>

</html>