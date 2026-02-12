<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conge_types')) {
            return;
        }

        Schema::create('conge_types', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('actif'); // actif|inactif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conge_types');
    }
};

