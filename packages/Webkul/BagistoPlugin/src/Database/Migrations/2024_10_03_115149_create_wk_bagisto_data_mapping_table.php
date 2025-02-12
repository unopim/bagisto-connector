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
        Schema::create('wk_bagisto_data_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->string('related_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('job_instance_id')->nullable();
            $table->unsignedInteger('credential_id')->nullable();
            $table->foreign('credential_id')->references('id')->on('wk_bagisto_credential')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_bagisto_data_mapping');
    }
};
