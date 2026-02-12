<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conges')) {
            return;
        }

        Schema::create('conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('motif')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'date_debut', 'date_fin'], 'conges_agent_dates_idx');
            $table->index(['status'], 'conges_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conges');
    }
};

