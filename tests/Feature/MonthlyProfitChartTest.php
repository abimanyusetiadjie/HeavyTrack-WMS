<?php

namespace Tests\Feature;

use App\Filament\Widgets\MonthlyProfitChart;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyProfitChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_profit_chart_widget_renders_and_calculates_profit_correctly(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_chart@heavytrack.com',
            'name' => 'Owner Chart',
        ]);

        $contact = Contact::create([
            'company_name' => 'PT Tambang Nusantara',
            'type' => 'CUSTOMER',
        ]);

        $brand = Brand::firstOrCreate(['name' => 'Komatsu'], ['code' => 'KOM']);
        $cat = Category::firstOrCreate(['name' => 'Filter'], ['code' => 'FLT']);

        // Buat part dengan HPP (cost_price) 100.000 dan Harga Jual (selling_price) 150.000
        $part = Part::create([
            'part_number' => 'FILT-TEST-001',
            'name' => 'Test Heavy Duty Filter',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'cost_price' => 100000,
            'selling_price' => 150000,
            'unit' => 'PCS',
        ]);

        $currentYear = date('Y');

        // 1. Buat Faktur di Bulan Januari: 10 PCS @ Rp 150.000 = Rp 1.500.000 (HPP 10 x 100.000 = Rp 1.000.000, Laba = Rp 500.000)
        $invJan = Invoice::create([
            'invoice_number' => 'INV/' . $currentYear . '01/0001',
            'contact_id' => $contact->id,
            'invoice_date' => Carbon::create((int) $currentYear, 1, 15),
            'due_date' => Carbon::create((int) $currentYear, 1, 30),
            'status' => 'PAID',
            'subtotal' => 1500000,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => 1500000,
            'paid_amount' => 1500000,
            'created_by' => $owner->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invJan->id,
            'part_id' => $part->id,
            'part_number_snapshot' => $part->part_number,
            'part_name_snapshot' => $part->name,
            'qty' => 10,
            'unit' => 'PCS',
            'unit_price' => 150000,
            'discount_percent' => 0,
            'total_price' => 1500000,
        ]);

        // 2. Buat Faktur di Bulan Februari: 20 PCS @ Rp 150.000 = Rp 3.000.000 (HPP 20 x 100.000 = Rp 2.000.000, Laba = Rp 1.000.000)
        $invFeb = Invoice::create([
            'invoice_number' => 'INV/' . $currentYear . '02/0001',
            'contact_id' => $contact->id,
            'invoice_date' => Carbon::create((int) $currentYear, 2, 10),
            'due_date' => Carbon::create((int) $currentYear, 2, 25),
            'status' => 'UNPAID',
            'subtotal' => 3000000,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'grand_total' => 3000000,
            'paid_amount' => 0,
            'created_by' => $owner->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invFeb->id,
            'part_id' => $part->id,
            'part_number_snapshot' => $part->part_number,
            'part_name_snapshot' => $part->name,
            'qty' => 20,
            'unit' => 'PCS',
            'unit_price' => 150000,
            'discount_percent' => 0,
            'total_price' => 3000000,
        ]);

        $this->actingAs($owner);

        // Test Livewire Chart Widget
        $test = Livewire::test(MonthlyProfitChart::class)
            ->assertSuccessful();

        $data = $test->instance()->getData();

        // Verifikasi labels berjumlah 12 bulan (Januari - Desember)
        $this->assertCount(12, $data['labels']);
        $this->assertEquals('JANUARI', $data['labels'][0]);
        $this->assertEquals('FEBRUARI', $data['labels'][1]);

        // Verifikasi datasets ada 2: Keuntungan Bersih (Laba) dan Total Penjualan (Omset)
        $this->assertCount(2, $data['datasets']);
        $this->assertEquals('Keuntungan Bersih (Laba)', $data['datasets'][0]['label']);
        $this->assertEquals('Total Penjualan (Omset)', $data['datasets'][1]['label']);

        // Verifikasi data bulan Januari (index 0): Laba 500.000, Omset 1.500.000
        $this->assertEquals(500000, $data['datasets'][0]['data'][0]);
        $this->assertEquals(1500000, $data['datasets'][1]['data'][0]);

        // Verifikasi data bulan Februari (index 1): Laba 1.000.000, Omset 3.000.000
        $this->assertEquals(1000000, $data['datasets'][0]['data'][1]);
        $this->assertEquals(3000000, $data['datasets'][1]['data'][1]);

        // Verifikasi filter 6 bulan terakhir
        $test6m = Livewire::test(MonthlyProfitChart::class)
            ->set('filter', 'last_6_months')
            ->assertSuccessful();
        $data6m = $test6m->instance()->getData();
        $this->assertCount(6, $data6m['labels']);

        // Verifikasi filter 12 bulan terakhir
        $test12m = Livewire::test(MonthlyProfitChart::class)
            ->set('filter', 'last_12_months')
            ->assertSuccessful();
        $data12m = $test12m->instance()->getData();
        $this->assertCount(12, $data12m['labels']);
    }

    public function test_dashboard_page_renders_monthly_profit_chart(): void
    {
        $owner = User::where('email', 'owner@heavytrack.com')->first() ?: User::factory()->create([
            'email' => 'owner_dash@heavytrack.com',
            'name' => 'Owner Dash',
        ]);

        $this->actingAs($owner);
        $response = $this->get('/admin');
        $response->assertSuccessful();
        $response->assertSee('Grafik Keuntungan Bulanan');
    }
}
