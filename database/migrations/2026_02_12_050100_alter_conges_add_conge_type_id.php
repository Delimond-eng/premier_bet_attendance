<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conges')) {
            return;
        }

        Schema::table('conges', function (Blueprint $table) {
            if (!Schema::hasColumn('conges', 'conge_type_id')) {
                $table->foreignId('conge_type_id')->nullable()->after('agent_id')->constrained('conge_types')->nullOnDelete();
                $table->index(['conge_type_id', 'status'], 'conges_type_status_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conges')) {
            return;
        }

        Schema::table('conges', function (Blueprint $table) {
            if (Schema::hasColumn('conges', 'conge_type_id')) {
                $table->dropForeign(['conge_type_id']);
                $table->dropIndex('conges_type_status_idx');
                $table->dropColumn('conge_type_id');
            }
        });
    }
};

