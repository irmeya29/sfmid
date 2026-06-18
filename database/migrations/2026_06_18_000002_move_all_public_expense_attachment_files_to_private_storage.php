<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $privateDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');

        foreach ($publicDisk->allFiles('expense-attachments') as $path) {
            if (! $privateDisk->exists($path)) {
                $privateDisk->put($path, $publicDisk->get($path));
            }

            if ($privateDisk->exists($path)) {
                $publicDisk->delete($path);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
