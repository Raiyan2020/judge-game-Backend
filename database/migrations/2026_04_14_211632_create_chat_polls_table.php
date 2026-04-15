<?php

use App\Models\ChatMessage;
use App\Models\GroupLaw;
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
        Schema::create('chat_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ChatMessage::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(GroupLaw::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('ads');
            $table->json('data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_polls');
    }
};
