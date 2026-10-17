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
