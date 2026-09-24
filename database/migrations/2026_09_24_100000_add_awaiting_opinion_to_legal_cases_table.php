<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part 3 "await-opinion" gate. Records which party the judge is currently
 * awaiting an opinion from, so every further judge action (a second
 * request-opinion, scheduling a hearing, issuing the first-instance ruling) is
 * rejected until that party responds.
 *
 * Values: `defendant_lawyer` | `consultant` | NULL (free to act). Set in
 * LegalCaseService::requestOpinion, cleared in
 * LegalCaseOpinionServices::createOpinion when the awaited role files, and
 * enforced by LegalCaseService::ensureNotAwaitingOpinion. Exposed as
 * `awaiting_opinion` on LegalCaseResource.
 *
 * A plain nullable string column is fully portable (MySQL + the phpunit SQLite
 * driver), so no `DB::getDriverName()` guard is needed in either direction —
 * that guard is only for raw, MySQL-specific DDL. `->after('status')` is a
 * MySQL column-ordering hint that Laravel's SQLite grammar silently ignores.
 *
 * NOTE: this project never runs migrations; it ships for the server to run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('awaiting_opinion')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('awaiting_opinion');
        });
    }
};
