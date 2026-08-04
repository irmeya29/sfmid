<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['proforma_items', 'delivery_note_items', 'invoice_items', 'customer_order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $after = match ($tableName) {
                    'proforma_items' => 'proforma_id',
                    'delivery_note_items', 'customer_order_items' => 'proforma_item_id',
                    default => 'delivery_note_item_id',
                };

                $table->string('item_type')->default('product')->after($after)->index();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE proforma_items DROP FOREIGN KEY proforma_items_product_id_foreign');
            DB::statement('ALTER TABLE delivery_note_items DROP FOREIGN KEY delivery_note_items_product_id_foreign');
            DB::statement('ALTER TABLE invoice_items DROP FOREIGN KEY invoice_items_product_id_foreign');
            DB::statement('ALTER TABLE customer_order_items DROP FOREIGN KEY customer_order_items_product_id_foreign');

            DB::statement('ALTER TABLE proforma_items MODIFY product_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE delivery_note_items MODIFY product_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE invoice_items MODIFY product_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE customer_order_items MODIFY product_id BIGINT UNSIGNED NULL');

            DB::statement('ALTER TABLE proforma_items ADD CONSTRAINT proforma_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT');
            DB::statement('ALTER TABLE delivery_note_items ADD CONSTRAINT delivery_note_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT');
            DB::statement('ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT');
            DB::statement('ALTER TABLE customer_order_items ADD CONSTRAINT customer_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT');
        }
    }

    public function down(): void
    {
        foreach (['proforma_items', 'delivery_note_items', 'invoice_items', 'customer_order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('item_type');
            });
        }
    }
};
