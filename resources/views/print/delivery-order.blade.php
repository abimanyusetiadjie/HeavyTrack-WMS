<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $do->do_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            color: #0f172a;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .header-box {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
            line-height: 11px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .doc-number {
            font-size: 11px;
            font-family: monospace;
            color: #1e293b;
            text-align: right;
            font-weight: bold;
            margin-top: 2px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 3px 4px;
            font-size: 9.5px;
            text-transform: uppercase;
            line-height: 13px;
        }
        .items-table {
            margin-top: 10px;
            margin-bottom: 10px;
            page-break-inside: auto;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table th {
            background-color: #f1f5f9;
            border: 1px solid #64748b;
            padding: 5px 6px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 6px;
            text-transform: uppercase;
            font-size: 9px;
            line-height: 12px;
        }
        .notes-box {
            font-size: 9px;
            margin-top: 6px;
            text-transform: uppercase;
            color: #334155;
            line-height: 12px;
        }
        .footer-table {
            margin-top: 20px;
            text-align: center;
            text-transform: uppercase;
            page-break-inside: avoid;
        }
        .footer-table td {
            width: 33.3%;
            font-size: 9px;
            text-transform: uppercase;
            vertical-align: top;
            padding: 0 8px;
        }
        .sig-space {
            height: 48px;
            text-align: center;
            line-height: 48px;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $hasGd = extension_loaded('gd') && function_exists('imagecreatefrompng');
        $logoBase64 = ($hasGd && file_exists($logoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $sigPath = public_path('images/signature.png');
        $sigBase64 = file_exists($sigPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sigPath)) : null;
    @endphp

    <div class="header-box">
        <table class="header-table">
            <tr>
                <td style="width: 62%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            @if($logoBase64)
                            <td style="width: 48px; vertical-align: middle; padding: 0 6px 0 0; border: none;">
                                <img src="{{ $logoBase64 }}" style="height: 40px; width: auto; display: block;" alt="Logo" />
                            </td>
                            @endif
                            <td style="vertical-align: middle; border: none; padding: 0;">
                                <div class="company-name">MITRA ABADI NUSANTARA</div>
                                <div class="company-sub">
                                    HEAVY EQUIPMENT SPARE PARTS, HEAVY DUTY FILTRATION & INDUSTRIAL SUPPLY<br>
                                    TELP: 081297354119 | EMAIL: <span style="text-transform: lowercase;">revaldojulian07@gmail.com</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 38%; vertical-align: middle; text-align: right;">
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
            <td>
                : <strong>{{ strtoupper($do->contact->company_name ?? '-') }}</strong>
                @if(!empty($do->contact->pic_name))
                <div style="font-size: 8.5px; color: #1e293b; margin-top: 1px;">
                    &nbsp;&nbsp;UP / PIC: <strong>{{ strtoupper($do->contact->pic_name) }}</strong>@if(!empty($do->contact->phone)) ({{ $do->contact->phone }})@endif
                </div>
                @endif
            </td>
            <td><strong>PENGEMUDI (DRIVER)</strong></td>
            <td>: {{ strtoupper($do->driver_name ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>ALAMAT TUJUAN</strong></td>
            <td>: {{ strtoupper($do->contact->address ?? '-') }}</td>
            <td><strong>NO. KENDARAAN</strong></td>
            <td>: {{ strtoupper($do->vehicle_plate_number ?? '-') }}</td>
        </tr>
        @if(!empty($do->sales_order_reference) || !empty($do->salesOrder?->order_number))
        <tr>
            <td><strong>REF. SO / PO</strong></td>
            <td colspan="3">: {{ strtoupper($do->sales_order_reference ?? $do->salesOrder?->order_number) }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="4%" style="text-align: center;">NO</th>
                <th width="23%">PART NUMBER</th>
                <th width="45%">NAMA BARANG / SPESIFIKASI</th>
                <th width="14%" style="text-align: center;">JUMLAH (QTY)</th>
                <th width="14%" style="text-align: center;">SATUAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($do->items as $itemIdx => $item)
            <tr>
                <td style="text-align: center;">{{ $itemIdx + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ strtoupper($item->part->part_number ?? '-') }}</td>
                <td>
                    {{ strtoupper($item->part->name ?? '-') }}
                    @if(isset($item->part->brand->name))<small>({{ strtoupper($item->part->brand->name) }})</small>@endif
                    @if(!empty($item->notes))
                    <div style="font-size: 8px; color: #475569; margin-top: 1px;">{{ strtoupper($item->notes) }}</div>
                    @endif
                </td>
                <td style="text-align: center; font-weight: bold;">{{ $item->qty }}</td>
                <td style="text-align: center;">{{ strtoupper($item->unit) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #64748b; padding: 15px; font-style: italic;">
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
                DITERIMA OLEH,
                <div class="sig-space"></div>
                ( {{ strtoupper($do->contact?->pic_name ?: '___________________') }} )<br>
                TGL:
            </td>
            <td>
                PENGEMUDI / EKSPEDISI,
                <div class="sig-space"></div>
                ( {{ strtoupper($do->driver_name ?: '___________________') }} )
            </td>
            <td>
                HORMAT KAMI,
                <div class="sig-space">
                    @if($sigBase64)
                    <img src="{{ $sigBase64 }}" style="height: 44px; width: auto; vertical-align: middle; display: inline-block;" alt="Tanda Tangan" />
                    @endif
                </div>
                ( REVALDO JULIAN )
            </td>
        </tr>
    </table>
</body>
</html>
