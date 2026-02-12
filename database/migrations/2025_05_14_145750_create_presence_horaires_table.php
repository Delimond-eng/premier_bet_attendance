<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('presence_horaires', function (Blueprint $table) {
            $table->id();
            $table->string("libelle"); // ex: "Shift Matin", "Service 24h"
            $table->time("started_at"); // Heure de début prévue
            $table->time("ended_at");   // Heure de fin prévue
            $table->integer("tolerence_minutes")->default(15); // Tolérance avant marquage "Retard"
            $table->unsignedBigInteger("site_id")->nullable(); // Optionnel : spécifique à une station
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->index(['site_id', 'started_at']); // Pour recherche rapide par shift
        });
    }

    public function down()
    {
        Schema::dropIfExists('presence_horaires');
    }
};
