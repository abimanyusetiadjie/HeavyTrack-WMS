<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_invoice_create_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test@heavytrack.com']);

        $response = $this->actingAs($user)->get(InvoiceResource::getUrl('create'));
        $response->assertSuccessful();
    }

    public function test_can_render_delivery_orders_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test2@heavytrack.com']);

        $response = $this->actingAs($user)->get('/admin/delivery-orders');
        $response->assertSuccessful();
    }

    public function test_can_render_invoices_list_page(): void
    {
        $user = User::factory()->create(['email' => 'admin_test3@heavytrack.com']);

        $response = $this->actingAs($user)->get('/admin/invoices');
        $response->assertSuccessful();
    }
}
