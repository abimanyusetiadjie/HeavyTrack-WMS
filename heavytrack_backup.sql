-- ============================================================================
-- HeavyTrack WMS (Mitra Abadi Nusantara) - Full Production Database Backup
-- Source Host : caboose.proxy.rlwy.net:51244 (railway)
-- Generated At: 2026-10-17 17:12:40
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `brands` (10 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_name_unique` (`name`),
  UNIQUE KEY `brands_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brands` (`id`, `name`, `code`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Hitachi', 'HIT', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('2', 'Komatsu', 'KOM', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('3', 'CAT', 'CAT', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('4', 'LiuGong', 'LIU', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('5', 'Parker', 'PAR', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('6', 'Fleetguard', 'FLE', '1', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('7', 'Kobelco', 'KOBEL', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('8', 'XCMG', 'XCMG', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('9', 'Parker Racor', 'PARKE', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('10', 'SANY', 'SANY', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `cache` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:0:{}s:11:\"permissions\";a:0:{}s:5:\"roles\";a:0:{}}', '1792328748');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `cache_locks` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `categories` (7 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Fuel Filter', 'FUEL_FILTER', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('2', 'Oil Filter', 'OIL_FILTER', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('3', 'Water Separator', 'WATER_SEPARATOR', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('4', 'Element Assy', 'ELEMENT_ASSY', '2026-10-08 18:09:35', '2026-10-08 18:09:35', NULL),
('5', 'Hydraulic Filter', 'HYDRA', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('6', 'Heavy Equipment Filter', 'HEAVY', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('7', 'Air Filter', 'AIRFI', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `contacts` (3 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('CUSTOMER','SUPPLIER','BOTH') COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `tax_number_npwp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term_of_payment_days` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contacts` (`id`, `type`, `company_name`, `pic_name`, `phone`, `email`, `address`, `tax_number_npwp`, `term_of_payment_days`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'CUSTOMER', 'syifa', 'syifaaa ', '123121', 'qsdaa@gmail', 'bintaro ', '21311', '-1', '2026-10-15 14:20:44', '2026-10-15 14:20:44', NULL),
('2', 'SUPPLIER', 'PT United Tractors Tbk', '231', '123', NULL, 'gaga', NULL, '0', '2026-10-15 15:05:13', '2026-10-15 15:05:13', NULL),
('3', 'CUSTOMER', 'tina', 'bdg', '12313', NULL, 'junam ', NULL, '0', '2026-10-15 15:11:53', '2026-10-15 15:11:53', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `delivery_order_items` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `delivery_order_items`;
CREATE TABLE `delivery_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `qty` int NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bin_location_snapshot` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_order_items_delivery_order_id_foreign` (`delivery_order_id`),
  KEY `delivery_order_items_part_id_foreign` (`part_id`),
  CONSTRAINT `delivery_order_items_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_order_items_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `delivery_orders` (5 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `delivery_orders`;
CREATE TABLE `delivery_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `do_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `delivery_date` date NOT NULL,
  `driver_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_plate_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','ISSUED','CONFIRMED','SHIPPED','RECEIVED','VOID') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ISSUED',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_orders_do_number_unique` (`do_number`),
  KEY `delivery_orders_sales_order_id_foreign` (`sales_order_id`),
  KEY `delivery_orders_warehouse_id_foreign` (`warehouse_id`),
  KEY `delivery_orders_contact_id_foreign` (`contact_id`),
  KEY `delivery_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `delivery_orders_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  CONSTRAINT `delivery_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `delivery_orders_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`),
  CONSTRAINT `delivery_orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `delivery_orders` (`id`, `do_number`, `sales_order_id`, `warehouse_id`, `contact_id`, `delivery_date`, `driver_name`, `vehicle_plate_number`, `status`, `notes`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'SJ/202610/0001', NULL, '1', '1', '2026-10-15', 'anto', NULL, 'SHIPPED', NULL, '1', '2026-10-15 15:07:00', '2026-10-15 15:09:25', NULL),
('2', 'SJ/202610/0002', NULL, '1', '1', '2026-10-15', 'anto', '12312', 'CONFIRMED', NULL, '1', '2026-10-15 15:07:41', '2026-10-15 15:07:41', NULL),
('3', 'SJ/202610/002', NULL, '1', '1', '2026-10-15', 'anto', '12312', 'ISSUED', NULL, '1', '2026-10-15 15:08:08', '2026-10-15 15:08:08', NULL),
('4', 'SJ/202610/0004', NULL, '1', '3', '2026-10-15', 'anto', '1231', 'SHIPPED', NULL, '1', '2026-10-15 15:13:16', '2026-10-15 15:13:16', NULL),
('5', 'SJ/202610/0005', NULL, '1', '3', '2026-10-19', 'anto', '1231', 'SHIPPED', NULL, '1', '2026-10-15 15:13:49', '2026-10-15 15:13:49', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `failed_jobs` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `goods_receipt_items` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `goods_receipt_items`;
CREATE TABLE `goods_receipt_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `qty_received` int NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `bin_location_snapshot` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_items_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `goods_receipt_items_part_id_foreign` (`part_id`),
  CONSTRAINT `goods_receipt_items_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_receipt_items_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goods_receipt_items` (`id`, `goods_receipt_id`, `part_id`, `qty_received`, `unit`, `unit_cost`, `bin_location_snapshot`, `notes`) VALUES
('1', '1', '1', '1', 'PCS', '0.00', NULL, NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `goods_receipts` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `goods_receipts`;
CREATE TABLE `goods_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_do_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `status` enum('DRAFT','RECEIVED','VOID') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RECEIVED',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_receipts_receipt_number_unique` (`receipt_number`),
  KEY `goods_receipts_warehouse_id_foreign` (`warehouse_id`),
  KEY `goods_receipts_contact_id_foreign` (`contact_id`),
  KEY `goods_receipts_created_by_foreign` (`created_by`),
  CONSTRAINT `goods_receipts_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  CONSTRAINT `goods_receipts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `goods_receipts_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goods_receipts` (`id`, `receipt_number`, `supplier_do_number`, `po_reference_number`, `receipt_date`, `warehouse_id`, `contact_id`, `status`, `notes`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'LPB/202610/0001', NULL, NULL, '2026-10-15', '1', '2', 'RECEIVED', NULL, '1', '2026-10-15 15:05:35', '2026-10-15 15:05:35', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `invoice_items` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `part_number_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_part_id_foreign` (`part_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `invoices` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_order_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '11.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','UNPAID','PARTIALLY_PAID','PAID','VOID') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNPAID',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_delivery_order_id_foreign` (`delivery_order_id`),
  KEY `invoices_contact_id_foreign` (`contact_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  CONSTRAINT `invoices_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invoices_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `job_batches` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `jobs` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `migrations` (20 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '0001_01_01_000000_create_users_table', '1'),
('2', '0001_01_01_000001_create_cache_table', '1'),
('3', '0001_01_01_000002_create_jobs_table', '1'),
('4', '2026_10_08_144720_create_brands_table', '1'),
('5', '2026_10_08_144720_create_categories_table', '1'),
('6', '2026_10_08_144720_create_parts_table', '1'),
('7', '2026_10_08_144721_create_part_cross_references_table', '1'),
('8', '2026_10_08_144721_create_warehouses_table', '1'),
('9', '2026_10_08_144722_create_stock_balances_table', '1'),
('10', '2026_10_08_144723_create_contacts_table', '1'),
('11', '2026_10_08_144723_create_sales_orders_table', '1'),
('12', '2026_10_08_144723_create_stock_ledgers_table', '1'),
('13', '2026_10_08_144724_create_delivery_orders_table', '1'),
('14', '2026_10_08_144724_create_invoices_table', '1'),
('15', '2026_10_08_144725_create_delivery_order_items_table', '1'),
('16', '2026_10_08_144725_create_invoice_items_table', '1'),
('17', '2026_10_08_144726_create_payments_table', '1'),
('18', '2026_10_08_145047_create_permission_tables', '1'),
('19', '2026_10_15_120000_create_goods_receipts_table', '2'),
('20', '2026_10_17_192500_add_draft_to_invoices_status_enum', '3');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `model_has_permissions` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `model_has_roles` (3 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
('1', 'App\\Models\\User', '1'),
('2', 'App\\Models\\User', '2'),
('3', 'App\\Models\\User', '3');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `part_cross_references` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `part_cross_references`;
CREATE TABLE `part_cross_references` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_part_id` bigint unsigned NOT NULL,
  `target_part_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_brand_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `part_cross_references_source_part_id_foreign` (`source_part_id`),
  KEY `part_cross_references_target_part_number_index` (`target_part_number`),
  CONSTRAINT `part_cross_references_source_part_id_foreign` FOREIGN KEY (`source_part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `parts` (19 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `parts`;
CREATE TABLE `parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `part_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clean_part_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `bin_location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_stock_level` int NOT NULL DEFAULT '5',
  `cost_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parts_brand_id_clean_part_number_unique` (`brand_id`,`clean_part_number`),
  KEY `parts_category_id_foreign` (`category_id`),
  KEY `parts_clean_part_number_index` (`clean_part_number`),
  KEY `parts_bin_location_index` (`bin_location`),
  CONSTRAINT `parts_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `parts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parts` (`id`, `brand_id`, `category_id`, `part_number`, `clean_part_number`, `name`, `description`, `unit`, `bin_location`, `min_stock_level`, `cost_price`, `selling_price`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '6', '1', 'FS36231', 'FS36231', 'Fuel Water Separator Filter FS36231', 'Fleetguard Fuel Water Separator', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('2', '6', '1', 'FS1280', 'FS1280', 'Fuel Water Separator Filter FS1280', 'Fleetguard Fuel Filter / Water Separator', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('3', '6', '1', 'FS1242', 'FS1242', 'Fuel Water Separator Filter FS1242', 'Fleetguard Fuel Water Separator', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('4', '6', '1', 'FF5052', 'FF5052', 'Fuel Filter FF5052', 'Fleetguard Fuel Filter Spin-on', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('5', '6', '2', 'LF3349', 'LF3349', 'Lube Oil Filter LF3349', 'Fleetguard Lube Filter Full-Flow', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('6', '1', '1', '4616545', '4616545', 'Fuel Filter 4616545', 'Hitachi Genuine Parts Fuel Filter', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('7', '1', '1', '4616544', '4616544', 'Fuel Filter 4616544', 'Hitachi Genuine Fuel Filter', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('8', '1', '2', '4658521', '4658521', 'Engine Oil Filter 4658521RCP', 'Hitachi Genuine Engine Oil Filter', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('9', '7', '1', 'VH23390-E0020T1', 'VH23390E0020T1', 'Fuel Filter Element VH23390-E0020T1', 'Kobelco Genuine Parts Filter Element', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('10', '7', '5', 'YN21P01157R100', 'YN21P01157R100', 'Hydraulic Return Filter YN21P01157R100', 'Kobelco Filter Cartridge', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('11', '3', '1', '523-4987', '5234987', 'Fuel Filter Element 523-4987', 'Caterpillar Secondary Fuel Filter Element', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('12', '3', '1', '509-5694', '5095694', 'Fuel Filter Water Separator 509-5694', 'Caterpillar Primary Fuel Filter Water Separator', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('13', '4', '2', '53C0574', '53C0574', 'Oil Filter Element 53C0574', 'LiuGong Genuine Parts Filter', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('14', '4', '1', '53C0436ID', '53C0436ID', 'Fuel Water Separator 53C0436ID', 'LiuGong Genuine Parts Fuel Water Separator', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('15', '8', '6', '800194923', '800194923', 'Filter Element 800194923', 'XCMG Genuine Filter Part', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('16', '9', '1', '2040PM', '2040PM', 'Turbine Series Fuel Filter 2040PM', 'Parker Racor 30 Micron Fuel Filter Element', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('17', '9', '1', '2020PM', '2020PM', 'Turbine Series Fuel Filter 2020PM', 'Parker Racor 30 Micron Fuel Filter Element', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('18', '10', '7', 'A104-01460', 'A10401460', 'Air / Element Filter A104-01460', 'Sany Genuine Parts Element Filter', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL),
('19', '2', '5', '20Y-04-J1130', '20Y04J1130', 'Element Assy Filter 20Y-04-J1130', 'Komatsu Genuine Parts Element Assy', 'PCS', NULL, '5', '0.00', '0.00', '1', '2026-10-09 04:00:20', '2026-10-09 04:00:20', NULL);

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `password_reset_tokens` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `payments` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('CASH','BANK_TRANSFER','GIRO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_receipt_number_unique` (`receipt_number`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_created_by_foreign` (`created_by`),
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `permissions` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `role_has_permissions` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `roles` (3 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
('1', 'Admin', 'web', '2026-10-08 18:09:35', '2026-10-08 18:09:35'),
('2', 'Gudang', 'web', '2026-10-08 18:09:35', '2026-10-08 18:09:35'),
('3', 'Kasir', 'web', '2026-10-08 18:09:35', '2026-10-08 18:09:35');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `sales_orders` (0 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sales_orders`;
CREATE TABLE `sales_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('DRAFT','CONFIRMED','DELIVERED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_orders_order_number_unique` (`order_number`),
  KEY `sales_orders_contact_id_foreign` (`contact_id`),
  KEY `sales_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `sales_orders_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  CONSTRAINT `sales_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `sessions` (17 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5asqOVP3RhAoshxKGV3wE4vpnrs2w37aqs8Ic3KS', NULL, '169.150.224.130', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiREE5NlRQRlM1VzNrVU1vVnlJejdxUkJiYUwwSzR2WFp6VUNEa0VXRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', '1792239803'),
('7TxMmjJgElfwR21kJZhKSvh3cNBUK8CHffpi2ViI', NULL, '152.233.47.66', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiR0tIVXhlWm51cEJCTjRtZENNRXVoa2R0U21BMzFjaE8wUExiN0ZmQiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239287'),
('bARWWrkTwzer6jEvnplOk3DVGIRUKlbxRMHUNbFR', NULL, '152.233.30.104', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/154.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZWJQRlRQNTAzUkdGdWw2N2V4VHExVUJYMURxY3k1eUdMZll4Mm9DRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792241718'),
('Ctu18MI0TlIgRuineTvwvqLMnFwyedcODuTxHXk9', NULL, '89.222.103.194', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia1dEYktxdlg2bGxpYkwweWxkbFBDNHJiQ3VLMXBrME9IczJ1dlg0WCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcCI7czo1OiJyb3V0ZSI7czoyNzoiZ2VuZXJhdGVkOjpSVkc4MUhnaXNrQ3RyQ1ZqIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1792239288'),
('DipsKUBFDnvOmJoYXmudyq5kyYbQ8MLT0JANMSJR', NULL, '169.150.224.129', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQW5OMHh4d0laZ015b0I4Mm9CUWNITFQ1cXdJSjJNNEliY1dZeGNqUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', '1792239857'),
('DTu94YGc6j7o4gzMWh1lMpuYsYqdLA88ybgggokh', NULL, '46.151.194.129', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSmliOFlEc3E4d3Y3Q3FtTEt6ZVV6dkpPTDJZUUJYU1d1NVlnbHhydiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239289'),
('EutswloGsYnZSZchGAaZxspFrIyte01ANCFMaKYI', NULL, '152.233.43.34', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZkRtNlYxcmZOZnpHVTZudUsxNENhSUFpZE1yQU9ueGp1SnAyQjZLOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239290'),
('gdXCMZly6HjUzzNRqRxw1PKenwSK6TRCR8djdpeU', '1', '152.233.68.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiekhwblBpOFRvdTZIdzJXWXNEZHlEQmRPendNRFNjVUxHc2kzcWQ3eiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjU0OiJodHRwczovL2hlYXZ5dHJhY2std21zLXByb2R1Y3Rpb24udXAucmFpbHdheS5hcHAvYWRtaW4iO3M6NToicm91dGUiO3M6MzA6ImZpbGFtZW50LmFkbWluLnBhZ2VzLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6ImU2ODcxMTY0YjM0NTdjOTFiOTE5ODY3MjZmZmVmYjg4YjJhNWIwNTJjMjM1NGRjZjY1OGNhZGIzYzNjZDYzZDkiO3M6NjoidGFibGVzIjthOjc6e3M6NDA6IjM3ZTZlM2Y2MGFkYzgzZGM1NjI1YTBiYjUyNjFlNjIyX2NvbHVtbnMiO2E6NTp7aTowO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJpbnZvaWNlX251bWJlciI7czo1OiJsYWJlbCI7czoxMjoiTm9tb3IgRmFrdHVyIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MTthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMjoiaW52b2ljZV9kYXRlIjtzOjU6ImxhYmVsIjtzOjEwOiJUZ2wgVGVyYml0IjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MjthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoyMDoiY29udGFjdC5jb21wYW55X25hbWUiO3M6NToibGFiZWwiO3M6OToiUGVsYW5nZ2FuIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MzthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMToiZ3JhbmRfdG90YWwiO3M6NToibGFiZWwiO3M6MTM6IlRvdGFsIFRhZ2loYW4iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo0O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjY6InN0YXR1cyI7czo1OiJsYWJlbCI7czo2OiJTdGF0dXMiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9fXM6NDA6IjA5OTFiMWQyMjg2NGU3MmMwZTgzZWIwYTJjZDA3NzI3X2NvbHVtbnMiO2E6Njp7aTowO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjExOiJwYXJ0X251bWJlciI7czo1OiJsYWJlbCI7czoxMDoiTm9tb3IgUGFydCI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoibmFtZSI7czo1OiJsYWJlbCI7czoxMToiTmFtYSBCYXJhbmciO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEwOiJicmFuZC5uYW1lIjtzOjU6ImxhYmVsIjtzOjU6Ik1lcmVrIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MzthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMzoiY2F0ZWdvcnkubmFtZSI7czo1OiJsYWJlbCI7czo4OiJLYXRlZ29yaSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjQ7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MzA6InN0b2NrX2JhbGFuY2VzX3N1bV9xdHlfb25faGFuZCI7czo1OiJsYWJlbCI7czoxMDoiVG90YWwgU3RvayI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6OToiaXNfYWN0aXZlIjtzOjU6ImxhYmVsIjtzOjU6IkFrdGlmIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fX1zOjQwOiJmYzQ3MmFhYzE3Njk0ZjU0NjZlM2FkOWM5NmIxNDZhMV9jb2x1bW5zIjthOjY6e2k6MDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxNDoicmVjZWlwdF9udW1iZXIiO3M6NToibGFiZWwiO3M6MTU6Ik5vLiBEb2t1bWVuIExQQiI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTI6InJlY2VpcHRfZGF0ZSI7czo1OiJsYWJlbCI7czo5OiJUZ2wgTWFzdWsiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjIwOiJjb250YWN0LmNvbXBhbnlfbmFtZSI7czo1OiJsYWJlbCI7czoxODoiU3VwcGxpZXIgLyBQZW1hc29rIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MzthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxNDoid2FyZWhvdXNlLm5hbWUiO3M6NToibGFiZWwiO3M6MTM6Ikd1ZGFuZyBUdWp1YW4iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo0O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjExOiJpdGVtc19jb3VudCI7czo1OiJsYWJlbCI7czoxMDoiVG90YWwgSXRlbSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6Njoic3RhdHVzIjtzOjU6ImxhYmVsIjtzOjY6IlN0YXR1cyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO319czo0MDoiZTFiMGU5NmY3NTc4ZTc1MjQ2MDI4MjE4N2Y0ZTNiZGRfY29sdW1ucyI7YTo1OntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6OToiZG9fbnVtYmVyIjtzOjU6ImxhYmVsIjtzOjg6Ik5vbW9yIFNKIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MTthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMzoiZGVsaXZlcnlfZGF0ZSI7czo1OiJsYWJlbCI7czo5OiJUZ2wgS2lyaW0iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJ3YXJlaG91c2UubmFtZSI7czo1OiJsYWJlbCI7czoxMToiR3VkYW5nIEFzYWwiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTozO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjIwOiJjb250YWN0LmNvbXBhbnlfbmFtZSI7czo1OiJsYWJlbCI7czoxODoiVHVqdWFuIC8gUGVsYW5nZ2FuIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6NDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo2OiJzdGF0dXMiO3M6NToibGFiZWwiO3M6NjoiU3RhdHVzIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fX1zOjQwOiJjYTQwYTNmZjI1ODc0ZjhhOGMwN2YwYzcxZDg3NTk2NF9jb2x1bW5zIjthOjExOntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTI6Ildha3R1IE11dGFzaSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTY6InBhcnQucGFydF9udW1iZXIiO3M6NToibGFiZWwiO3M6MTA6Ik5vbW9yIFBhcnQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjk6InBhcnQubmFtZSI7czo1OiJsYWJlbCI7czoxMToiTmFtYSBCYXJhbmciO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTozO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJ3YXJlaG91c2UubmFtZSI7czo1OiJsYWJlbCI7czo2OiJHdWRhbmciO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo0O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJyZWZlcmVuY2VfdHlwZSI7czo1OiJsYWJlbCI7czoxNToiSmVuaXMgVHJhbnNha3NpIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6NTthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxNjoicmVmZXJlbmNlX251bWJlciI7czo1OiJsYWJlbCI7czoxMToiTm8uIERva3VtZW4iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo2O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEwOiJxdHlfY2hhbmdlIjtzOjU6ImxhYmVsIjtzOjY6Ik11dGFzaSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjc7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTQ6ImJhbGFuY2VfYmVmb3JlIjtzOjU6ImxhYmVsIjtzOjEwOiJTYWxkbyBBd2FsIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6ODthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMzoiYmFsYW5jZV9hZnRlciI7czo1OiJsYWJlbCI7czoxMDoiU2lzYSBTYWxkbyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjk7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NToibm90ZXMiO3M6NToibGFiZWwiO3M6MTA6IktldGVyYW5nYW4iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToxMDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoxMjoiY3JlYXRvci5uYW1lIjtzOjU6ImxhYmVsIjtzOjg6Ik9wZXJhdG9yIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fX1zOjQwOiIxOTQyODhlMjk3ZDNmMzE1NGZjYmY3NzhjODhmMTQxNF9jb2x1bW5zIjthOjU6e2k6MDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo0OiJjb2RlIjtzOjU6ImxhYmVsIjtzOjExOiJLb2RlIEd1ZGFuZyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoibmFtZSI7czo1OiJsYWJlbCI7czoxMToiTmFtYSBHdWRhbmciO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjc6ImFkZHJlc3MiO3M6NToibGFiZWwiO3M6NjoiQWxhbWF0IjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MzthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czoyMDoic3RvY2tfYmFsYW5jZXNfY291bnQiO3M6NToibGFiZWwiO3M6MTc6Ikp1bWxhaCBKZW5pcyBQYXJ0IjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6NDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo5OiJpc19hY3RpdmUiO3M6NToibGFiZWwiO3M6NToiQWt0aWYiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9fXM6NDA6IjQyMzFlZGQ3MjI1NjQyMWVjMjNkNzJkYTQ3OGY4ODA3X2NvbHVtbnMiO2E6Njp7aTowO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEyOiJjb21wYW55X25hbWUiO3M6NToibGFiZWwiO3M6MjQ6Ik5hbWEgUmVsYXNpIC8gUGVydXNhaGFhbiI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoidHlwZSI7czo1OiJsYWJlbCI7czo0OiJUaXBlIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MjthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo4OiJwaWNfbmFtZSI7czo1OiJsYWJlbCI7czozOiJQSUMiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTozO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjU6InBob25lIjtzOjU6ImxhYmVsIjtzOjEyOiJUZWxlcG9uIC8gV0EiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo0O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjIwOiJ0ZXJtX29mX3BheW1lbnRfZGF5cyI7czo1OiJsYWJlbCI7czo2OiJUZXJtaW4iO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTo1O2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjU6ImxhYmVsIjtzOjE1OiJUZXJkYWZ0YXIgU2VqYWsiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO319fX0=', '1792248258'),
('L7QoB5o6p0fd1uPx4UVHTNst7T8kF7P99zz7bMZR', NULL, '152.233.30.104', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibWN2UUdyZGN5ZDAxamd4eFJLVFA0N1Z0ZDgwUURsQkR5WXRRNm1BayI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NDoiaHR0cHM6Ly9oZWF2eXRyYWNrLXdtcy1wcm9kdWN0aW9uLnVwLnJhaWx3YXkuYXBwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7czo1OiJyb3V0ZSI7czozMDoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1792239498'),
('lkVGc0fewNSdHf5iUGFQCvEil2zblIIJZv21Ppv2', NULL, '152.233.23.193', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaTNHZkVLTlRPN0EzVFo5YWhycWNXQW45OW1ScnVod1VybXFITDFLNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcCI7czo1OiJyb3V0ZSI7czoyNzoiZ2VuZXJhdGVkOjp6djlVN05zTmxLQVdUODhIIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1792241707'),
('MBXiuMNCkROoKpzLuWIK2mu2p4XrMsQNsZd7fEPF', NULL, '152.233.30.104', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVjFoZ05qTHN4RUpHMjRVRWpLbTd2U25Hc1VmU3p2VDBlSnN4SmJsSSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NDoiaHR0cHM6Ly9oZWF2eXRyYWNrLXdtcy1wcm9kdWN0aW9uLnVwLnJhaWx3YXkuYXBwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7czo1OiJyb3V0ZSI7czozMDoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1792239555'),
('QLdtXx8ZV6DSaV96Rlj19y2gqyTnwy98Q2i6v6If', NULL, '169.150.224.129', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQWtlVlRBZnpZTmVodXdKM2JKbTltbG1Ec29DSWxjWkNkU0tTM1JrWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239288'),
('SAeIK7mVZi8pv859ey7OHtu2aZ4IJPkRGiHFvrv4', NULL, '89.222.103.193', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicklZZkZSdDZTTkxOTGZOQkMzc2x3VVo1RlhWeTBqNzR1TkRXcVRRZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', '1792240006'),
('SDzsqEBLVStkzKCcWnpaI00bJdCcoVLBDFRmrNvl', NULL, '152.233.47.65', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiU1dGZEhNODFmVnhpd3VrTmV6c0hIRUhFN3d2N3RsaFlINlZ2bDNTcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcCI7czo1OiJyb3V0ZSI7czoyNzoiZ2VuZXJhdGVkOjpSVkc4MUhnaXNrQ3RyQ1ZqIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1792239292'),
('u9tmT7w80ZukYaaY2BdGNve3kXtnfrLIftia1ldl', NULL, '46.151.194.130', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUmtNOU01NURTSHVEWHlPOWc3dFZ5R2JSTnNmV1NaNnJoZDNhV0hJSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792241765'),
('vdUiRUjrdRDHkaCW2I3rKgGCBhW0GRAl9HRLip5h', NULL, '152.233.43.33', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRWRZdEtEUks2b0tVeWNubnNaRFVqWm13WWEwWXByM3g0ODNmakpZcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239288'),
('WZTwUnBSPPcQ1fCKkWcQUSrHGPFsdWjralRZ1lgZ', NULL, '152.233.30.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOXA3V0lnbHl0ZFBObnFiOTdXbFJNTTA2cVJXbVlhQXdybkdITlNMNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6NTQ6Imh0dHBzOi8vaGVhdnl0cmFjay13bXMtcHJvZHVjdGlvbi51cC5yYWlsd2F5LmFwcC9hZG1pbiI7fX0=', '1792239287');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `stock_balances` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_balances`;
CREATE TABLE `stock_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `qty_on_hand` int NOT NULL DEFAULT '0',
  `qty_reserved` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_balances_warehouse_id_part_id_unique` (`warehouse_id`,`part_id`),
  KEY `stock_balances_part_id_foreign` (`part_id`),
  CONSTRAINT `stock_balances_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_balances_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_balances` (`id`, `warehouse_id`, `part_id`, `qty_on_hand`, `qty_reserved`, `created_at`, `updated_at`) VALUES
('1', '1', '1', '1', '0', '2026-10-15 15:05:35', '2026-10-15 15:05:35');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `stock_ledgers` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_ledgers`;
CREATE TABLE `stock_ledgers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_change` int NOT NULL,
  `balance_before` int NOT NULL,
  `balance_after` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_ledgers_part_id_foreign` (`part_id`),
  KEY `stock_ledgers_created_by_foreign` (`created_by`),
  KEY `stock_ledgers_warehouse_id_part_id_created_at_index` (`warehouse_id`,`part_id`,`created_at`),
  CONSTRAINT `stock_ledgers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_ledgers_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`),
  CONSTRAINT `stock_ledgers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_ledgers` (`id`, `warehouse_id`, `part_id`, `reference_type`, `reference_id`, `reference_number`, `qty_change`, `balance_before`, `balance_after`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
('1', '1', '1', 'GOODS_RECEIPT', '1', 'LPB/202610/0001', '1', '0', '1', 'Penerimaan LPB: LPB/202610/0001', '1', '2026-10-15 15:05:35', '2026-10-15 15:05:35');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `users` (3 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
('1', 'Super Admin', 'admin@heavytrack.com', NULL, '$2y$12$jbffpIZFHzVsS/9q1ipZqu5srtwD1Vj3JGVBkqiq9ycjfvXuebACW', 'uvYcyUr1a3LKwnpCU4FYgPcDVtUepZrBDpgtDe9kVT9sXw2UzW6ILQLxohoe', '2026-10-08 18:09:35', '2026-10-08 18:09:35'),
('2', 'Kepala Gudang', 'gudang@heavytrack.com', NULL, '$2y$12$Sj0bDdIrCkR6NhmXPg13rOaTg7wxzkWU7HLMXUs9OCG.4atmqe9.y', NULL, '2026-10-08 18:09:35', '2026-10-08 18:09:35'),
('3', 'Kasir Utama', 'kasir@heavytrack.com', NULL, '$2y$12$esmFs.CRWzzph9oPc6mm/usZ3IIDOCIn7RZU1q4fJktdKhQ0r3Ymq', NULL, '2026-10-08 18:09:35', '2026-10-08 18:09:35');

-- --------------------------------------------------------
-- Struktur & Data untuk tabel `warehouses` (1 baris)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warehouses` (`id`, `code`, `name`, `address`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '12', 'poris ', 'poris', '1', '2026-10-15 13:58:51', '2026-10-15 13:58:51', NULL);

SET FOREIGN_KEY_CHECKS = 1;
-- Dump completed on 2026-10-17 17:13:07
