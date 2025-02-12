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
        Schema::create('tvc_mall_product_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('unopim_code');
            $table->string('tvc_mall_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tvc_mall_product_attribute_mappings');
    }
};
