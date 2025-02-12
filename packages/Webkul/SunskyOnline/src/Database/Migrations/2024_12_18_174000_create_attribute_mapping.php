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
        Schema::create('sunsky_attribute_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->string('section');
            $table->json('mapped_value')->nullable();
            $table->json('fixed_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sunsky_attribute_mapping');
    }
};
