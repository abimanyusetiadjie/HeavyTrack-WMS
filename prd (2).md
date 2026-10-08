# Product Requirements Document (PRD)
## Heavy Equipment Spare Parts Warehouse & Document Management System

### 1. Executive Summary
Sistem Manajemen Gudang (WMS) dan Cetak Dokumen Legal khusus distributor dan toko suku cadang alat berat (Komatsu, CAT, Hitachi, Kobelco, LiuGong, SANY, Fleetguard, Parker Racor). Sistem ini berfokus pada kecepatan transaksi stok, akurasi pencarian *part number*, pencegahan stok negatif dengan *concurrency locking*, dan pencetakan dokumen legal siap pakai (Surat Jalan, Faktur Penjualan, Bukti Kas/Kwitansi).

---

### 2. User Personas & Roles
1. **Warehouse Admin / Staff Gudang:**
   - Penerimaan barang masuk (Goods Receipt).
   - Penyiapan barang keluar dan packing berdasarkan Surat Jalan.
   - Pengecekan lokasi rak (*bin location*) dan stok fisik.
2. **Sales / Billing Officer:**
   - Pembuatan pesanan penjualan (Sales Order).
   - Penerbitan Surat Jalan dan Faktur Penjualan (Invoicing).
   - Input tanda terima pembayaran / kas masuk.
3. **Supervisor / Owner:**
   - Memonitor pergerakan kartu stok (*stock ledger*).
   - Otorisasi pembatalan dokumen (*void invoice/SJ*).
   - Laporan laba kotor, piutang (*aging AR*), dan rekapitulasi mutasi.

---

### 3. Key Functional Requirements

#### 3.1 Master Data Suku Cadang (Spare Parts Specific)
- **Part Number Unik:** Primary indexing pada `part_number` (mendukung karakter alfanumerik dan simbol strip/titik, contoh: `20Y-04-J1130`, `VH23390-E0020T1`).
- **Cross-Reference Number:** Mekanisme relasi ekuivalensi (misal Racor 2020PM ekuivalen dengan FS1280).
- **Brand & Machine Compatibility:** Merek filter (Fleetguard, Parker) dan unit cocokannya (CAT 320D, Komatsu PC200-8, dll).
- **Lokasi Rak (*Bin Location*):** Format Kode Rak (contoh: `A-01-03` -> Lorong A, Rak 01, Tingkat 03).
- **Multi-Satuan & Konversi:** Pcs, Box, Pack (1 Box = 12 Pcs).

#### 3.2 Manajemen Stok & Mutasi (ACID & No Negative Stock)
- **Kartu Stok Otomatis:** Setiap mutasi (masuk, keluar, penyesuaian, opname) mencatat snapshot saldo stok sebelumnya dan saldo akhir.
- **Pessimistic Locking:** Penguncian stok saat checkout/pembuatan surat jalan untuk mencegah *double-selling* pada jam sibuk.
- **Buffer Stock & Reorder Alert:** Penanda otomatis jika stok berada di bawah batas minimum.

#### 3.3 Alur Dokumen Transaksi
1. **Purchase / Penerimaan:**
   - Supplier -> Purchase Order (PO) -> Surat Jalan Masuk / Goods Receipt -> Stok Masuk.
2. **Penjualan & Distribusi:**
   - Sales Order (SO) -> Validasi Stok -> Cetak Surat Jalan (Stok Keluar) -> Cetak Faktur Penjualan (Tagihan/AR).
3. **Pembayaran:**
   - Pelunasan penuh atau bertahap (termin) -> Cetak Bukti Pembayaran / Kwitansi Resmi.

#### 3.4 Cetak Dokumen Legal
- Mendukung dua format output:
  1. **A4 / F4 (PDF):** Dokumen formal laser jet.
  2. **Continuous Form / Dot Matrix (9.5 x 11 inch):** Cetak rangkap 3 (Asli, Gudang, Akunting) dengan margin dan format ringkas.

---

### 4. Non-Functional Requirements
- **Latensi Pencarian Part:** $\le 300\text{ ms}$ untuk dataset $> 50.000$ SKU.
- **Generasi PDF Dokumen:** $\le 1.5\text{ detik}$ per dokumen tunggal.
- **Server Footprint:** Berjalan stabil pada VPS spesifikasi minimal 1 vCPU / 2GB RAM.
- **Auditability:** Tidak ada *hard delete* pada dokumen legal; hanya pembatalan (*void*) bertingkat dengan pencatatan user dan alasan.