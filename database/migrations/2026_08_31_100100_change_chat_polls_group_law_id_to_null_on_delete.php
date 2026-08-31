<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * chat_polls.group_law_id was cascadeOnDelete, so a carried DELETE_LAW vote
     * ($law->delete()) also destroyed the poll + its votes — an orphaned poll
     * message, a lost audit trail, and no card outcome to show. Re-point the FK
     * to nullOnDelete: deleting a law now NULLs group_law_id but keeps the poll
     * row (with its `result`) intact.
     *
     * The original column/FK: foreignIdFor(GroupLaw)->nullable()->constrained()
     * ->cascadeOnDelete() → column `group_law_id`, FK references group_laws.id
     * under the conventional name `chat_polls_group_law_id_foreign`.
     */
    public function up(): void
    {
        Schema::table('chat_polls', function (Blueprint $table) {
            $table->dropForeign(['group_law_id']);
            $table->foreign('group_law_id')
                ->references('id')->on('group_laws')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse: restore the original cascadeOnDelete FK.
     */
    public function down(): void
    {
        Schema::table('chat_polls', function (Blueprint $table) {
            $table->dropForeign(['group_law_id']);
            $table->foreign('group_law_id')
                ->references('id')->on('group_laws')
                ->cascadeOnDelete();
        });
    }
};
