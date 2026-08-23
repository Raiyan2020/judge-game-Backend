<?php

use App\Enums\BannerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Defaults to `home`, so every row that already exists stays a home
            // banner and GET /banners + GET /home keep returning exactly what
            // they returned before this column existed.
            $table->string('type')
                ->default(BannerType::HOME->value)
                ->after('id')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
