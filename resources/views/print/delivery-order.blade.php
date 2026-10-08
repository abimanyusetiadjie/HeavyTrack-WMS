<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $do->do_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 5px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .items-table {
            margin-top: 15px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        .items-table th {
            background-color: #f0f0f0;
        }
        .footer-table {
            margin-top: 30px;
            text-align: center;
        }
        .footer-table td {
            width: 33%;
            padding-top: 50px;
        }
    </style>
</head>
<body>
    <div class="title">SURAT JALAN</div>
    
    <table class="header-table">
        <tr>
            <td width="15%"><strong>No. DO</strong></td>
            <td width="35%">: {{ $do->do_number }}</td>
            <td width="15%"><strong>Tanggal</strong></td>
            <td width="35%">: {{ \Carbon\Carbon::parse($do->delivery_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Kepada</strong></td>
            <td>: {{ $do->contact->company_name ?? '-' }}</td>
            <td><strong>Gudang</strong></td>
            <td>: {{ $do->warehouse->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td colspan="3">: {{ $do->contact->address ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Pengemudi</strong></td>
            <td>: {{ $do->driver_name ?? '-' }}</td>
            <td><strong>No. Polisi</strong></td>
            <td>: {{ $do->vehicle_plate_number ?? '-' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Part Number</th>
                <th width="45%">Nama Barang</th>
                <th width="15%">Qty</th>
                <th width="15%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($do->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->part->part_number ?? '-' }}</td>
                <td>{{ $item->part->name ?? '-' }} ({{ $item->part->brand->name ?? '' }})</td>
                <td style="text-align: center;">{{ $item->qty }}</td>
                <td style="text-align: center;">{{ $item->unit }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:10px;"><strong>Catatan:</strong> {{ $do->notes ?? '-' }}</p>

    <table class="footer-table">
        <tr>
            <td>
                Diterima Oleh,<br><br><br><br>
                (___________________)
            </td>
            <td>
                Pengemudi,<br><br><br><br>
                (___________________)
            </td>
            <td>
                Hormat Kami,<br><br><br><br>
                ({{ $do->creator->name ?? 'Admin' }})
            </td>
        </tr>
    </table>
</body>
</html>
