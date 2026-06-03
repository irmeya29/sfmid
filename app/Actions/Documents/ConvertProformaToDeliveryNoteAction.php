<?php

namespace App\Actions\Documents;

use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Enums\ValidationAction;
use App\Models\DeliveryNote;
use App\Models\Proforma;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertProformaToDeliveryNoteAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(Proforma $proforma, User $user): DeliveryNote
    {
        if (! $user->can('convertToDeliveryNote', $proforma)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        return DB::transaction(function () use ($proforma, $user): DeliveryNote {
            $proforma = Proforma::query()
                ->with(['items', 'deliveryNote'])
                ->whereKey($proforma->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($proforma->status !== DocumentStatus::Validated) {
                throw new RuntimeException('Seule une proforma validée peut être convertie en BL.');
            }

            if ($proforma->deliveryNote !== null) {
                throw new RuntimeException('Cette proforma possède déjà un bordereau de livraison.');
            }

            if ($proforma->items->isEmpty()) {
                throw new RuntimeException('Impossible de convertir une proforma sans lignes.');
            }

            $deliveryNoteStatus = $user->bypassesDocumentValidation()
                ? DeliveryNoteStatus::Validated
                : DeliveryNoteStatus::Draft;

            $deliveryNote = DeliveryNote::query()->create([
                'number' => $this->documentNumberGenerator->generate('delivery_note'),
                'proforma_id' => $proforma->id,
                'client_id' => $proforma->client_id,
                'client_delivery_site_id' => $proforma->client_delivery_site_id,
                'status' => $deliveryNoteStatus,
                'submitted_at' => $deliveryNoteStatus === DeliveryNoteStatus::Validated ? now() : null,
                'validated_by' => $deliveryNoteStatus === DeliveryNoteStatus::Validated ? $user->id : null,
                'validated_at' => $deliveryNoteStatus === DeliveryNoteStatus::Validated ? now() : null,
                'planned_delivery_date' => null,
                'delivered_at' => null,
                'delivered_by' => null,
                'receiver_name' => null,
                'receiver_phone' => null,
                'delivery_address' => null,
                'subtotal' => $proforma->subtotal,
                'discount_total' => $proforma->discount_total,
                'tax_total' => $proforma->tax_total,
                'total' => $proforma->total,
                'notes' => $proforma->notes,
                'created_by' => $user->id,
            ]);

            foreach ($proforma->items as $item) {
                $deliveryNote->items()->create([
                    'proforma_item_id' => $item->id,
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

            $fromStatus = $proforma->status->value;

            $proforma->forceFill([
                'status' => DocumentStatus::Converted,
                'converted_to_delivery_note_at' => now(),
            ])->save();

            $this->validationHistoryLogger->log(
                document: $proforma,
                action: ValidationAction::Convert,
                fromStatus: $fromStatus,
                toStatus: DocumentStatus::Converted->value,
                comment: "Conversion en BL {$deliveryNote->number}."
            );

            $this->activityLogger->log(
                action: 'converted_to_delivery_note',
                module: 'proformas',
                description: "Proforma {$proforma->number} convertie en BL {$deliveryNote->number}.",
                subject: $proforma,
                oldValues: ['status' => $fromStatus],
                newValues: [
                    'status' => DocumentStatus::Converted->value,
                    'delivery_note_id' => $deliveryNote->id,
                ],
            );

            $this->activityLogger->log(
                action: 'created_from_proforma',
                module: 'delivery_notes',
                description: "BL {$deliveryNote->number} créé depuis la proforma {$proforma->number}.",
                subject: $deliveryNote,
                newValues: [
                    'proforma_id' => $proforma->id,
                    'status' => $deliveryNoteStatus->value,
                ],
            );

            if ($deliveryNoteStatus === DeliveryNoteStatus::Validated) {
                $this->validationHistoryLogger->log(
                    document: $deliveryNote,
                    action: ValidationAction::Validate,
                    fromStatus: DeliveryNoteStatus::Draft->value,
                    toStatus: DeliveryNoteStatus::Validated->value,
                    comment: 'Validation automatique administrateur.'
                );
            }

            return $deliveryNote->load(['items', 'proforma']);
        });
    }
}
