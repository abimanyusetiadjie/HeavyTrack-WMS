# Design Specification: UI & Document Layout

### 1. Admin Panel UI Design (Filament v3 Theme)

- **Color Palette:**
  - *Primary (Heavy Equipment Industrial):* `#D97706` (Amber/Caterpillar Yellow/Industrial Gold)
  - *Slate / Background:* `#0F172A` (Dark mode) / `#F8FAFC` (Light mode)
  - *Danger / Warning:* `#DC2626` (Red) / `#EA580C` (Orange)
  - *Success:* `#16A34A` (Green)
- **Part Number Visibility:**
  - Font tabel untuk `part_number` wajib menggunakan tipe monospace (`font-mono font-bold tracking-wider`) agar kode seperti `0` (nol) dan `O` (huruf O) terlihat kontras dan jelas bagi staf gudang.
- **Tabel Suku Cadang (Zero Images):**
  - Kolom: `Part Number` | `Brand` | `Kategori` | `Nama Part` | `Lokasi Rak` | `Stok Fisik` | `Harga Jual` | `Aksi`.
  - Filter: Quick dropdown berdasarkan Brand (Komatsu, CAT, Hitachi, Fleetguard, dll) dan Lokasi Rak.

---

### 2. Print Layout Specifications (HTML/CSS Blade)

#### A. Format Surat Jalan (Delivery Order)
- **Ukuran Kertas:** Continuous Form 9.5 x 11 inch (bagi 2: $215\text{ mm} \times 139\text{ mm}$) atau A4 Portrait.
- **Karakter Dokumen:** Tidak menggunakan warna/bayangan tebal (menghemat pita dot matrix), garis tepi menggunakan `border: 1px solid #000;`.
- **Elemen Wajib:**
  1. Header: Logo/Nama Perusahaan, Nomor Surat Jalan, Tanggal, Tujuan Customer.
  2. Tabel Barang: No, Part Number, Deskripsi Barang, Brand, Qty, Satuan, Cek Fisik Gudang (Checkbox).
  3. Kolom Tanda Tangan: Diterima Oleh (Customer), Sopir/Ekspedisi, Petugas Gudang, Hormat Kami.

#### B. Format Faktur Penjualan (Commercial Invoice)
- **Ukuran Kertas:** A4 Portrait.
- **Elemen Wajib:**
  1. Header & Identitas Pajak: No Faktur, No Surat Jalan Referensi, Tanggal Jatuh Tempo, NPWP.
  2. Tabel Nilai: No, Part Number, Nama Barang, Qty, Harga Satuan, Diskon %, Subtotal.
  3. Footer Finansial: Subtotal, Potongan, PPN 11%, Grand Total (Angka Terbilang Indonesia).
  4. Rekening Bank Transfer & Stempel/Tanda Tangan Authorized Officer.

#### C. Format Bukti Pembayaran / Kwitansi
- **Ukuran Kertas:** A5 Landscape ($210\text{ mm} \times 148\text{ mm}$).
- **Elemen Wajib:**
  1. Nomor Bukti Kas Masuk (BKM).
  2. Terbilang huruf nominal uang.
  3. Peruntukan pembayaran (Pelunasan Faktur No...).