<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMrstatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mrstatuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('mr_userId');
            $table->text('mr_id');
            $table->text('name');
            $table->text('status');
            $table->text('anchor_url');
            $table->bigint('progress');
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
        Schema::dropIfExists('mrstatuses');
    }
}
