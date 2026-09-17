<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bazaar-owned active/inactive status for the host app's own authenticatable
 * User model (Story 1.4, AD-18 amendment). Never a column on the host's
 * `users` table -- `user_id` is stored as a plain indexed string because the
 * host's user PK type is not guaranteed to be a ULID (pattern identical to
 * `bazaar_user_preferences`, Story 1.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bazaar_user_statuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_user_statuses');
    }
};
