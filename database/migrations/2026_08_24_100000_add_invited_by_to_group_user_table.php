<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * N5 — record who sent each group invitation on the group_user pivot, so
     * accept/reject notifies the real inviter (not the group judge/owner) and
     * the invitee can see who invited them. Nullable + nullOnDelete so historic
     * rows and deleted inviters stay valid.
     */
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->foreignId('invited_by')
                ->nullable()
                ->after('title')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
        });
    }
};
