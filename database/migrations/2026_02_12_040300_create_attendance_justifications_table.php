<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_justifications')) {
            return;
        }

        Schema::create('attendance_justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('presence_agent_id')->nullable()->constrained('presence_agents')->nullOnDelete();
            $table->date('date_reference');
            $table->string('kind'); // retard|absence
            $table->text('justification');
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'date_reference'], 'attendance_just_agent_date_idx');
            $table->index(['kind', 'status'], 'attendance_just_kind_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_justifications');
    }
};

