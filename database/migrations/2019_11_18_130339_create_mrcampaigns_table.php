<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMrcampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mrcampaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('mruser_id');
            $table->text('site_url');
            $table->text('keywords');
            $table->text('title');
            $table->mediumText('body');
            $table->int('body_files');
            $table->text('builder');
            $table->text('profile');
            $table->text('diagram');
            $table->text('accounts');
            $table->text('link_article');
            $table->text('category');
            $table->text('generic');
            $table->text('blog_title');
            $table->text('blog_addr');
            $table->text('youtube_url');
            $table->text('name');
            $table->text('run');
            $table->text('priority');
            $table->text('bulk');
            $table->text('client');
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
        Schema::dropIfExists('mrcampaigns');
    }
}
