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
        Schema::create('agent_group_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId("agent_id")->constrained("agents", "id")->cascadeOnDelete();
            $table->foreignId("agent_group_id")->constrained("agent_groups", "id")->cascadeOnDelete();
            $table->date("start_date");
            $table->date("end_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_group_assignments');
    }
};
