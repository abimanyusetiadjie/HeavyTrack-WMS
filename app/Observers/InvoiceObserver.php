<?php

namespace App\Observers;

use App\Models\Invoice;
use Exception;

class InvoiceObserver
{
    public function updating(Invoice $invoice): void
    {
        $originalStatus = $invoice->getOriginal('status');
        
        // Faktur yang sudah berstatus PAID tidak boleh dimanipulasi data keuangannya
        if ($originalStatus === 'PAID') {
            $dirtyKeys = array_keys($invoice->getDirty());
            // Hanya izinkan perubahan jika semata-mata menyentuh timestamp updated_at
            if (!empty(array_diff($dirtyKeys, ['updated_at']))) {
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
