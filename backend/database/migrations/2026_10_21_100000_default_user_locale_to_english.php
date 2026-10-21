<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // English is the default language (product owner, 2026-10-21); existing accounts keep their choice.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('locale', 2)->default('en')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('locale', 2)->default('el')->change();
        });
    }
};
