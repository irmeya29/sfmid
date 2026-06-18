<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('expenses')
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->chunkById(100, function ($expenses): void {
                foreach ($expenses as $expense) {
                    $path = $expense->attachment_path;

                    if (! is_string($path) || $path === '') {
                        continue;
                    }

                    $privateDisk = Storage::disk('local');
                    $publicDisk = Storage::disk('public');

                    if (! $publicDisk->exists($path)) {
                        continue;
                    }

                    if (! $privateDisk->exists($path)) {
                        $privateDisk->put($path, $publicDisk->get($path));
                    }

                    if ($privateDisk->exists($path)) {
                        $publicDisk->delete($path);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
