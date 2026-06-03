<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockSuspenseFactory extends Factory
{
    public function definition(): array
    {
        $client = Client::factory()->create();
        $product = Product::factory()->create();
        $deliveryNote = DeliveryNote::factory()->create([
            'client_id' => $client->id,
        ]);
        $deliveryNoteItem = DeliveryNoteItem::factory()->create([
            'delivery_note_id' => $deliveryNote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'delivered_quantity' => 2,
        ]);

        return [
            'client_id' => $client->id,
            'product_id' => $product->id,
            'delivery_note_id' => $deliveryNote->id,
            'delivery_note_item_id' => $deliveryNoteItem->id,
            'invoice_id' => null,
            'quantity' => 2,
            'closed_quantity' => 0,
            'status' => 'open',
            'delivered_at' => now(),
            'closed_at' => null,
            'closing_reason' => null,
            'created_by' => User::factory(),
            'closed_by' => null,
        ];
    }
}
