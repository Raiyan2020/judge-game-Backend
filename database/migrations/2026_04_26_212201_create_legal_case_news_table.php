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
        Schema::create('legal_case_news', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\LegalCase::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Group::class)->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('type');
            $table->foreignIdFor(\App\Models\User::class,'actor_id')->constrained('users')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\User::class,'subject_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_case_news');
    }
};
