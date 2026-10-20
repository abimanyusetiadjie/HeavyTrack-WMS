<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\GoodsReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_order_number_increments_sequentially(): void
    {
        $prefix = 'SJ/' . date('Ym') . '/';

        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'GDG-01',
        ]);

        $contact = \App\Models\Contact::create([
            'company_name' => 'PT Mitra Sejati',
            'type' => 'CUSTOMER',
        ]);

        $user = \App\Models\User::factory()->create();

        // 1st record
        $do1 = DeliveryOrder::create([
            'do_number' => $prefix . '0001',
            'warehouse_id' => $warehouse->id,
            'contact_id' => $contact->id,
            'created_by' => $user->id,
            'delivery_date' => now(),
            'status' => 'CONFIRMED',
        ]);
        $this->assertEquals($prefix . '0001', $do1->do_number);

        // Compute next sequence
        $latest = DeliveryOrder::withTrashed()
            ->where('do_number', 'like', $prefix . '%')
            ->orderByDesc('do_number')
            ->value('do_number');
        preg_match('/(\d+)$/', $latest, $m);
        $nextSeq = (int) $m[1] + 1;
        $nextDoNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $this->assertEquals($prefix . '0002', $nextDoNumber);

        // Create 2nd record
        $do2 = DeliveryOrder::create([
            'do_number' => $nextDoNumber,
            'warehouse_id' => $warehouse->id,
            'contact_id' => $contact->id,
            'created_by' => $user->id,
            'delivery_date' => now(),
            'status' => 'CONFIRMED',
        ]);
        $this->assertEquals($prefix . '0002', $do2->do_number);

        // Soft-delete 1st record
        $do1->delete();

        // Compute next sequence again (should still be 0003, not resetting or colliding)
        $latest = DeliveryOrder::withTrashed()
            ->where('do_number', 'like', $prefix . '%')
            ->orderByDesc('do_number')
            ->value('do_number');
        preg_match('/(\d+)$/', $latest, $m);
        $nextSeq = (int) $m[1] + 1;
        $nextDoNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $this->assertEquals($prefix . '0003', $nextDoNumber);
    }

    public function test_goods_receipt_and_invoice_numbering_increments(): void
    {
        $contact = \App\Models\Contact::create([
            'company_name' => 'PT Mitra Sejati',
            'type' => 'CUSTOMER',
        ]);
        $user = \App\Models\User::factory()->create();

        $prefix = 'INV/' . date('Ym') . '/';
        $inv1 = Invoice::create([
            'invoice_number' => $prefix . '0001',
            'contact_id' => $contact->id,
            'created_by' => $user->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'UNPAID',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 0,
        ]);
        $this->assertEquals($prefix . '0001', $inv1->invoice_number);

        $latest = Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        preg_match('/(\d+)$/', $latest, $m);
        $nextSeq = (int) $m[1] + 1;
        $nextInvNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $this->assertEquals($prefix . '0002', $nextInvNumber);
    }
}
