<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets `legal_case_news` also carry GROUP-level events (role/permission/law
 * changes) that have no case, actor, or subject — so the unified
 * GroupEventService can write one news row per event. The columns were NOT NULL
 * because every news row used to be case-scoped.
 *
 * Laravel 13 changes columns natively (no doctrine/dbal), on MySQL and sqlite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_case_news', function (Blueprint $table) {
            $table->foreignId('legal_case_id')->nullable()->change();
            $table->foreignId('actor_id')->nullable()->change();
            $table->foreignId('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left nullable on rollback: re-adding NOT NULL would fail on any
        // group-level rows written in the meantime. Intentionally irreversible.
    }
};
