<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintDocumentController extends Controller
{
    public function printDeliveryOrder(DeliveryOrder $deliveryOrder)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isOwner() || $user->isAdmin()), 403, 'Anda tidak memiliki akses untuk mencetak Surat Jalan ini.');

        $deliveryOrder->load(['items.part.brand', 'contact', 'warehouse', 'creator']);
        
        $pdf = Pdf::loadView('print.delivery-order', ['do' => $deliveryOrder])
            ->setPaper([0, 0, 609.45, 396.85], 'portrait'); // 215mm x 140mm in points
            
        return $pdf->stream('Surat_Jalan_' . str_replace('/', '_', $deliveryOrder->do_number) . '.pdf');
    }

    public function printInvoiceFromDeliveryOrder(DeliveryOrder $deliveryOrder)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isOwner() || $user->isAdmin()), 403, 'Anda tidak memiliki akses untuk mencetak Faktur.');

        // Transaksionalitas & Pencegahan Race Condition:
        // Gunakan DB transaction + row-level lock agar tidak terjadi faktur duplikat jika 2 kasir klik bersamaan
        $invoice = DB::transaction(function () use ($deliveryOrder, $user) {
            $existing = Invoice::where('delivery_order_id', $deliveryOrder->id)
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }

            $deliveryOrder->load(['items.part' => fn ($q) => $q->withTrashed(), 'contact']);
            abort_if($deliveryOrder->items->isEmpty(), 400, 'Surat Jalan belum memiliki rincian barang. Silakan isi barang pada Surat Jalan terlebih dahulu.');

            $prefix = 'INV/' . date('Ym') . '/';
            $latest = Invoice::withTrashed()
                ->where('invoice_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $nextSeq = 1;
            if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                $nextSeq = (int) $matches[1] + 1;
            } else {
                $nextSeq = Invoice::withTrashed()
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->lockForUpdate()
                    ->count() + 1;
            }

            $invoiceNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

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

            $createdInvoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'delivery_order_id' => $deliveryOrder->id,
                'contact_id' => $deliveryOrder->contact_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'UNPAID',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'grand_total' => $subtotal,
                'paid_amount' => 0,
                'created_by' => $user->id ?? 1,
            ]);

            foreach ($itemsData as $data) {
                $data['invoice_id'] = $createdInvoice->id;
                \App\Models\InvoiceItem::create($data);
            }

            return $createdInvoice;
        });

        $invoice->load(['items', 'contact', 'creator', 'deliveryOrder']);
        $pdf = Pdf::loadView('print.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $pdf->stream('Faktur_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printInvoice(Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isOwner() || $user->isAdmin()), 403, 'Anda tidak memiliki akses untuk mencetak Faktur keuangan.');

        $invoice->load(['items', 'contact', 'creator', 'deliveryOrder']);
        $pdf = Pdf::loadView('print.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $pdf->stream('Faktur_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printReceipt(Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isOwner() || $user->isAdmin()), 403, 'Anda tidak memiliki akses untuk mencetak Kwitansi keuangan.');

        $invoice->load(['payments', 'contact']);
        $pdf = Pdf::loadView('print.receipt', ['invoice' => $invoice])->setPaper('a5', 'landscape');
        return $pdf->stream('Kwitansi_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printGoodsReceipt(\App\Models\GoodsReceipt $goodsReceipt)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isOwner() || $user->isAdmin()), 403, 'Anda tidak memiliki akses untuk mencetak LPB ini.');

        $goodsReceipt->load(['items.part.brand', 'contact', 'warehouse', 'creator']);
        $pdf = Pdf::loadView('print.goods-receipt', ['gr' => $goodsReceipt])->setPaper('a4', 'portrait');
        return $pdf->stream('LPB_' . str_replace('/', '_', $goodsReceipt->receipt_number) . '.pdf');
    }
}
