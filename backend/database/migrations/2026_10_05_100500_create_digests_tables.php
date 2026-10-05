<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digests', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('cadence', 16);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('slug', 191)->unique();
            $table->json('title');
            $table->json('intro')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('generated_automatically')->default(false);
            // Set when an editor changes the draft; the generator never overwrites edited drafts.
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'cadence', 'period_start']);
            $table->index(['kind', 'cadence', 'status', 'period_start']);
        });

        Schema::create('digest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digest_id')->constrained()->cascadeOnDelete();
            $table->morphs('itemable');
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('editor_note')->nullable();
            $table->timestamps();

            $table->unique(['digest_id', 'itemable_type', 'itemable_id']);
            $table->index(['digest_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_items');
        Schema::dropIfExists('digests');
    }
};
