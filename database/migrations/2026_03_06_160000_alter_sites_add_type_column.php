<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sites', 'type')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->string('type')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sites', 'type')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};

