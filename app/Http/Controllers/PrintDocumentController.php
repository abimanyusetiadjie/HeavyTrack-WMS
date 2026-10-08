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
        $deliveryOrder->load(['items.part.brand', 'contact', 'warehouse', 'creator']);
        
        $pdf = Pdf::loadView('print.delivery-order', ['do' => $deliveryOrder])
            ->setPaper([0, 0, 609.45, 396.85], 'portrait'); // 215mm x 140mm in points
            
        return $pdf->stream('Surat_Jalan_' . str_replace('/', '_', $deliveryOrder->do_number) . '.pdf');
    }

    public function printInvoice(Invoice $invoice)
    {
        $invoice->load(['items', 'contact', 'creator', 'deliveryOrder']);
        $pdf = Pdf::loadView('print.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $pdf->stream('Faktur_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printReceipt(Invoice $invoice)
    {
        $invoice->load(['payments', 'contact']);
        $pdf = Pdf::loadView('print.receipt', ['invoice' => $invoice])->setPaper('a5', 'landscape');
        return $pdf->stream('Kwitansi_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }
}
