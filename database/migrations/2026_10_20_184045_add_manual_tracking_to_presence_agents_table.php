<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('status');
            $table->foreignId('manual_recorded_by')
                ->nullable()
                ->after('is_manual')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            $table->dropForeign(['manual_recorded_by']);
            $table->dropColumn(['manual_recorded_by', 'is_manual']);
        });
    }
};
