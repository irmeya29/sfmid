<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveReferencedFiles('payments');
        $this->moveReferencedFiles('customer_orders');
        $this->moveDirectory('payment-attachments');
        $this->moveDirectory('customer-orders');
    }

    public function down(): void
    {
        //
    }

    private function moveReferencedFiles(string $table): void
    {
        DB::table($table)
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->select(['id', 'attachment_path'])
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $this->moveFile((string) $row->attachment_path);
                }
            });
    }

    private function moveDirectory(string $directory): void
    {
        foreach (Storage::disk('public')->allFiles($directory) as $path) {
            $this->moveFile($path);
        }
    }

    private function moveFile(string $path): void
    {
        if ($path === '' || Storage::disk('local')->exists($path) || ! Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('local')->put($path, Storage::disk('public')->get($path));

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
};
