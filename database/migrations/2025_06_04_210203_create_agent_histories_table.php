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
        if (!Schema::hasTable('agent_histories')) {
            Schema::create('agent_histories', function (Blueprint $table) {
                $table->id();
                $table->dateTime("date")->nullable();
                $table->unsignedBigInteger("agent_id");
                $table->unsignedBigInteger("site_id");
                $table->unsignedBigInteger("site_provenance_id")->nullable();
                $table->string("status")->default("permenant");
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_histories');
    }
};
