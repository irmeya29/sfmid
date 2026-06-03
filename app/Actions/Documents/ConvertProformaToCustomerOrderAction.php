<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\CustomerOrder;
use App\Models\Proforma;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\DocumentNumberGenerator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertProformaToCustomerOrderAction
{
    public function __construct(
        private readonly DocumentNumberGenerator $documentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Proforma $proforma, User $user, array $data = []): CustomerOrder
    {
        return DB::transaction(function () use ($proforma, $user, $data): CustomerOrder {
            $proforma = Proforma::query()
                ->with(['items', 'customerOrder'])
                ->whereKey($proforma->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($proforma->status !== DocumentStatus::Validated) {
                throw new RuntimeException('Seule une proforma validee peut generer un bon de commande client.');
            }

            if ($proforma->customerOrder) {
                throw new RuntimeException('Cette proforma possede deja un bon de commande client.');
            }

            $order = CustomerOrder::query()->create([
                'number' => $this->documentNumberGenerator->generate('customer_order'),
                'proforma_id' => $proforma->id,
                'client_id' => $proforma->client_id,
                'client_delivery_site_id' => $proforma->client_delivery_site_id,
                'customer_reference' => $data['customer_reference'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'status' => DocumentStatus::Validated,
                'confirmed_terms' => $data['confirmed_terms'] ?? $proforma->payment_terms ?? $proforma->terms,
                'attachment_path' => $data['attachment_path'] ?? null,
                'subtotal' => $proforma->subtotal,
                'discount_total' => $proforma->discount_total,
                'tax_total' => $proforma->tax_total,
                'total' => $proforma->total,
                'created_by' => $user->id,
            ]);

            foreach ($proforma->items as $item) {
                $order->items()->create([
                    'proforma_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_internal_reference' => $item->product_internal_reference,
                    'client_product_reference' => $item->client_product_reference,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal ?? ((float) $item->quantity * (float) $item->unit_price),
                    'discount_rate' => $item->discount_rate,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate ?? 0,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'line_total_ht' => $item->line_total_ht ?? (((float) $item->quantity * (float) $item->unit_price) - (float) $item->discount_amount),
                    'line_total_ttc' => $item->line_total_ttc ?? $item->line_total,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->activityLogger->log(
                action: 'converted_to_customer_order',
                module: 'customer_orders',
                description: "Bon de commande {$order->number} cree depuis la proforma {$proforma->number}.",
                subject: $order,
                newValues: $order->only(['proforma_id', 'client_id', 'customer_reference', 'total'])
            );

            return $order->refresh()->load(['client', 'items']);
        });
    }
}
