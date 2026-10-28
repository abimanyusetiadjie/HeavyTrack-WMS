<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kwitansi Pembayaran - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        .header-box { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; }
        .company-sub { font-size: 9px; color: #475569; margin-top: 2px; }
        .doc-title { font-size: 16px; font-weight: bold; color: #0f172a; text-align: right; }
        .doc-number { font-size: 11px; font-family: monospace; color: #334155; text-align: right; margin-top: 2px; }
        .content-table { margin-top: 15px; }
        .content-table td { padding: 8px 5px; vertical-align: top; font-size: 12px; }
        .terbilang-box { background-color: #f8fafc; border: 1px dashed #94a3b8; padding: 8px 12px; font-style: italic; font-weight: 500; color: #0f172a; border-radius: 4px; }
        .amount-container { margin-top: 20px; width: 45%; float: left; }
        .amount-box { border: 2px solid #0f172a; background-color: #f1f5f9; padding: 10px 15px; font-size: 16px; font-weight: bold; color: #0f172a; display: inline-block; border-radius: 4px; }
        .signatures { margin-top: 20px; width: 45%; float: right; text-align: center; font-size: 11px; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $hasGd = extension_loaded('gd') && function_exists('imagecreatefrompng');
        $logoBase64 = ($hasGd && file_exists($logoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
        $latestPayment = $invoice->payments->last();
        $totalPaid = (float) $invoice->payments->sum('amount');
        $receiptNumber = $latestPayment?->receipt_number ?? ('BKM/' . date('Ym') . '/' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT));
        $paymentDate = $latestPayment?->payment_date ? \Carbon\Carbon::parse($latestPayment->payment_date) : now();
    @endphp

    <div class="header-box">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            @if($logoBase64)
                            <td style="width: 50px; vertical-align: middle; padding: 0 10px 0 0; border: none;">
                                <img src="{{ $logoBase64 }}" style="height: 42px; width: auto; display: block;" alt="Logo" />
                            </td>
                            @endif
                            <td style="vertical-align: middle; border: none; padding: 0;">
                                <div class="company-name">MITRA ABADI NUSANTARA</div>
                                <div class="company-sub">
                                    Heavy Equipment Spare Parts, Heavy Duty Filtration & Industrial Supply<br>
                                    Telp: 081297354119 | Email: revaldo07@gmail.com
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="doc-title">KWITANSI PEMBAYARAN</div>
                    <div class="doc-number">No: {{ $receiptNumber }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="content-table">
        <tr>
            <td style="width: 22%;"><strong>Telah Terima Dari</strong></td>
            <td style="width: 3%;">:</td>
            <td style="width: 75%; font-weight: bold; font-size: 13px; color: #0f172a;">
                {{ $invoice->contact->company_name ?? 'Pelanggan Umum' }}
            </td>
        </tr>
        <tr>
            <td><strong>Uang Sejumlah</strong></td>
            <td>:</td>
            <td>
                <div class="terbilang-box">
                    # {{ \App\Helpers\NumberHelper::terbilangRupiah($totalPaid) }} #
                </div>
            </td>
        </tr>
        <tr>
            <td><strong>Untuk Pembayaran</strong></td>
            <td>:</td>
            <td>
                Pelunasan / Pembayaran Faktur Penjualan No. <strong>{{ $invoice->invoice_number }}</strong>
                @if($latestPayment?->reference_number)
                    <br><span style="font-size: 11px; color: #64748b;">(Ref / Bukti Transfer: {{ $latestPayment->reference_number }})</span>
                @endif
                @if($latestPayment?->notes)
                    <br><span style="font-size: 11px; color: #64748b;">Catatan: {{ $latestPayment->notes }}</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="clearfix" style="margin-top: 15px;">
        <div class="amount-container">
            <div style="font-size: 10px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; font-weight: bold;">Jumlah Dibayar:</div>
            <div class="amount-box">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
            </div>
        </div>

        <div class="signatures">
            Jakarta, {{ $paymentDate->translatedFormat('d F Y') }}<br>
            <strong>Mitra Abadi Nusantara</strong>
            <br><br><br><br><br>
            ( _________________________ )<br>
            <span style="font-size: 9px; color: #64748b;">Kasir / Bagian Keuangan</span>
        </div>
    </div>
</body>
</html>
