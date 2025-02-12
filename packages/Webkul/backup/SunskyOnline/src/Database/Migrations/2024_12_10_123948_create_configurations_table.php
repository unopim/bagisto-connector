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
        Schema::create('sunsky_online_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('baseUrl')->unique();
            $table->string('key');
            $table->string('secret');
            $table->json('additional')->nullable();
            $table->timestamps();

            $table->index('baseUrl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sunsky_online_configurations');
    }
};
