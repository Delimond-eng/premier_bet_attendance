<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_authorizations')) {
            return;
        }

        Schema::create('attendance_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->date('date_reference');
            $table->string('type'); // retard|absence|maladie|deuil|autre
            $table->time('started_at')->nullable();
            $table->time('ended_at')->nullable();
            $table->unsignedInteger('minutes')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'date_reference'], 'attendance_auth_agent_date_idx');
            $table->index(['type', 'status'], 'attendance_auth_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_authorizations');
    }
};

