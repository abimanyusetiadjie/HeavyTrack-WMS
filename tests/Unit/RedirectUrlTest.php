<?php

namespace Tests\Unit;

use App\Filament\Resources\ContactResource\Pages\CreateContact;
use App\Filament\Resources\ContactResource\Pages\EditContact;
use App\Filament\Resources\DeliveryOrderResource\Pages\CreateDeliveryOrder;
use App\Filament\Resources\DeliveryOrderResource\Pages\EditDeliveryOrder;
use App\Filament\Resources\GoodsReceiptResource\Pages\CreateGoodsReceipt;
use App\Filament\Resources\GoodsReceiptResource\Pages\EditGoodsReceipt;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\WarehouseResource\Pages\CreateWarehouse;
use App\Filament\Resources\WarehouseResource\Pages\EditWarehouse;
use Tests\TestCase;

class RedirectUrlTest extends TestCase
{
    public function test_all_create_and_edit_pages_redirect_to_index_after_save(): void
    {
        $classes = [
            CreateContact::class => '/admin/contacts',
            EditContact::class => '/admin/contacts',
            CreateDeliveryOrder::class => '/admin/delivery-orders',
            EditDeliveryOrder::class => '/admin/delivery-orders',
            CreateGoodsReceipt::class => '/admin/goods-receipts',
            EditGoodsReceipt::class => '/admin/goods-receipts',
            CreateInvoice::class => '/admin/invoices',
            EditInvoice::class => '/admin/invoices',
            CreateProduct::class => '/admin/products',
            EditProduct::class => '/admin/products',
            CreateWarehouse::class => '/admin/warehouses',
            EditWarehouse::class => '/admin/warehouses',
        ];

        foreach ($classes as $class => $expectedPath) {
            $instance = new $class();
            $method = new \ReflectionMethod($instance, 'getRedirectUrl');
            $method->setAccessible(true);
            $url = $method->invoke($instance);

            $this->assertStringContainsString($expectedPath, $url, "Page {$class} should redirect to {$expectedPath}");
        }
    }
}
