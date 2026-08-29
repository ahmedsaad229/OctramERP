<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('permissions')->nullable()->after('role_id');
        });

        /*
         * Copy each user's current role permissions.
         * This guarantees that applying this migration does not remove
         * any access the user already has.
         */
        DB::table('users')
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->each(function ($user): void {
                $role = DB::table('roles')
                    ->where('id', $user->role_id)
                    ->first();

                if ($role) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'permissions' => $role->permissions,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('permissions');
        });
    }
};
