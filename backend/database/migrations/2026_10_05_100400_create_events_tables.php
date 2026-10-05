<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('available_locales');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('Asia/Nicosia');
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 96)->nullable()->index();
            $table->boolean('is_online')->default(false);
            $table->string('online_url', 2048)->nullable();
            $table->string('registration_url', 2048)->nullable();
            $table->string('organizer_name')->nullable();
            $table->json('price_info')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        Schema::create('event_industry', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);

            $table->primary(['event_id', 'industry_id']);
            $table->index(['industry_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_industry');
        Schema::dropIfExists('events');
    }
};
