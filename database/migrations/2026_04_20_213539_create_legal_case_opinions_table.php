<?php

use App\Models\LegalCase;
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
        Schema::create('legal_case_opinions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LegalCase::class)->constrained()->onDelete('cascade');
            $table->text('opinion');
            $table->text('closing_statements')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_case_opinions');
    }
};
