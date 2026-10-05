<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->json('name');
            // False for "events": it is a menu section backed by the events table, not by articles.
            $table->boolean('has_articles')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->json('name');
            $table->string('group', 32)->index();
            $table->char('color', 7);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('industry_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            // Push notifications for new featured articles in this industry.
            $table->boolean('notify')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'industry_id']);
            $table->index('industry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_user');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('sections');
    }
};
