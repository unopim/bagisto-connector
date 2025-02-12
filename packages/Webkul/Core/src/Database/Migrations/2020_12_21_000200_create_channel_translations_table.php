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
        Schema::create('channel_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('channel_id')->unsigned();
            $table->string('locale')->index();
            $table->string('name');

            $table->timestamps();

            $table->unique(['channel_id', 'locale']);
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_translations');
    }
};
