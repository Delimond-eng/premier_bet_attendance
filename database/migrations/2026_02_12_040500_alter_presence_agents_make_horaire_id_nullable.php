<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('presence_agents')) {
            return;
        }

        // doctrine/dbal n'est pas installé => alter via SQL brut (MySQL)
        DB::statement('ALTER TABLE presence_agents MODIFY horaire_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('presence_agents')) {
            return;
        }

        DB::statement('ALTER TABLE presence_agents MODIFY horaire_id BIGINT UNSIGNED NOT NULL');
    }
};

