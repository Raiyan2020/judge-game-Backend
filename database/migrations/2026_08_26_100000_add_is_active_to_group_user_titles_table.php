<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M4a — persist the ONE role title (اللقب) a member has chosen to display in
     * a group. `is_active` flags that chosen row; the service keeps a single
     * active title per (group, user). Nullable/default-false so historic rows
     * stay valid and no title is active until the member picks one.
     */
    public function up(): void
    {
        Schema::table('group_user_titles', function (Blueprint $table) {
            $table->boolean('is_active')
                ->nullable()
                ->default(false)
                ->after('role_title_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_user_titles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
