<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $do->do_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            color: #1e293b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-box {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .doc-number {
            font-size: 11px;
            font-family: monospace;
            color: #334155;
            text-align: right;
            margin-top: 2px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 3px 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table {
            margin-top: 12px;
            margin-bottom: 12px;
        }
        .items-table th {
            background-color: #f1f5f9;
            border: 1px solid #94a3b8;
            padding: 6px 4px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            text-transform: uppercase;
        }
        .notes-box {
            font-size: 10px;
            margin-top: 6px;
            text-transform: uppercase;
        }
        .footer-table {
            margin-top: 20px;
            text-align: center;
            text-transform: uppercase;
        }
        .footer-table td {
            width: 33%;
            padding-top: 45px;
            font-size: 10px;
            text-transform: uppercase;
        }
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
                    <div class="doc-title">SURAT JALAN</div>
                    <div class="doc-number">NO: {{ strtoupper($do->do_number) }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="meta-table">
        <tr>
            <td width="15%"><strong>TGL PENGIRIMAN</strong></td>
            <td width="35%">: {{ strtoupper(\Carbon\Carbon::parse($do->delivery_date)->translatedFormat('d F Y')) }}</td>
            <td width="15%"><strong>GUDANG ASAL</strong></td>
            <td width="35%">: {{ strtoupper($do->warehouse->name ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>PENERIMA / KLIEN</strong></td>
            <td>: <strong>{{ strtoupper($do->contact->company_name ?? '-') }}</strong></td>
            <td><strong>PENGEMUDI (DRIVER)</strong></td>
            <td>: {{ strtoupper($do->driver_name ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>ALAMAT TUJUAN</strong></td>
            <td>: {{ strtoupper($do->contact->address ?? '-') }}</td>
            <td><strong>NO. KENDARAAN</strong></td>
            <td>: {{ strtoupper($do->vehicle_plate_number ?? '-') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">NO</th>
                <th width="22%">PART NUMBER</th>
                <th width="43%">NAMA BARANG / SPESIFIKASI</th>
                <th width="15%" style="text-align: center;">JUMLAH (QTY)</th>
                <th width="15%" style="text-align: center;">SATUAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($do->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ strtoupper($item->part->part_number ?? '-') }}</td>
                <td>
                    {{ strtoupper($item->part->name ?? '-') }}
                    @if(isset($item->part->brand->name))<small>({{ strtoupper($item->part->brand->name) }})</small>@endif
                    @if(!empty($item->notes))
                    <div style="font-size: 9px; color: #475569; margin-top: 2px;">{{ strtoupper($item->notes) }}</div>
                    @endif
                </td>
                <td style="text-align: center; font-weight: bold;">{{ $item->qty }}</td>
                <td style="text-align: center;">{{ strtoupper($item->unit) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #64748b; padding: 12px; font-style: italic;">
                    (BELUM ADA RINCIAN BARANG PADA SURAT JALAN INI. SILAKAN TAMBAHKAN BARANG DI MENU SURAT JALAN).
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="notes-box">
        <strong>CATATAN PENGIRIMAN:</strong> {{ strtoupper($do->notes ?? 'BARANG DIKIRIM DALAM KONDISI BAIK DAN LENGKAP.') }}
    </div>

    <table class="footer-table">
        <tr>
            <td>
                DITERIMA OLEH,<br><br><br><br>
                (___________________)<br>
                TGL:
            </td>
            <td>
                PENGEMUDI / EKSPEDISI,<br><br><br><br>
                ( {{ strtoupper($do->driver_name ?: '___________________') }} )
            </td>
            <td>
                HORMAT KAMI,<br><br><br><br><br>
                (___________________)
            </td>
        </tr>
    </table>
</body>
</html>
