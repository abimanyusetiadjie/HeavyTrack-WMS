<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bukti Bayar - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        .title { font-size: 20px; font-weight: bold; text-align: center; margin-bottom: 20px; text-decoration: underline; }
        .content-table td { padding: 10px 5px; vertical-align: top; }
        .amount-box { border: 2px solid #000; padding: 10px; font-size: 18px; font-weight: bold; width: 250px; text-align: center; margin-top: 20px; }
        .signatures { margin-top: 40px; text-align: right; padding-right: 50px; }
    </style>
</head>
<body>
    <div class="title">KWITANSI / BUKTI PEMBAYARAN</div>
    
    <table class="content-table">
        <tr>
            <td width="25%"><strong>No. Bukti</strong></td>
            <td width="5%">:</td>
            <td width="70%">{{ $invoice->payments->last()->receipt_number ?? 'BKM-' . date('Ymd-His') }}</td>
        </tr>
        <tr>
            <td><strong>Telah Terima Dari</strong></td>
            <td>:</td>
            <td>{{ $invoice->contact->company_name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Uang Sejumlah</strong></td>
            <td>:</td>
            <td style="background-color: #f0f0f0; padding: 10px; font-style: italic;">
                
            </td>
        </tr>
        <tr>
            <td><strong>Untuk Pembayaran</strong></td>
            <td>:</td>
            <td>Pelunasan/Cicilan Faktur No. {{ $invoice->invoice_number }}</td>
        </tr>
    </table>

    <div class="amount-box">
        Rp {{ number_format($invoice->payments->sum('amount'), 2, ',', '.') }}
    </div>

    <div class="signatures">
        Jakarta, {{ \Carbon\Carbon::parse($invoice->payments->last()->payment_date ?? now())->format('d F Y') }}<br><br><br><br><br>
        ( _________________________ )
    </div>
</body>
</html>
