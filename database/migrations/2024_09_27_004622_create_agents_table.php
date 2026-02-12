<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string("matricule")->unique();
            $table->string("fullname");
            $table->string("photo")->nullable();
            $table->string("password");
            $table->string("role")->default("agent");
            $table->unsignedBigInteger("site_id")->nullable(); // Station d'affectation
            $table->unsignedBigInteger("groupe_id")->nullable(); // Groupe de rotation
            $table->unsignedBigInteger("horaire_id")->nullable(); // Horaire fixe si pas de rotation
            $table->string("status")->default("actif");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agents');
    }
};
