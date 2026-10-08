<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur Penjualan - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 5px; }
        .title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .items-table { margin-top: 15px; width: 100%; table-layout: fixed; }
        .items-table tr { page-break-inside: avoid; }
        .summary-table { page-break-inside: avoid; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 5px; text-align: left; }
        .items-table th { background-color: #f0f0f0; }
        .summary-table { margin-top: 20px; width: 40%; float: right; }
        .summary-table td { padding: 5px; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="title">FAKTUR PENJUALAN</div>
    
    <table class="header-table">
        <tr>
            <td width="15%"><strong>No. Faktur</strong></td>
            <td width="35%">: {{ $invoice->invoice_number }}</td>
            <td width="15%"><strong>Tanggal</strong></td>
            <td width="35%">: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>No. Surat Jalan</strong></td>
            <td>: {{ $invoice->deliveryOrder->do_number ?? '-' }}</td>
            <td><strong>Jatuh Tempo</strong></td>
            <td>: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Kepada</strong></td>
            <td>: {{ $invoice->contact->company_name ?? '-' }}</td>
            <td><strong>Status</strong></td>
            <td>: {{ $invoice->status }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td colspan="3">: {{ $invoice->contact->address ?? '-' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Part Number</th>
                <th width="35%">Deskripsi</th>
                <th width="10%">Qty</th>
                <th width="15%">Harga Satuan</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->part_number_snapshot }}</td>
                <td>{{ $item->part_name_snapshot }}</td>
                <td style="text-align: center;">{{ $item->qty }} {{ $item->unit }}</td>
                <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="summary-table">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Diskon</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>PPN ({{ $invoice->tax_percent }}%)</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Grand Total</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($invoice->grand_total, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 50px; text-align: center;">
        <tr>
            <td width="50%">
                Hormat Kami,<br><br><br><br><br>
                (___________________)
            </td>
            <td width="50%">
                Penerima,<br><br><br><br><br>
                (___________________)
            </td>
        </tr>
    </table>
</body>
</html>
