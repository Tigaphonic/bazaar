<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bazaar-owned Audit Trail storage (Story 1.5, AD-18). Mirrors the column
 * shape of spatie/laravel-activitylog's own `activity_log` table, but with a
 * ULID primary key and string morph ids: audited models span host tables
 * with bigint keys (users) and Bazaar-owned tables with ULID keys, which
 * the vendor migration's bigint `nullableMorphs()` columns cannot hold.
 * The vendor migration is never edited (AD-18).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bazaar_audit_trails', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('event')->nullable();
            $table->string('causer_type')->nullable();
            $table->string('causer_id')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'bazaar_audit_trails_subject');
            $table->index(['causer_type', 'causer_id'], 'bazaar_audit_trails_causer');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_audit_trails');
    }
};
