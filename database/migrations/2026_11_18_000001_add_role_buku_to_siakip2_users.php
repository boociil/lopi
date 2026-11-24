<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role khusus fitur BUKU.
     * Nilai: umum (default), admin_buku, super_admin
     */
    public function up(): void
    {
        if (!Schema::hasColumn('siakip2_users', 'role_buku')) {
            Schema::table('siakip2_users', function (Blueprint $table) {
                $table->enum('role_buku', ['umum', 'admin_buku', 'super_admin'])
                    ->default('umum')
                    ->index();
            });
        }

        // Super admin awal fitur BUKU.
        DB::table('siakip2_users')
            ->whereIn('username', ['misna', 'ryan.ardiansyah'])
            ->update(['role_buku' => 'super_admin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('siakip2_users', 'role_buku')) {
            Schema::table('siakip2_users', function (Blueprint $table) {
                $table->dropColumn('role_buku');
            });
        }
    }
};
