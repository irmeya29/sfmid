<?php

namespace Tests\Feature\Documents;

use App\Actions\Documents\SaveDeliveryNoteAction;
use App\Actions\Documents\SaveDirectInvoiceAction;
use App\Actions\Documents\SaveProformaAction;
use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\DocumentNumberSequence;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDocumentValidationBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_commercial_documents_are_validated_immediately(): void
    {
        $this->sequence('proforma', 'PRO');
        $this->sequence('delivery_note', 'BL');
        $this->sequence('invoice', 'FAC');

        $admin = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $admin->roles()->attach($adminRole);

        $client = Client::factory()->create();
        $product = Product::factory()->create([
            'sale_price' => 15000,
            'physical_stock' => 10,
        ]);

        $items = [[
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15000,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'delivered_quantity' => 2,
        ]];

        $proforma = app(SaveProformaAction::class)->execute([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'items' => $items,
        ], $admin);

        $deliveryNote = app(SaveDeliveryNoteAction::class)->execute([
            'client_id' => $client->id,
            'planned_delivery_date' => now()->addDay()->toDateString(),
            'items' => $items,
        ], $admin);

        $invoice = app(SaveDirectInvoiceAction::class)->execute([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'items' => $items,
        ], $admin);

        $this->assertSame(DocumentStatus::Validated, $proforma->status);
        $this->assertSame($admin->id, $proforma->validated_by);
        $this->assertNotNull($proforma->validated_at);

        $this->assertSame(DeliveryNoteStatus::Validated, $deliveryNote->status);
        $this->assertSame($admin->id, $deliveryNote->validated_by);
        $this->assertNotNull($deliveryNote->validated_at);

        $this->assertSame(InvoiceStatus::Validated, $invoice->status);
        $this->assertSame($admin->id, $invoice->validated_by);
        $this->assertNotNull($invoice->validated_at);
    }

    public function test_non_admin_created_commercial_documents_keep_normal_draft_status(): void
    {
        $this->sequence('proforma', 'PRO');
        $this->sequence('delivery_note', 'BL');
        $this->sequence('invoice', 'FAC');

        $user = User::factory()->create();
        $client = Client::factory()->create();
        $product = Product::factory()->create(['sale_price' => 15000]);

        $items = [[
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'delivered_quantity' => 1,
        ]];

        $proforma = app(SaveProformaAction::class)->execute([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'items' => $items,
        ], $user);

        $deliveryNote = app(SaveDeliveryNoteAction::class)->execute([
            'client_id' => $client->id,
            'items' => $items,
        ], $user);

        $invoice = app(SaveDirectInvoiceAction::class)->execute([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'items' => $items,
        ], $user);

        $this->assertSame(DocumentStatus::Draft, $proforma->status);
        $this->assertSame(DeliveryNoteStatus::Draft, $deliveryNote->status);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
    }

    private function sequence(string $documentType, string $prefix): void
    {
        DocumentNumberSequence::query()->create([
            'document_type' => $documentType,
            'prefix' => $prefix,
            'next_number' => 1,
            'padding' => 5,
            'reset_period' => 'yearly',
            'last_generated_at' => null,
        ]);
    }
}
