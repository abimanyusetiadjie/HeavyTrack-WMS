<?php

namespace Tests\Feature;

use App\Filament\Resources\DeliveryOrderResource;
use App\Filament\Resources\GoodsReceiptResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\StockLedgerResource;
use App\Filament\Widgets\StatsOverview;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Part;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RealTimeSynchronizationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_realtime_polling_is_configured_on_all_critical_modules(): void
    {
        // 1. Dashboard Widget Real-Time Polling
        $widget = new StatsOverview();
        $refWidget = new \ReflectionClass($widget);
        $prop = $refWidget->getProperty('pollingInterval');
        $prop->setAccessible(true);
        $this->assertEquals('15s', $prop->getValue($widget));

        $chartWidget = new \App\Filament\Widgets\MonthlyProfitChart();
        $refChart = new \ReflectionClass($chartWidget);
        $propChart = $refChart->getProperty('pollingInterval');
        $propChart->setAccessible(true);
        $this->assertEquals('15s', $propChart->getValue($chartWidget));

        // 2. Table Real-Time Polling
        $tableProduct = ProductResource::table(new Table(new \App\Filament\Resources\ProductResource\Pages\ListProducts()));
        $this->assertEquals('30s', $tableProduct->getPollingInterval());

        $tableDO = DeliveryOrderResource::table(new Table(new \App\Filament\Resources\DeliveryOrderResource\Pages\ListDeliveryOrders()));
        $this->assertEquals('20s', $tableDO->getPollingInterval());

        $tableLPB = GoodsReceiptResource::table(new Table(new \App\Filament\Resources\GoodsReceiptResource\Pages\ListGoodsReceipts()));
        $this->assertEquals('20s', $tableLPB->getPollingInterval());

        $tableLedger = StockLedgerResource::table(new Table(new \App\Filament\Resources\StockLedgerResource\Pages\ListStockLedgers()));
        $this->assertEquals('15s', $tableLedger->getPollingInterval());

        $tableInvoice = InvoiceResource::table(new Table(new \App\Filament\Resources\InvoiceResource\Pages\ListInvoices()));
        $this->assertEquals('30s', $tableInvoice->getPollingInterval());
    }

    public function test_warehouse_transfer_synchronizes_physical_balance_and_stock_ledger(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_sync@heavytrack.com',
            'name' => 'Owner Sync',
        ]);

        $gma = Warehouse::firstOrCreate(['code' => 'GMA'], ['name' => 'GUDANG MITRA ABADI']);
        $gar = Warehouse::firstOrCreate(['code' => 'GAR'], ['name' => 'GUDANG ANEKARASA']);

        $brand = Brand::firstOrCreate(['name' => 'Hitachi'], ['code' => 'HIT']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        $part = Part::create([
            'part_number' => 'SYNC-100',
            'name' => 'Hitachi Hydraulic Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'bin_location' => 'Gudang Mitra Abadi',
        ]);

        // Saldo awal 80 unit di GMA (dilogkan juga ke StockLedger)
        StockBalance::create([
            'part_id' => $part->id,
            'warehouse_id' => $gma->id,
            'qty_on_hand' => 80,
        ]);
        StockLedger::create([
            'warehouse_id' => $gma->id,
            'part_id' => $part->id,
            'reference_type' => 'INITIAL_STOCK',
            'reference_id' => $part->id,
            'reference_number' => 'INIT/' . $part->id,
            'qty_change' => 80,
            'balance_before' => 0,
            'balance_after' => 80,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner);

        // 1. Pindahkan lokasi barang ke Gudang Anekarasa via Edit Product
        Livewire::test(\App\Filament\Resources\ProductResource\Pages\EditProduct::class, ['record' => $part->id])
            ->assertSuccessful()
            ->fillForm([
                'bin_location' => 'Gudang Anekarasa',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Verifikasi fisik berpindah
        $this->assertEquals(0, StockBalance::where('part_id', $part->id)->where('warehouse_id', $gma->id)->value('qty_on_hand'));
        $this->assertEquals(80, StockBalance::where('part_id', $part->id)->where('warehouse_id', $gar->id)->value('qty_on_hand'));

        // Verifikasi StockLedger mencatat mutasi debit dan kredit
        $this->assertDatabaseHas('stock_ledgers', [
            'warehouse_id' => $gma->id,
            'part_id' => $part->id,
            'qty_change' => -80,
            'balance_after' => 0,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'warehouse_id' => $gar->id,
            'part_id' => $part->id,
            'qty_change' => 80,
            'balance_after' => 80,
        ]);

        // 2. Jalankan audit & rekonsiliasi stok -> pastikan 0 discrepancy (100% sinkron!)
        $inventoryService = app(InventoryService::class);
        $reconciliation = $inventoryService->reconcileStock();
        $this->assertEquals(0, $reconciliation['discrepancies'], 'Saldo fisik dan kartu mutasi harus 100% selaras tanpa selisih');

        // 3. Pindahkan kembali ke Gudang Mitra Abadi
        Livewire::test(\App\Filament\Resources\ProductResource\Pages\EditProduct::class, ['record' => $part->id])
            ->assertSuccessful()
            ->fillForm([
                'bin_location' => 'Gudang Mitra Abadi',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(80, StockBalance::where('part_id', $part->id)->where('warehouse_id', $gma->id)->value('qty_on_hand'));
        $this->assertEquals(0, StockBalance::where('part_id', $part->id)->where('warehouse_id', $gar->id)->value('qty_on_hand'));

        $reconciliation2 = $inventoryService->reconcileStock();
        $this->assertEquals(0, $reconciliation2['discrepancies']);
    }
}
