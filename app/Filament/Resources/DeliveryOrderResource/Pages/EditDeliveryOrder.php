<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printInvoice')
                ->label(fn (): string => $this->record->invoice ? 'Cetak Faktur' : 'Terbitkan & Cetak Faktur')
                ->icon('heroicon-o-document-currency-dollar')
                ->color(fn (): string => $this->record->invoice ? 'success' : 'warning')
                ->action(function () {
                    $record = $this->record;
                    $record->loadMissing(['items.part' => fn ($q) => $q->withTrashed(), 'contact', 'invoice']);
                    
                    if ($record->items->isEmpty()) {
                        Notification::make()
                            ->title('Surat Jalan Belum Memiliki Barang')
                            ->body("Surat Jalan {$record->do_number} masih kosong (0 item). Harap isi barang pada tabel di bawah terlebih dahulu.")
                            ->warning()
                            ->persistent()
                            ->send();
                        return;
                    }

                    if ($record->invoice) {
                        $this->js("window.open('" . route('print.invoice', ['invoice' => $record->invoice->id]) . "', '_blank');");
                        return;
                    }

                    $invoice = DB::transaction(function () use ($record) {
                        $existing = Invoice::where('delivery_order_id', $record->id)->latest()->first();
                        if ($existing) {
                            return $existing;
                        }

                        $prefix = 'INV/' . date('Ym') . '/';
                        $latest = Invoice::withTrashed()
                            ->where('invoice_number', 'like', $prefix . '%')
                            ->orderByDesc('invoice_number')
                            ->value('invoice_number');

                        $nextSeq = 1;
                        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                            $nextSeq = (int) $matches[1] + 1;
                        } else {
                            $nextSeq = Invoice::withTrashed()
                                ->whereMonth('created_at', date('m'))
                                ->whereYear('created_at', date('Y'))
                                ->count() + 1;
                        }

                        $invoiceNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

                        $subtotal = 0;
                        $itemsData = [];
                        foreach ($record->items as $item) {
                            $price = round((float) ($item->part?->selling_price ?? 0));
                            $lineTotal = round($item->qty * $price);
                            $subtotal += $lineTotal;

                            $itemsData[] = [
                                'part_id' => $item->part_id,
                                'part_number_snapshot' => mb_strtoupper($item->part?->part_number ?? '-', 'UTF-8'),
                                'part_name_snapshot' => mb_strtoupper($item->part?->name ?? 'Sparepart', 'UTF-8'),
                                'qty' => $item->qty,
                                'unit' => mb_strtoupper($item->unit ?: ($item->part?->unit ?? 'PCS'), 'UTF-8'),
                                'unit_price' => $price,
                                'discount_percent' => 0,
                                'total_price' => $lineTotal,
                            ];
                        }

                        $createdInvoice = Invoice::create([
                            'invoice_number' => $invoiceNumber,
                            'delivery_order_id' => $record->id,
                            'contact_id' => $record->contact_id,
                            'invoice_date' => now(),
                            'due_date' => now()->addDays(30),
                            'status' => 'UNPAID',
                            'subtotal' => $subtotal,
                            'discount_amount' => 0,
                            'tax_percent' => 0,
                            'tax_amount' => 0,
                            'grand_total' => $subtotal,
                            'paid_amount' => 0,
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        foreach ($itemsData as $data) {
                            $data['invoice_id'] = $createdInvoice->id;
                            InvoiceItem::create($data);
                        }

                        return $createdInvoice;
                    });

                    Notification::make()
                        ->title('Faktur Berhasil Diterbitkan')
                        ->body("Faktur resmi {$invoice->invoice_number} berhasil dibuat. Dokumen faktur sedang dibuka...")
                        ->success()
                        ->send();

                    $this->js("window.open('" . route('print.invoice', ['invoice' => $invoice->id]) . "', '_blank');");
                }),
            Actions\Action::make('print')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('print.delivery-order', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $newStatus = $this->data['status'] ?? $this->record->status;
        $oldStatus = $this->record->status;
        $triggerStatuses = ['ISSUED', 'CONFIRMED', 'SHIPPED', 'RECEIVED'];

        // Jika mengaktifkan kembali surat jalan dari status VOID/DRAFT -> pastikan stok tersedia
        if (!in_array($oldStatus, $triggerStatuses, true) && in_array($newStatus, $triggerStatuses, true)) {
            $warehouseId = $this->data['warehouse_id'] ?? $this->record->warehouse_id;
            $items = $this->data['items'] ?? $this->record->items->toArray();

            $qtyByPart = [];
            foreach ($items as $item) {
                $partId = $item['part_id'] ?? null;
                $qty = (int) ($item['qty'] ?? 0);
                if ($partId && $qty > 0) {
                    $qtyByPart[$partId] = ($qtyByPart[$partId] ?? 0) + $qty;
                }
            }

            foreach ($qtyByPart as $partId => $totalNeeded) {
                $stock = \App\Models\StockBalance::where('warehouse_id', $warehouseId)
                    ->where('part_id', $partId)
                    ->first();

                $available = $stock ? $stock->qty_on_hand : 0;
                if ($available < $totalNeeded) {
                    $part = \App\Models\Part::find($partId);
                    $partName = $part ? "{$part->part_number} ({$part->name})" : "Part ID #{$partId}";

                    Notification::make()
                        ->title('Stok Gudang Tidak Mencukupi')
                        ->body("Tidak dapat mengaktifkan Surat Jalan: stok untuk {$partName} di gudang hanya tersisa {$available}, dibutuhkan {$totalNeeded}.")
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        }
    }
}
