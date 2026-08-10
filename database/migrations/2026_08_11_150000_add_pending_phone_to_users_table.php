<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staging columns for a VERIFIED phone change.
 *
 * The new number lives here — never in `users.phone` — until its code is
 * confirmed, so an unverified or mistyped number can never become the login
 * identity. `users.phone` is only written by the confirm step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_phone')->nullable()->after('phone');
            $table->string('pending_country_code')->nullable()->after('pending_phone');
            $table->string('pending_phone_code')->nullable()->after('pending_country_code');
            // A pending change must age out: a code that is valid forever is a
            // standing takeover opportunity on whichever number was requested.
            $table->timestamp('pending_phone_expires_at')->nullable()->after('pending_phone_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pending_phone',
                'pending_country_code',
                'pending_phone_code',
                'pending_phone_expires_at',
            ]);
        });
    }
};
