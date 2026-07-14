<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('siakip2_calendar_user', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('google_calendar_id', 255);
            $table->string('username', 100);
            $table->foreign('username', 'calendar_user_fk_username')
                  ->references('username')
                  ->on('siakip2_users')
                  ->onDelete('cascade'); // Hapus data social jika user dihapus
        });

        Schema::create('siakip2_calendar_google_event', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('google_calendar_id', 255);
            $table->string('event_tipe', 100); // jadwal dll
            $table->string('event_id', 255);
            $table->string('username', 100);
            $table->foreign('username', 'calendar_google_event_fk_username')
                  ->references('username')
                  ->on('siakip2_users')
                  ->onDelete('cascade'); // Hapus data social jika user dihapus
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_calendar_google_event');
        Schema::dropIfExists('siakip2_calendar_user');
    }
};
