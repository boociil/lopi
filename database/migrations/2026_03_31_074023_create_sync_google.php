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
       Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider');        // google
            $table->string('provider_user_id');// sub dari google

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'provider','provider_user_id']);
        });

        Schema::create('siakip2_sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();

            $table->timestamps();
        });

        Schema::create('google_event_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('google_calendar_id')->constrained('google_calendars')->cascadeOnDelete();

            $table->string('google_event_id');
            $table->string('etag')->nullable(); // version control google

            $table->timestamps();

            $table->unique(['event_id','google_event_id']);
        });

        Schema::create('siakip2_sync_tasl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->boolean('completed')->default(false);
            $table->timestamp('due_at')->nullable();

            $table->timestamps();
        });

        Schema::create('google_task_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('google_task_id');
            $table->string('google_tasklist_id');

            $table->timestamps();

            $table->unique(['task_id','google_task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_google');
    }
};
