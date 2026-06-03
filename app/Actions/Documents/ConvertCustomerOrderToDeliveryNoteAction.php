<?php

namespace App\Actions\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Enums\ValidationAction;
use App\Models\CustomerOrder;
use App\Models\DeliveryNote;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertCustomerOrderToDeliveryNoteAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    public function execute(CustomerOrder $customerOrder, User $user): DeliveryNote
    {
        return DB::transaction(function () use ($customerOrder, $user): DeliveryNote {
            $customerOrder = CustomerOrder::query()
                ->with('items')
                ->whereKey($customerOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($customerOrder->status !== DocumentStatus::Validated) {
                throw new RuntimeException('Le bon de commande client doit etre confirme avant creation du BL.');
            }

            $status = $user->bypassesDocumentValidation() ? DeliveryNoteStatus::Validated : DeliveryNoteStatus::Draft;

            $deliveryNote = DeliveryNote::query()->create([
                'number' => $this->documentNumberGenerator->generate('delivery_note'),
                'proforma_id' => $customerOrder->proforma_id,
                'customer_order_id' => $customerOrder->id,
                'client_id' => $customerOrder->client_id,
                'client_delivery_site_id' => $customerOrder->client_delivery_site_id,
                'status' => $status,
                'submitted_at' => $status === DeliveryNoteStatus::Validated ? now() : null,
                'validated_by' => $status === DeliveryNoteStatus::Validated ? $user->id : null,
                'validated_at' => $status === DeliveryNoteStatus::Validated ? now() : null,
                'planned_delivery_date' => now()->addDay()->toDateString(),
                'subtotal' => $customerOrder->subtotal,
                'discount_total' => $customerOrder->discount_total,
                'tax_total' => $customerOrder->tax_total,
                'total' => $customerOrder->total,
                'notes' => $customerOrder->confirmed_terms,
                'created_by' => $user->id,
            ]);

            foreach ($customerOrder->items as $item) {
                $deliveryNote->items()->create([
                    'proforma_item_id' => $item->proforma_item_id,
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_internal_reference' => $item->product_internal_reference,
                    'client_product_reference' => $item->client_product_reference,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'delivered_quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'line_total' => $item->line_total,
                ]);
            }

            if ($status === DeliveryNoteStatus::Validated) {
                $this->validationHistoryLogger->log(
                    document: $deliveryNote,
                    action: ValidationAction::Validate,
                    fromStatus: DeliveryNoteStatus::Draft->value,
                    toStatus: DeliveryNoteStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            $this->activityLogger->log(
                action: 'converted_to_delivery_note',
                module: 'customer_orders',
                description: "Bon de commande {$customerOrder->number} converti en BL {$deliveryNote->number}.",
                subject: $customerOrder,
                newValues: ['delivery_note_id' => $deliveryNote->id]
            );

            return $deliveryNote->refresh()->load(['client', 'items']);
        });
    }
}
