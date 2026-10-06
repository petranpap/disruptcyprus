<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Denormalized plain text of both locales; utf8mb4_unicode_ci makes matching accent- and case-insensitive (Greek too).
        foreach (['articles', 'events'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->longText('search_text')->collation('utf8mb4_unicode_ci')->nullable();
                $table->fullText('search_text');
            });
        }

        // Hourly view buckets; trending reads the last 48 hours.
        Schema::create('content_view_stats', function (Blueprint $table) {
            $table->id();
            $table->morphs('viewable');
            $table->timestamp('bucket_at');
            $table->unsignedInteger('views')->default(0);

            $table->unique(['viewable_type', 'viewable_id', 'bucket_at']);
            $table->index('bucket_at');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->index(['bookmarkable_type', 'created_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'is_online', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_online', 'starts_at']);
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex(['bookmarkable_type', 'created_at']);
        });

        Schema::dropIfExists('content_view_stats');

        foreach (['articles', 'events'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropFullText(['search_text']);
                $table->dropColumn('search_text');
            });
        }
    }
};
