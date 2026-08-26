<?php

use App\Support\ItemNameNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('name_normalized', 255)->nullable()->after('name');
        });

        $seen = [];

        DB::table('items')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$seen): void {
                foreach ($items as $item) {
                    $normalized = ItemNameNormalizer::normalize($item->name);

                    if ($normalized === '' || isset($seen[$normalized])) {
                        continue;
                    }

                    $seen[$normalized] = true;

                    DB::table('items')
                        ->where('id', $item->id)
                        ->update(['name_normalized' => $normalized]);
                }
            });

        Schema::table('items', function (Blueprint $table): void {
            $table->unique('name_normalized', 'items_name_normalized_unique');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropUnique('items_name_normalized_unique');
            $table->dropColumn('name_normalized');
        });
    }
};
