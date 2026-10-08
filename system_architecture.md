# System Architecture Document
## Laravel 11 + Filament v3 Warehouse Stack

### 1. Technology Selection & Topology

```
+-------------------------------------------------------------+
| Browser Client (Warehouse Workstation / Tablet / Mobile)   |
+-------------------------------------------------------------+
                             |
                             | HTTP/HTTPS (Livewire AJAX / Alpine.js)
                             v
+-------------------------------------------------------------+
| Reverse Proxy (Nginx) & Application Server (PHP 8.3 FPM)    |
| - Laravel 11 Kernel                                         |
| - Filament v3 Admin Panel (Tailwind CSS compiled)           |
| - Barryvdh/Laravel-Dompdf (Lightweight Server PDF Engine)   |
+-------------------------------------------------------------+
               |                               |
               | Eloquent (ACID Transactions)  | Queue Jobs (Export/PDF)
               v                               v
+------------------------------+  +---------------------------+
| PostgreSQL / MySQL 8.0       |  | Redis 7 (Cache & Queue)   |
| (Row-level Locking)          |  +---------------------------+
+------------------------------+
```

---

### 2. Layering Architecture

#### A. Presentation Layer (Lightweight Frontend)
- **Filament v3 Admin Panel:** Menggunakan Livewire v3 dan Alpine.js. 
- **Keunggulan Beban Server Rendah:** 
  - Tidak ada virtual DOM berat seperti SPA Next.js/React.
  - Resource statis (CSS/JS) di-cache penuh oleh browser.
  - Komponen tabel Filament hanya memuat data paginasi (10–25 baris per halaman), menjaga transfer data jaringan sangat hemat.

#### B. Business Logic & Concurrency Control
- **Pessimistic Locking Flow (`SELECT ... FOR UPDATE`):**
  Saat Surat Jalan divalidasi, sistem menjalankan `DB::transaction()`:
  ```php
  DB::transaction(function () use ($items, $warehouseId, $deliveryOrder) {
      foreach ($items as $item) {
          $stock = StockBalance::where('warehouse_id', $warehouseId)
              ->where('part_id', $item->part_id)
              ->lockForUpdate()
              ->firstOrFail();

          if ($stock->qty_on_hand < $item->qty) {
              throw new InsufficientStockException("Stok tidak cukup untuk part: " . $item->part->part_number);
          }

          $before = $stock->qty_on_hand;
          $stock->qty_on_hand -= $item->qty;
          $stock->save();

          StockLedger::create([
              'warehouse_id' => $warehouseId,
              'part_id' => $item->part_id,
              'reference_type' => 'DELIVERY_ORDER',
              'reference_id' => $deliveryOrder->id,
              'reference_number' => $deliveryOrder->do_number,
              'qty_change' => -$item->qty,
              'balance_before' => $before,
              'balance_after' => $stock->qty_on_hand,
              'created_by' => auth()->id(),
          ]);
      }
  });
  ```

#### C. Document Generation Pipeline
- **Engine:** `barryvdh/laravel-dompdf` (C++ extensionless, murni PHP, memory footprint $\le 30\text{ MB}$ per request PDF).
- **Template Source:** Blade templates yang terisolasi khusus dokumen print (`resources/views/print/faktur.blade.php`, `surat-jalan.blade.php`).
- **Direct Thermal / Continuous Form Support:** CSS `@page` diatur dengan ukuran tetap (`size: 210mm 140mm;` untuk continuous form setengah A4).