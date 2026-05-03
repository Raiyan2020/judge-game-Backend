<?php

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
        Schema::create('legal_case_judgments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\LegalCase::class)->constrained()->onDelete('cascade');
            $table->string('judgment_type');
            $table->string('stage');
            $table->text('judgment_text');
            $table->foreignIdFor(App\Models\User::class, 'judged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_case_judgments');
    }
};
