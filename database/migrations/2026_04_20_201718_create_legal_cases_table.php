<?php

use App\Enums\LegalCaseStatus;
use App\Models\Group;
use App\Models\GroupLaw;
use App\Models\User;
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
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignIdFor(Group::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(GroupLaw::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('status')->default(LegalCaseStatus::NEW->value);
            $table->integer('point_value')->default(0);
            $table->text('final_judgment')->nullable();          
            $table->foreignIdFor(User::class, 'judged_by')->nullable()->constrained('users')->nullOnDelete(); 
            $table->timestamp('judged_at')->nullable();          
            $table->boolean('is_final')->default(false);        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
