# Database Schema Design (PostgreSQL / MySQL 8.0+)
## Optimized for Heavy Equipment Spare Parts WMS

### 1. Entity Relationship Diagram (ERD Context)
```
[brands] <----+
              |--- [parts] <----+
[categories] <+       |         |--- [stock_ledgers]
                      |         |--- [stock_balances]
                      |         |--- [delivery_order_items]
                      |         |--- [invoice_items]
                      +--- [part_cross_references]

[customers] <--- [sales_orders] <--- [delivery_orders] <--- [invoices] <--- [payments]
```

---

### 2. DDL Table Definitions (SQL)

```sql
-- 1. Master Brands (Hitachi, Komatsu, CAT, LiuGong, Parker, Fleetguard, dll)
CREATE TABLE brands (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(30) UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Master Categories (Fuel Filter, Oil Filter, Water Separator, Element Assy)
CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(50) UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. Master Parts / Spare Parts
CREATE TABLE parts (
    id BIGSERIAL PRIMARY KEY,
    brand_id BIGINT NOT NULL REFERENCES brands(id) ON DELETE RESTRICT,
    category_id BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    part_number VARCHAR(100) NOT NULL,
    clean_part_number VARCHAR(100) NOT NULL, -- Di-strip dari spasi dan strip untuk regex search cepat
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(20) DEFAULT 'PCS',
    bin_location VARCHAR(50), -- Contoh: A-01-02
    min_stock_level INT DEFAULT 5,
    cost_price DECIMAL(15,2) DEFAULT 0.00,
    selling_price DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_parts_brand_part_number UNIQUE (brand_id, clean_part_number)
);

CREATE INDEX idx_parts_clean_pn ON parts(clean_part_number);
CREATE INDEX idx_parts_bin ON parts(bin_location);

-- 4. Part Cross References (Substitusi Suku Cadang)
CREATE TABLE part_cross_references (
    id BIGSERIAL PRIMARY KEY,
    source_part_id BIGINT NOT NULL REFERENCES parts(id) ON DELETE CASCADE,
    target_part_number VARCHAR(100) NOT NULL,
    target_brand_name VARCHAR(100),
    notes VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cross_ref_target ON part_cross_references(target_part_number);

-- 5. Multi-Warehouse / Location (Gudang Utama, Gudang Transit)
CREATE TABLE warehouses (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. Current Stock Balances (Fast Lookup & Locking)
CREATE TABLE stock_balances (
    id BIGSERIAL PRIMARY KEY,
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id) ON DELETE RESTRICT,
    part_id BIGINT NOT NULL REFERENCES parts(id) ON DELETE RESTRICT,
    qty_on_hand INT NOT NULL DEFAULT 0,
    qty_reserved INT NOT NULL DEFAULT 0, -- Dialokasikan untuk Sales Order yang belum dikirim
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_warehouse_part UNIQUE (warehouse_id, part_id),
    CONSTRAINT chk_qty_on_hand_positive CHECK (qty_on_hand >= 0),
    CONSTRAINT chk_qty_reserved_positive CHECK (qty_reserved >= 0)
);

-- 7. Stock Ledgers (Audit Trail / Kartu Stok)
CREATE TABLE stock_ledgers (
    id BIGSERIAL PRIMARY KEY,
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id),
    part_id BIGINT NOT NULL REFERENCES parts(id),
    reference_type VARCHAR(50) NOT NULL, -- 'DELIVERY_ORDER', 'GOODS_RECEIPT', 'ADJUSTMENT'
    reference_id BIGINT NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    qty_change INT NOT NULL, -- Negatif untuk keluar, positif untuk masuk
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    notes TEXT,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_stock_ledger_part_wh ON stock_ledgers(warehouse_id, part_id, created_at DESC);

-- 8. Customers & Suppliers
CREATE TABLE contacts (
    id BIGSERIAL PRIMARY KEY,
    type VARCHAR(20) NOT NULL CHECK (type IN ('CUSTOMER', 'SUPPLIER', 'BOTH')),
    company_name VARCHAR(255) NOT NULL,
    pic_name VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    tax_number_npwp VARCHAR(50),
    term_of_payment_days INT DEFAULT 0, -- 0 = COD/Cash
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 9. Sales Orders
CREATE TABLE sales_orders (
    id BIGSERIAL PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    contact_id BIGINT NOT NULL REFERENCES contacts(id),
    order_date DATE NOT NULL,
    status VARCHAR(30) DEFAULT 'DRAFT' CHECK (status IN ('DRAFT', 'CONFIRMED', 'DELIVERED', 'CANCELLED')),
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 10. Surat Jalan / Delivery Orders
CREATE TABLE delivery_orders (
    id BIGSERIAL PRIMARY KEY,
    do_number VARCHAR(50) NOT NULL UNIQUE, -- SJ-YYMM-XXXX
    sales_order_id BIGINT REFERENCES sales_orders(id),
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id),
    contact_id BIGINT NOT NULL REFERENCES contacts(id),
    delivery_date DATE NOT NULL,
    driver_name VARCHAR(100),
    vehicle_plate_number VARCHAR(30),
    status VARCHAR(30) DEFAULT 'ISSUED' CHECK (status IN ('ISSUED', 'RECEIVED', 'VOID')),
    notes TEXT,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE delivery_order_items (
    id BIGSERIAL PRIMARY KEY,
    delivery_order_id BIGINT NOT NULL REFERENCES delivery_orders(id) ON DELETE CASCADE,
    part_id BIGINT NOT NULL REFERENCES parts(id),
    qty INT NOT NULL CHECK (qty > 0),
    unit VARCHAR(20) NOT NULL,
    bin_location_snapshot VARCHAR(50),
    notes VARCHAR(255)
);

-- 11. Faktur Penjualan / Invoices
CREATE TABLE invoices (
    id BIGSERIAL PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE, -- INV-YYMM-XXXX
    delivery_order_id BIGINT REFERENCES delivery_orders(id),
    contact_id BIGINT NOT NULL REFERENCES contacts(id),
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_percent DECIMAL(5,2) DEFAULT 11.00, -- PPN 11% / 12%
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(15,2) DEFAULT 0.00,
    status VARCHAR(30) DEFAULT 'UNPAID' CHECK (status IN ('UNPAID', 'PARTIALLY_PAID', 'PAID', 'VOID')),
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoice_items (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    part_id BIGINT NOT NULL REFERENCES parts(id),
    part_number_snapshot VARCHAR(100) NOT NULL,
    part_name_snapshot VARCHAR(255) NOT NULL,
    qty INT NOT NULL CHECK (qty > 0),
    unit VARCHAR(20) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    total_price DECIMAL(15,2) NOT NULL
);

-- 12. Payments / Bukti Penerimaan Kas
CREATE TABLE payments (
    id BIGSERIAL PRIMARY KEY,
    receipt_number VARCHAR(50) NOT NULL UNIQUE, -- BKM-YYMM-XXXX (Bukti Kas Masuk)
    invoice_id BIGINT NOT NULL REFERENCES invoices(id),
    payment_date DATE NOT NULL,
    payment_method VARCHAR(30) NOT NULL CHECK (payment_method IN ('CASH', 'BANK_TRANSFER', 'GIRO')),
    bank_name VARCHAR(100),
    reference_number VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL CHECK (amount > 0),
    notes TEXT,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```