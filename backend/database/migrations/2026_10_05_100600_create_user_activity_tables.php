<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Digests are opt-in (GDPR); onboarding step 3 asks explicitly.
            $table->boolean('digest_news_daily')->default(false);
            $table->boolean('digest_news_monthly')->default(false);
            $table->boolean('digest_events_weekly')->default(false);
            $table->boolean('digest_events_monthly')->default(false);
            $table->boolean('event_reminders')->default(true);
            $table->time('delivery_time')->default('08:00:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('bookmarks');
    }
};
