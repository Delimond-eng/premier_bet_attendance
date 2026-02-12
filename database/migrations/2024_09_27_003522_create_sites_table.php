<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("code")->unique();
            $table->string("adresse");
            $table->string("latlng")->nullable(); // Coordonnées GPS pour validation distance
            $table->string("phone")->nullable();
            $table->integer("presence")->default(1); // Effectif attendu par défaut
            $table->string("status")->default("actif");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sites');
    }
};
