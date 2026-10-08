# Development & Business Rules (AntiGravity Configuration)

### 1. Data Integrity & Stock Operations Rules
1. **NO NEGATIVE STOCK:** Nilai kolom `qty_on_hand` di tabel `stock_balances` tidak boleh kurang dari 0 (`>= 0`). Transaksi pengeluaran barang wajib digagalkan jika saldo tidak mencukupi.
2. **STRICT CONCURRENCY LOCKING:** Setiap penambahan atau pengurangan inventaris wajib dibungkus dalam blok `DB::transaction()` dengan pessimistic locking `lockForUpdate()`.
3. **IMMUTABLE STOCK LEDGER:** Baris di tabel `stock_ledgers` bersifat append-only. Dilarang melakukan operasi `UPDATE` atau `DELETE` pada tabel kartu stok. Perbaikan salah input wajib dilakukan via dokumen *Stock Adjustment* baru.
4. **NO HARD DELETE ON LEGAL DOCUMENTS:** Dokumen `delivery_orders`, `invoices`, dan `payments` tidak boleh dihapus (`DELETE`). Status hanya boleh diubah menjadi `VOID` dengan menyertakan relasi user pengubah dan alasan tertulis.

---

### 2. Spare Parts Search & Naming Rules
1. **CLEAN PART NUMBER NORMALIZATION:** Saat membuat atau mengubah record suku cadang, sistem wajib membersihkan spasi, tanda strip, dan titik ke dalam kolom `clean_part_number` (contoh: `20Y-04-J1130` disimpan juga sebagai `20Y04J1130`).
2. **INSENSITIVE QUERYING:** Pencarian barang di antarmuka kasir dan gudang wajib mencari ke dua kolom (`part_number` asli dan `clean_part_number`) untuk toleransi kesalahan ketik oleh mekanik/stokis.

---

### 3. Print Engine Rules
1. **NO EXTERNAL HEADLESS BROWSERS FOR ROUTINE PRINTS:** Gunakan `barryvdh/laravel-dompdf` agar tidak membebani RAM server dengan instance Puppeteer/Chromium.
2. **STRICT CSS DOMPDF COMPATIBILITY:**
   - Gunakan CSS 2.1 murni (hindari Flexbox dan Grid CSS pada view PDF; gunakan `table` HTML klasik dengan lebar persentase tetap untuk tata letak cetak).
   - Seluruh asset gambar logo wajib diubah ke representasi base64 atau absolute filesystem path lokal (`public_path('images/logo.png')`).

---

### 4. Code Standards & Architecture Guidelines
1. **Filament v3 Conventions:**
   - Logika query berat tidak boleh diletakkan di dalam hook render tabel; gunakan query scope Eloquent terindeks.
   - Paginasi tabel dibatasi default 25 item untuk menjaga konsumsi memori browser workstation staf.
2. **Money Values:** Semua nilai mata uang wajib disimpan dalam bentuk integer/desimal 2 digit (`DECIMAL(15,2)`), dan tidak boleh menggunakan tipe `FLOAT` untuk menghindari kesalahan pembulatan sen.