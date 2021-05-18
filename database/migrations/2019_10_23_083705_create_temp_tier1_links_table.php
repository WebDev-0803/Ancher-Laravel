<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempTier1LinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_tier1_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('url');
            $table->text('status');
            $table->text('anchor_text');
            $table->text('anchor_url');
            $table->text('file_size');
            $table->dateTime('processed_at')->nullable();
            $table->bigInteger('temptier1_id');            
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
        Schema::dropIfExists('temp_tier1_links');
    }
}
