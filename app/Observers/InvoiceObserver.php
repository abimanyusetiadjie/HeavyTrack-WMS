<?php

namespace App\Observers;

use App\Models\Invoice;
use Exception;

class InvoiceObserver
{
    public function updating(Invoice $invoice): void
    {
        // Allow updating if it's changing TO paid or updating paid_amount internally.
        // But if it was already PAID and we're trying to change something else, block it.
        
        $originalStatus = $invoice->getOriginal('status');
        
        if ($originalStatus === 'PAID') {
            // Check if only certain fields changed, or block completely
            // Usually, we want to block completely. But Filament form disabled() already prevents user changes.
            // As an extra DB-level check (for API / code), we block updates if already PAID.
            
            // Allow if just touching timestamps
            if ($invoice->isDirty() && !$invoice->isDirty('updated_at') || count($invoice->getDirty()) > 1) {
                throw new Exception('Faktur yang sudah lunas (PAID) tidak dapat diubah.');
            }
        }
    }
    
    public function deleting(Invoice $invoice): void
    {
        if ($invoice->status === 'PAID') {
            throw new Exception('Faktur yang sudah lunas tidak boleh dihapus.');
        }
    }
}
