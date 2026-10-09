<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-launch waitlist: only what we need to send one launch email (GDPR data minimisation).
        Schema::create('waitlist_signups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 191)->unique();
            $table->char('locale', 2)->default('el');
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_signups');
    }
};
