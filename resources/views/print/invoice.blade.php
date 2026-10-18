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
        .meta-table td { vertical-align: top; padding: 3px 5px; font-size: 11px; }
        .items-table { margin-top: 15px; margin-bottom: 15px; }
        .items-table th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px 5px; font-size: 10px; text-transform: uppercase; }
        .items-table td { border: 1px solid #cbd5e1; padding: 6px 5px; }
        .summary-table { width: 45%; float: right; margin-top: 5px; border-collapse: collapse; }
        .summary-table td { padding: 4px 6px; font-size: 11px; }
        .payment-info { width: 50%; float: left; margin-top: 5px; font-size: 10px; border: 1px solid #e2e8f0; padding: 8px; border-radius: 4px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer-table { margin-top: 30px; text-align: center; }
        .footer-table td { width: 50%; padding-top: 50px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header-box">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="company-name">MITRA ABADI NUSANTARA</div>
                    <div class="company-sub">
                        Heavy Equipment Spare Parts, Heavy Duty Filtration & Industrial Supply<br>
                        Telp: 081297354119 | Email: revaldo07@gmail.com
                    </div>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="doc-title">FAKTUR PENJUALAN</div>
                    <div class="doc-number">No: {{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Tgl Faktur</strong></td>
            <td width="35%">: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d F Y') }}</td>
            <td width="18%"><strong>Ref. Surat Jalan</strong></td>
            <td width="32%">: {{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Jatuh Tempo</strong></td>
            <td>: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d F Y') }}</td>
            <td><strong>Status Pembayaran</strong></td>
            <td>: <strong>{{ $invoice->status }}</strong></td>
        </tr>
        <tr>
            <td><strong>Ditagihkan Ke</strong></td>
            <td colspan="3">: <strong>{{ $invoice->contact->company_name ?? '-' }}</strong> ({{ $invoice->contact->phone ?? '-' }})</td>
        </tr>
        <tr>
            <td><strong>Alamat Penagihan</strong></td>
            <td colspan="3">: {{ $invoice->contact->address ?? '-' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">No</th>
                <th width="20%">Part Number</th>
                <th width="35%">Deskripsi Barang</th>
                <th width="8%" style="text-align: center;">Qty</th>
                <th width="15%" style="text-align: right;">Harga Satuan</th>
                <th width="17%" style="text-align: right;">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->part_number_snapshot }}</td>
                <td>{{ $item->part_name_snapshot }}</td>
                <td style="text-align: center;">{{ $item->qty }} {{ $item->unit }}</td>
                <td style="text-align: right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold;">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="payment-info">
            <strong>Instruksi Pembayaran Bank Transfer:</strong><br>
            Bank: <strong>BCA (Bank Central Asia)</strong><br>
            No. Rekening: <strong>6891102400</strong><br>
            Atas Nama: <strong>MITRA</strong><br>
            <small style="color: #64748b;">* Harap mencantumkan nomor faktur pada berita transfer.</small>
        </div>

        <table class="summary-table">
            <tr>
                <td>Subtotal</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td>Potongan Diskon</td>
                <td style="text-align: right; color: red;">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td>PPN ({{ $invoice->tax_percent ?? 11 }}%)</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #0f172a; font-weight: bold; font-size: 13px;">
                <td>TOTAL TAGIHAN</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->paid_amount > 0)
            <tr style="color: green;">
                <td>Sudah Dibayar</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr style="font-weight: bold;">
                <td>Sisa Tagihan</td>
                <td style="text-align: right;">Rp {{ number_format(max(0, $invoice->grand_total - $invoice->paid_amount), 0, ',', '.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="footer-table">
        <tr>
            <td>
                Penerima Tagihan,<br><br><br><br>
                (___________________)
            </td>
            <td>
                Hormat Kami,<br><br><br><br>
                <strong>( Bagian Keuangan )</strong><br>
                Mitra Abadi Nusantara
            </td>
        </tr>
    </table>
</body>
</html>
