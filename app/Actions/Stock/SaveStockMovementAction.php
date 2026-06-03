<?php

namespace App\Actions\Stock;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Enums\ValidationAction;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveStockMovementAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ValidationHistoryLogger $validationHistoryLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): StockMovement
    {
        return DB::transaction(function () use ($data, $user): StockMovement {
            $type = StockMovementType::from($data['type']);
            $quantity = (float) $data['quantity'];
            $stockColumn = $data['stock_column'] ?? 'physical_stock';
            $reason = trim((string) ($data['reason'] ?? ''));

            if ($quantity <= 0) {
                throw new RuntimeException('La quantité doit être supérieure à zéro.');
            }

            if ($this->requiresReason($type) && $reason === '') {
                throw new RuntimeException('Le motif est obligatoire pour ce mouvement.');
            }

            $product = Product::query()
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $status = $this->requiresValidation($type)
                ? StockMovementStatus::PendingValidation
                : StockMovementStatus::Validated;

            $direction = $this->directionFor($type);
            $unitCost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : (float) $product->purchase_price;

            if ($status === StockMovementStatus::PendingValidation) {
                $movement = StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => $type,
                    'direction' => $direction,
                    'status' => $status,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'physical_before' => $product->physical_stock,
                    'physical_after' => $product->physical_stock,
                    'reserved_before' => $product->reserved_stock,
                    'reserved_after' => $product->reserved_stock,
                    'suspense_before' => $product->suspense_stock,
                    'suspense_after' => $product->suspense_stock,
                    'tool_before' => $product->tool_stock,
                    'tool_after' => $product->tool_stock,
                    'reason' => $reason,
                    'created_by' => $user->id,
                ]);

                $this->activityLogger->log(
                    action: 'submitted',
                    module: 'stock',
                    description: "Mouvement stock {$type->label()} soumis pour validation.",
                    subject: $movement,
                    newValues: $movement->only(['product_id', 'type', 'quantity', 'status', 'reason']),
                );

                $this->validationHistoryLogger->log(
                    document: $movement,
                    action: ValidationAction::Submit,
                    fromStatus: null,
                    toStatus: StockMovementStatus::PendingValidation->value,
                    reason: $reason,
                );

                return $movement->refresh()->load('product');
            }

            $movement = $this->applyMovement(
                product: $product,
                type: $type,
                direction: $direction,
                quantity: $quantity,
                stockColumn: $stockColumn,
                unitCost: $unitCost,
                reason: $reason,
                user: $user,
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'stock',
                description: "Mouvement stock {$type->label()} validé.",
                subject: $movement,
                newValues: $movement->only(['product_id', 'type', 'quantity', 'status']),
            );

            return $movement->refresh()->load('product');
        });
    }

    public function applyPending(StockMovement $movement, User $user): StockMovement
    {
        return DB::transaction(function () use ($movement, $user): StockMovement {
            $movement = StockMovement::query()
                ->whereKey($movement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($movement->status !== StockMovementStatus::PendingValidation) {
                throw new RuntimeException('Ce mouvement n’est pas en attente de validation.');
            }

            $product = Product::query()
                ->whereKey($movement->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $stockColumn = $this->stockColumnForPending($movement);
            $applied = $this->applyMovement(
                product: $product,
                type: $movement->type,
                direction: $movement->direction,
                quantity: (float) $movement->quantity,
                stockColumn: $stockColumn,
                unitCost: (float) $movement->unit_cost,
                reason: $movement->reason,
                user: $user,
                movement: $movement,
            );

            $this->activityLogger->log(
                action: 'validated',
                module: 'stock',
                description: "Mouvement stock {$movement->type->label()} validé.",
                subject: $applied,
                oldValues: ['status' => StockMovementStatus::PendingValidation->value],
                newValues: ['status' => StockMovementStatus::Validated->value],
            );

            $this->validationHistoryLogger->log(
                document: $applied,
                action: ValidationAction::Validate,
                fromStatus: StockMovementStatus::PendingValidation->value,
                toStatus: StockMovementStatus::Validated->value,
            );

            return $applied->refresh()->load('product');
        });
    }

    private function applyMovement(
        Product $product,
        StockMovementType $type,
        StockMovementDirection $direction,
        float $quantity,
        string $stockColumn,
        float $unitCost,
        string $reason,
        User $user,
        ?StockMovement $movement = null,
    ): StockMovement {
        $before = [
            'physical_before' => (float) $product->physical_stock,
            'reserved_before' => (float) $product->reserved_stock,
            'suspense_before' => (float) $product->suspense_stock,
            'tool_before' => (float) $product->tool_stock,
        ];

        $after = [
            'physical_after' => $before['physical_before'],
            'reserved_after' => $before['reserved_before'],
            'suspense_after' => $before['suspense_before'],
            'tool_after' => $before['tool_before'],
        ];

        $field = $this->afterField($stockColumn);
        $current = (float) $product->{$stockColumn};
        $newValue = $direction === StockMovementDirection::In
            ? $current + $quantity
            : $current - $quantity;

        if ($newValue < 0) {
            throw new RuntimeException('Stock insuffisant : le mouvement rendrait le stock négatif.');
        }

        $after[$field] = $newValue;

        $product->forceFill([
            $stockColumn => $newValue,
        ])->save();

        $attributes = [
            'product_id' => $product->id,
            'type' => $type,
            'direction' => $direction,
            'status' => StockMovementStatus::Validated,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            ...$before,
            ...$after,
            'reason' => $reason,
            'created_by' => $movement?->created_by ?? $user->id,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ];

        if ($movement) {
            $movement->forceFill($attributes)->save();

            return $movement;
        }

        return StockMovement::query()->create($attributes);
    }

    private function directionFor(StockMovementType $type): StockMovementDirection
    {
        return match ($type) {
            StockMovementType::PurchaseEntry,
            StockMovementType::CustomerReturn,
            StockMovementType::PositiveAdjustment,
            StockMovementType::TransferIn => StockMovementDirection::In,
            default => StockMovementDirection::Out,
        };
    }

    private function requiresValidation(StockMovementType $type): bool
    {
        return in_array($type, [
            StockMovementType::PositiveAdjustment,
            StockMovementType::NegativeAdjustment,
            StockMovementType::LossOrDamage,
        ], true);
    }

    private function requiresReason(StockMovementType $type): bool
    {
        return $this->requiresValidation($type)
            || in_array($type, [
                StockMovementType::InternalUse,
                StockMovementType::DeliveryExit,
            ], true);
    }

    private function afterField(string $stockColumn): string
    {
        return match ($stockColumn) {
            'tool_stock' => 'tool_after',
            default => 'physical_after',
        };
    }

    private function stockColumnForPending(StockMovement $movement): string
    {
        return $movement->tool_before !== $movement->tool_after ? 'tool_stock' : 'physical_stock';
    }
}
