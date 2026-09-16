<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('region_id')->constrained('cities')->nullOnDelete();
            $table->index(['region_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropIndex(['region_id', 'city_id']);
            $table->dropColumn('city_id');
        });
    }
};
