<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempTier1sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_tier1s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');            
            $table->text('title');
            $table->text('anchor_url');
            $table->mediumText('text');
            $table->float('completed_percent');
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
        Schema::dropIfExists('temp_tier1s');
    }
}
