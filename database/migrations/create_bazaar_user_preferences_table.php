<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shell-owned per-Staff theme/locale preference (Story 1.2, AD-33). Never a
 * column on the host app's own `users` table (AD-18 amendment) — `user_id`
 * is stored as a plain indexed string because the host's user PK type is not
 * guaranteed to be a ULID.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bazaar_user_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('user_id');
            $table->string('theme')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_user_preferences');
    }
};
