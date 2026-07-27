<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajout des champs dans la table users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'matricule_prefix')) {
                $table->string('matricule_prefix')->nullable()->after('station_id');
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }
        });

        // Ajout du champ dans la table agents
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'restrict_station')) {
                $table->boolean('restrict_station')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['matricule_prefix', 'last_seen_at']);
        });
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('restrict_station');
        });
    }
};
