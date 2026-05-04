<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
       Schema::create('siakip2_social_account', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('username');

            $table->string('provider');        // google
            $table->string('provider_user_id');// sub dari google

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();

            $table->unique(['username', 'provider','provider_user_id']);

            $table->foreign('username', 'social_account_fk_username')->references('username')->on('siakip2_users');
        });

        Schema::create('siakip2_calender', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('google_id', 255);

            $table->string('summary');
            $table->string('timeZone')->default('Asia/Makassar');

            $table->string('tipe_parent', 30); // satuan_kerja_tahun, tim
            $table->string('parent_id', 36)->nullable();
        });

        Schema::create('siakip2_calender_member', function (Blueprint $table) {
            $table->string('id', 36)->primary();

             $table->string('username');

            $table->string('calender_id', 36);

            $table->foreign('username', 'calender_member_fk_username')->references('username')->on('siakip2_users');
            $table->foreign('calender_id', 'calender_member_fk_calender_id')->references('id')->on('siakip2_calender');
        });

        Schema::create('siakip2_calender_google_event', function (Blueprint $table) {
            $table->string('id', 36)->primary();

            $table->string('calender_id', 36)->nullable();
            $table->string('event_id', 255);

            $table->string('tipe_event', 30); // satuan_kerja_tahun, tim, user
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('siakip2_calender_google_event');
        Schema::dropIfExists('siakip2_calender_member');
        Schema::dropIfExists('siakip2_calender');
        Schema::dropIfExists('siakip2_social_account');
    }
};
