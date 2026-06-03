<?php

namespace Tests\Feature\Documents;

use App\Actions\Documents\ConvertProformaToDeliveryNoteAction;
use App\Actions\Validation\RejectProformaAction;
use App\Actions\Validation\SubmitProformaAction;
use App\Actions\Validation\ValidateProformaAction;
use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Models\DocumentNumberSequence;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProformaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_proforma_can_be_submitted_validated_and_converted_to_delivery_note(): void
    {
        DocumentNumberSequence::query()->create([
            'document_type' => 'delivery_note',
            'prefix' => 'BL',
            'next_number' => 1,
            'padding' => 5,
            'reset_period' => 'yearly',
            'last_generated_at' => null,
        ]);

        $creator = User::factory()->create();

        $validator = $this->userWithPermissions([
            'proformas.submit',
            'proformas.validate',
            'proformas.convert_to_delivery_note',
        ]);

        $proforma = Proforma::factory()->create([
            'created_by' => $creator->id,
            'status' => DocumentStatus::Draft,
        ]);

        $product = Product::factory()->create([
            'physical_stock' => 10,
            'sale_price' => 25000,
        ]);

        ProformaItem::factory()->create([
            'proforma_id' => $proforma->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 2,
            'unit_price' => 25000,
            'line_total' => 50000,
        ]);

        $submitted = app(SubmitProformaAction::class)->execute($proforma, $validator);

        $this->assertSame(DocumentStatus::PendingValidation, $submitted->status);

        $validated = app(ValidateProformaAction::class)->execute($submitted, $validator);

        $this->assertSame(DocumentStatus::Validated, $validated->status);

        $deliveryNote = app(ConvertProformaToDeliveryNoteAction::class)->execute($validated, $validator);

        $this->assertSame(DeliveryNoteStatus::Draft, $deliveryNote->status);
        $this->assertCount(1, $deliveryNote->items);
        $this->assertSame(DocumentStatus::Converted, $validated->refresh()->status);
        $this->assertSame('BL-'.now()->format('Y').'-00001', $deliveryNote->number);
    }

    public function test_reject_requires_reason(): void
    {
        $creator = User::factory()->create();

        $validator = $this->userWithPermissions([
            'proformas.reject',
        ]);

        $proforma = Proforma::factory()
            ->pendingValidation()
            ->create([
                'created_by' => $creator->id,
            ]);

        $this->expectException(InvalidArgumentException::class);

        app(RejectProformaAction::class)->execute($proforma, $validator, '');
    }

    public function test_creator_cannot_validate_own_proforma_without_sensitive_permission(): void
    {
        $creator = $this->userWithPermissions([
            'proformas.validate',
        ]);

        $proforma = Proforma::factory()
            ->pendingValidation()
            ->create([
                'created_by' => $creator->id,
            ]);

        $this->expectException(AuthorizationException::class);

        app(ValidateProformaAction::class)->execute($proforma, $creator);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::factory()->create();

        foreach ($permissions as $slug) {
            $permission = Permission::query()->create([
                'name' => $slug,
                'slug' => $slug,
                'module' => str($slug)->before('.')->toString(),
                'action' => str($slug)->after('.')->toString(),
                'is_sensitive' => false,
                'description' => null,
            ]);

            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();

        $user->roles()->attach($role);

        return $user;
    }
}
