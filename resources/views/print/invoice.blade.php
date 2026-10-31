<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur Penjualan - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        .header-box { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 15px; }
        .company-name { font-size: 17px; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; }
        .company-sub { font-size: 9px; color: #475569; margin-top: 2px; }
        .doc-title { font-size: 17px; font-weight: bold; color: #0f172a; text-align: right; }
        .doc-number { font-size: 12px; font-family: monospace; color: #334155; text-align: right; margin-top: 2px; }
        .meta-table td { vertical-align: top; padding: 3px 5px; font-size: 11px; text-transform: uppercase; }
        .items-table { margin-top: 15px; margin-bottom: 15px; }
        .items-table th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px 5px; font-size: 10px; text-transform: uppercase; }
        .items-table td { border: 1px solid #cbd5e1; padding: 6px 5px; text-transform: uppercase; }
        .summary-table { width: 45%; float: right; margin-top: 5px; border-collapse: collapse; }
        .summary-table td { padding: 4px 6px; font-size: 11px; text-transform: uppercase; }
        .payment-info { width: 50%; float: left; margin-top: 5px; font-size: 10px; border: 1px solid #e2e8f0; padding: 8px; border-radius: 4px; text-transform: uppercase; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer-table { margin-top: 30px; text-align: center; text-transform: uppercase; }
        .footer-table td { width: 50%; padding-top: 50px; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $hasGd = extension_loaded('gd') && function_exists('imagecreatefrompng');
        $logoBase64 = ($hasGd && file_exists($logoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    @endphp
    <div class="header-box">
        <table>
            <tr>
                <td style="width: 65%; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            @if($logoBase64)
                            <td style="width: 55px; vertical-align: middle; padding: 0 10px 0 0; border: none;">
                                <img src="{{ $logoBase64 }}" style="height: 48px; width: auto; display: block;" alt="Logo" />
                            </td>
                            @endif
                            <td style="vertical-align: middle; border: none; padding: 0;">
                                <div class="company-name">MITRA ABADI NUSANTARA</div>
                                <div class="company-sub">
                                    HEAVY EQUIPMENT SPARE PARTS, HEAVY DUTY FILTRATION & INDUSTRIAL SUPPLY<br>
                                    TELP: 081297354119 | EMAIL: REVALDO07@GMAIL.COM
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 35%; vertical-align: top;">
                    <div class="doc-title">FAKTUR PENJUALAN</div>
                    <div class="doc-number">NO: {{ strtoupper($invoice->invoice_number) }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="meta-table">
        <tr>
            <td width="15%"><strong>TGL FAKTUR</strong></td>
            <td width="35%">: {{ $invoice->invoice_date ? strtoupper(\Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y')) : '-' }}</td>
            <td width="18%"><strong>REF. SURAT JALAN</strong></td>
            <td width="32%">: {{ strtoupper($invoice->deliveryOrder?->do_number ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>JATUH TEMPO</strong></td>
            <td>: {{ $invoice->due_date ? strtoupper(\Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y')) : '-' }}</td>
            <td><strong>STATUS PEMBAYARAN</strong></td>
            <td>: <strong>{{ strtoupper($invoice->status ?? 'UNPAID') }}</strong></td>
        </tr>
        <tr>
            <td><strong>DITAGIHKAN KE</strong></td>
            <td colspan="3">: <strong>{{ strtoupper($invoice->contact?->company_name ?? '-') }}</strong> ({{ $invoice->contact?->phone ?? '-' }})</td>
        </tr>
        <tr>
            <td><strong>ALAMAT PENAGIHAN</strong></td>
            <td colspan="3">: {{ strtoupper($invoice->contact?->address ?? '-') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">NO</th>
                <th width="20%">PART NUMBER</th>
                <th width="35%">DESKRIPSI BARANG</th>
                <th width="8%" style="text-align: center;">QTY</th>
                <th width="15%" style="text-align: right;">HARGA SATUAN</th>
                <th width="17%" style="text-align: right;">SUBTOTAL (RP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ strtoupper($item->part_number_snapshot ?: ($item->part?->part_number ?? '-')) }}</td>
                <td>{{ strtoupper($item->part_name_snapshot ?: ($item->part?->name ?? 'SPAREPART')) }}</td>
                <td style="text-align: center;">{{ $item->qty }} {{ strtoupper($item->unit ?: 'PCS') }}</td>
                <td style="text-align: right;">Rp {{ number_format((float) ($item->unit_price ?? 0), 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold;">Rp {{ number_format((float) ($item->total_price ?? 0), 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #64748b; padding: 12px; font-style: italic;">
                    (BELUM ADA RINCIAN BARANG PADA FAKTUR INI. SILAKAN TAMBAHKAN BARANG DI RINCIAN TAGIHAN).
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="clearfix">
        <div class="payment-info">
            <strong>INSTRUKSI PEMBAYARAN BANK TRANSFER:</strong><br>
            BANK: <strong>BCA (BANK CENTRAL ASIA)</strong><br>
            NO. REKENING: <strong>6891102400</strong><br>
            ATAS NAMA: <strong>REVALDO JULIAN</strong><br>
            <small style="color: #64748b;">* HARAP MENCANTUMKAN NOMOR FAKTUR PADA BERITA TRANSFER.</small>
        </div>

        <table class="summary-table">
            <tr>
                <td>SUBTOTAL</td>
                <td style="text-align: right;">Rp {{ number_format((float) ($invoice->subtotal ?? 0), 0, ',', '.') }}</td>
            </tr>
            @if(($invoice->discount_amount ?? 0) > 0)
            <tr>
                <td>POTONGAN DISKON</td>
                <td style="text-align: right; color: red;">- Rp {{ number_format((float) $invoice->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(($invoice->tax_amount ?? 0) > 0)
            <tr>
                <td>PPN ({{ $invoice->tax_percent ?? 11 }}%)</td>
                <td style="text-align: right;">+ Rp {{ number_format((float) $invoice->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="border-top: 2px solid #0f172a; font-weight: bold; font-size: 13px;">
                <td>TOTAL TAGIHAN</td>
                <td style="text-align: right;">Rp {{ number_format((float) ($invoice->grand_total ?? 0), 0, ',', '.') }}</td>
            </tr>
            @if(($invoice->paid_amount ?? 0) > 0)
            <tr style="color: green;">
                <td>SUDAH DIBAYAR</td>
                <td style="text-align: right;">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr style="font-weight: bold;">
                <td>SISA TAGIHAN</td>
                <td style="text-align: right;">Rp {{ number_format(max(0, (float) ($invoice->grand_total ?? 0) - (float) ($invoice->paid_amount ?? 0)), 0, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="footer-table">
        <tr>
            <td>
                PENERIMA TAGIHAN,<br><br><br><br><br>
                (___________________)
            </td>
            <td>
                HORMAT KAMI,<br><br><br><br><br>
                (___________________)
            </td>
        </tr>
    </table>
</body>
</html>
