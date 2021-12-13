<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopDetailThemeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_detail_theme', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_detail_id');
            $table->unsignedBigInteger('theme_id');
            $table->string('effect');
            $table->string('color');
            $table->string('font_family');
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
        Schema::dropIfExists('shop_detail_theme');
    }
}
