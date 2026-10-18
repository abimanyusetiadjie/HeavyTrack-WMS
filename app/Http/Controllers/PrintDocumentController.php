<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintDocumentController extends Controller
{
    public function printDeliveryOrder(DeliveryOrder $deliveryOrder)
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Admin') || $user->hasRole('Gudang') || $user->hasRole('Kasir') || $user->id === 1), 403, 'Anda tidak memiliki akses untuk mencetak Surat Jalan ini.');

        $deliveryOrder->load(['items.part.brand', 'contact', 'warehouse', 'creator']);
        
        $pdf = Pdf::loadView('print.delivery-order', ['do' => $deliveryOrder])
            ->setPaper([0, 0, 609.45, 396.85], 'portrait'); // 215mm x 140mm in points
            
        return $pdf->stream('Surat_Jalan_' . str_replace('/', '_', $deliveryOrder->do_number) . '.pdf');
    }

    public function printInvoiceFromDeliveryOrder(DeliveryOrder $deliveryOrder)
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Admin') || $user->hasRole('Kasir') || $user->hasRole('Gudang') || $user->id === 1), 403, 'Anda tidak memiliki akses untuk mencetak Faktur.');

        // 1. Cek apakah sudah ada faktur terkait Surat Jalan ini
        $invoice = Invoice::where('delivery_order_id', $deliveryOrder->id)->latest()->first();

        // 2. Jika belum ada, otomatis terbitkan faktur baru dari rincian Surat Jalan
        if (!$invoice) {
            $deliveryOrder->load(['items.part' => fn ($q) => $q->withTrashed(), 'contact']);
            
            abort_if($deliveryOrder->items->isEmpty(), 400, 'Surat Jalan belum memiliki rincian barang. Silakan isi barang pada Surat Jalan terlebih dahulu.');

            $count = Invoice::withTrashed()->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count() + 1;
            $invoiceNumber = 'INV/' . date('Ym') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $itemsData = [];
            foreach ($deliveryOrder->items as $item) {
                $price = round((float) ($item->part?->selling_price ?? 0));
                $lineTotal = round($item->qty * $price);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'part_id' => $item->part_id,
                    'part_number_snapshot' => $item->part?->part_number ?? '-',
                    'part_name_snapshot' => $item->part?->name ?? 'Sparepart',
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'unit_price' => $price,
                    'discount_percent' => 0,
                    'total_price' => $lineTotal,
                ];
            }

            $taxPercent = 11;
            $taxAmount = round($subtotal * ($taxPercent / 100));
            $grandTotal = $subtotal + $taxAmount;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'delivery_order_id' => $deliveryOrder->id,
                'contact_id' => $deliveryOrder->contact_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'UNPAID',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'notes' => 'Faktur diterbitkan otomatis dari Surat Jalan: ' . $deliveryOrder->do_number,
                'created_by' => $user->id ?? 1,
            ]);

            foreach ($itemsData as $data) {
                $data['invoice_id'] = $invoice->id;
                \App\Models\InvoiceItem::create($data);
            }
        }

        $invoice->load(['items', 'contact', 'creator', 'deliveryOrder']);
        $pdf = Pdf::loadView('print.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $pdf->stream('Faktur_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printInvoice(Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Admin') || $user->hasRole('Kasir') || $user->id === 1), 403, 'Anda tidak memiliki akses untuk mencetak Faktur keuangan.');

        $invoice->load(['items', 'contact', 'creator', 'deliveryOrder']);
        $pdf = Pdf::loadView('print.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $pdf->stream('Faktur_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printReceipt(Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Admin') || $user->hasRole('Kasir') || $user->id === 1), 403, 'Anda tidak memiliki akses untuk mencetak Kwitansi keuangan.');

        $invoice->load(['payments', 'contact']);
        $pdf = Pdf::loadView('print.receipt', ['invoice' => $invoice])->setPaper('a5', 'landscape');
        return $pdf->stream('Kwitansi_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printGoodsReceipt(\App\Models\GoodsReceipt $goodsReceipt)
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Admin') || $user->hasRole('Gudang') || $user->id === 1), 403, 'Anda tidak memiliki akses untuk mencetak LPB ini.');

        $goodsReceipt->load(['items.part.brand', 'contact', 'warehouse', 'creator']);
        $pdf = Pdf::loadView('print.goods-receipt', ['gr' => $goodsReceipt])->setPaper('a4', 'portrait');
        return $pdf->stream('LPB_' . str_replace('/', '_', $goodsReceipt->receipt_number) . '.pdf');
    }
}
