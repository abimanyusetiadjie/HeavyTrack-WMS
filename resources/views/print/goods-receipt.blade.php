<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penerimaan Barang - {{ $gr->receipt_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 15px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        .header-box { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; }
        .company-sub { font-size: 9px; color: #475569; margin-top: 2px; }
        .doc-title { font-size: 16px; font-weight: bold; color: #0f172a; text-align: right; }
        .doc-number { font-size: 11px; font-family: monospace; color: #334155; text-align: right; margin-top: 2px; }
        .meta-table td { vertical-align: top; padding: 3px 4px; font-size: 11px; }
        .items-table { margin-top: 12px; margin-bottom: 12px; }
        .items-table th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px 4px; font-size: 10px; text-transform: uppercase; }
        .items-table td { border: 1px solid #cbd5e1; padding: 5px 4px; }
        .footer-table { margin-top: 25px; text-align: center; }
        .footer-table td { width: 33%; padding-top: 45px; font-size: 10px; }
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
                        Gudang & Logistik Masuk (Inbound Logistics)
                    </div>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="doc-title">BUKTI PENERIMAAN BARANG</div>
                    <div class="doc-number">No: {{ $gr->receipt_number }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="meta-table">
        <tr>
            <td width="18%"><strong>Tgl Penerimaan</strong></td>
            <td width="32%">: {{ \Carbon\Carbon::parse($gr->receipt_date)->format('d F Y') }}</td>
            <td width="18%"><strong>Gudang Penerima</strong></td>
            <td width="32%">: {{ $gr->warehouse->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Supplier / Pemasok</strong></td>
            <td>: <strong>{{ $gr->contact->company_name ?? '-' }}</strong></td>
            <td><strong>No. Surat Jalan Vendor</strong></td>
            <td>: {{ $gr->supplier_do_number ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Status Fisik</strong></td>
            <td>: <strong>{{ $gr->status === 'RECEIVED' ? 'DITERIMA DI GUDANG' : $gr->status }}</strong></td>
            <td><strong>No. Ref. PO</strong></td>
            <td>: {{ $gr->po_reference_number ?? '-' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">No</th>
                <th width="22%">Part Number</th>
                <th width="40%">Nama Part & Spesifikasi</th>
                <th width="15%" style="text-align: center;">Qty Diterima</th>
                <th width="18%">Lokasi Rak</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gr->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->part->part_number ?? '-' }}</td>
                <td>{{ $item->part->name ?? '-' }} @if(isset($item->part->brand->name))<small>({{ $item->part->brand->name }})</small>@endif</td>
                <td style="text-align: center; font-weight: bold;">{{ $item->qty_received }} {{ $item->unit }}</td>
                <td>{{ $item->bin_location_snapshot ?? $item->part->bin_location ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="font-size: 10px; margin-top: 5px;">
        <strong>Catatan Pemeriksaan Barang:</strong> {{ $gr->notes ?? 'Barang telah diperiksa fisik dan sesuai dengan dokumen pengantar vendor.' }}
    </div>

    <table class="footer-table">
        <tr>
            <td>
                Pengirim (Vendor/Ekspedisi),<br><br><br><br>
                (___________________)
            </td>
            <td>
                Petugas QC / Pemeriksa,<br><br><br><br>
                (___________________)
            </td>
            <td>
                Kepala Gudang Penerima,<br><br><br><br>
                <strong>( {{ $gr->creator->name ?? 'Staf Gudang' }} )</strong>
            </td>
        </tr>
    </table>
</body>
</html>
