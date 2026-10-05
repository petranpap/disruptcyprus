<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->string('slug', 191)->unique();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('body');
            $table->json('hero_caption')->nullable();
            // Locales with both title and body, e.g. ["el","en"]. Maintained by the model.
            $table->json('available_locales');
            // Minutes per locale, e.g. {"el":5,"en":4}. Maintained by the model.
            $table->json('reading_time_minutes');
            $table->boolean('is_original')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['section_id', 'status', 'published_at']);
            $table->index(['is_featured', 'published_at']);
        });

        Schema::create('article_industry', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);

            $table->primary(['article_id', 'industry_id']);
            $table->index(['industry_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_industry');
        Schema::dropIfExists('articles');
    }
};
