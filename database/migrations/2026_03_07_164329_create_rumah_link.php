<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('siakip2_rumah_links', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('title', 256);
            $table->string('url', 1024);
            $table->string('color',25)->default('indigo');
            $table->string('icon',100);
            $table->string('access_type',100);
            $table->string('access_id',100);
            $table->enum('default_pin', ['1', '0'])->default('0');
            $table->string('pembuat_username', 100);
            $table->string('satuan_kerja_tahun_id', 36)->nullable();
            $table->timestamps();

            $table->foreign('pembuat_username', 'link_fk_pembuat_username')->references('username')->on('siakip2_users');
            $table->foreign('satuan_kerja_tahun_id', 'link_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });

        // Tabel Links
        Schema::create('siakip2_user_setting', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('username');
            $table->string('category', 100);
            $table->string('key', 100);
            $table->jsonb('data');
            $table->string('satuan_kerja_tahun_id', 36)->nullable();
            $table->timestamps();

            $table->foreign('username', 'user_setting_fk_username')->references('username')->on('siakip2_users');
            $table->foreign('satuan_kerja_tahun_id', 'user_setting_fk_satuan_kerja_tahun_id')->references('id')->on('siakip2_satuan_kerja_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('links');
        Schema::dropIfExists('groups');
    }
};
