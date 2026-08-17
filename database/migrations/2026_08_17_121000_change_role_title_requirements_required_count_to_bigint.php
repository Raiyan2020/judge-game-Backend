<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_title_requirements', function (Blueprint $table) {
            $table->unsignedBigInteger('required_count')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('role_title_requirements', function (Blueprint $table) {
            $table->unsignedInteger('required_count')->nullable()->change();
        });
    }
};
