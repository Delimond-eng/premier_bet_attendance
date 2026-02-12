<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('presence_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("agent_id");
            $table->unsignedBigInteger("site_id"); // Station d'affectation
            $table->unsignedBigInteger("gps_site_id")->nullable(); // Station de pointage effective
            $table->unsignedBigInteger("horaire_id"); // Horaire prévu
            $table->timestamp("started_at")->nullable(); // Heure Check-in
            $table->timestamp("ended_at")->nullable(); // Heure Check-out
            $table->string("duree")->nullable();
            $table->string("retard")->default("non");
            $table->string("photos_debut")->nullable();
            $table->string("photos_fin")->nullable();
            $table->text("commentaires")->nullable();
            $table->string("status")->default("arrive");
            $table->date('date_reference'); // Date du shift (utile pour les nuits)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('presence_agents');
    }
};
