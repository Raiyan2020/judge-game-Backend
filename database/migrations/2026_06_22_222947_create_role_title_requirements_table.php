<?php

use App\Models\RoleAction;
use App\Models\RoleTitle;
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
        Schema::create('role_title_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RoleTitle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(RoleAction::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('required_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_title_requirements');
    }
};
