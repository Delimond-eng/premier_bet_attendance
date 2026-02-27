<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('station_id');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->date('date_maintenance');
            $table->text('photo_debut')->nullable();
            $table->text('photo_fin')->nullable();
            $table->string('latlng')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'date_maintenance'], 'maintenance_agents_agent_date_idx');
            $table->index(['station_id', 'date_maintenance'], 'maintenance_agents_station_date_idx');

            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
            $table->foreign('station_id')->references('id')->on('sites')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_agents');
    }
};
