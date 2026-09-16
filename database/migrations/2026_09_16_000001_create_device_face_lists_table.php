<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_face_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('device_imei')->nullable();
            $table->string('request_id')->unique();
            $table->string('command')->default('FACE_LIST');
            $table->string('status')->default('pending');
            $table->json('matricules')->nullable();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('mobile_devices')->nullOnDelete();
            $table->index(['device_imei', 'status']);
            $table->index(['received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_face_lists');
    }
};
