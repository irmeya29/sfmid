<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $legacySite = DB::table('stock_sites')->where('code', 'SITE-PRINCIPAL')->first();
        $storeSite = DB::table('stock_sites')->where('code', 'MAGASIN-PRINCIPAL')->first();

        if (! $storeSite && $legacySite) {
            DB::table('stock_sites')
                ->where('id', $legacySite->id)
                ->update([
                    'code' => 'MAGASIN-PRINCIPAL',
                    'name' => 'Magasin principal',
                    'description' => 'Site de stockage principal. Vente non autorisee par defaut.',
                    'can_store' => true,
                    'can_sell' => false,
                    'is_default' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
        } elseif (! $storeSite) {
            DB::table('stock_sites')->insert([
                'code' => 'MAGASIN-PRINCIPAL',
                'name' => 'Magasin principal',
                'description' => 'Site de stockage principal. Vente non autorisee par defaut.',
                'can_store' => true,
                'can_sell' => false,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('stock_sites')
                ->where('id', $storeSite->id)
                ->update([
                    'can_store' => true,
                    'can_sell' => false,
                    'is_default' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
        }

        DB::table('stock_sites')
            ->where('code', '<>', 'MAGASIN-PRINCIPAL')
            ->update(['is_default' => false, 'updated_at' => $now]);

        DB::table('stock_sites')->updateOrInsert(
            ['code' => 'VENTE-PRINCIPALE'],
            [
                'name' => 'Vente principale',
                'description' => 'Site de vente principal. Les ventes doivent sortir de ce type de site.',
                'can_store' => true,
                'can_sell' => true,
                'is_default' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('stock_sites')->where('code', 'VENTE-PRINCIPALE')->delete();
    }
};
