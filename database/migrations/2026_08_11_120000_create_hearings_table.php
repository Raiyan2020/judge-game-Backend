<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A scheduled hearing for a case: a datetime, optionally linked to a live
     * audio room. The app's "تحديد جلسة" action had no backend at all before —
     * the button only showed a placeholder. This gives it a real record.
     */
    public function up(): void
    {
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\LegalCase::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(App\Models\Room::class)->nullable()->constrained()->onDelete('set null');
            $table->foreignIdFor(App\Models\User::class, 'created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('scheduled_at');
            // scheduled | done | cancelled
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index('legal_case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearings');
    }
};
