<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrudsTableEasypanel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cruds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('model')->unique();
            $table->string('route')->unique();
            $table->string('icon')->default('fas fa-bars');
            $table->boolean('active')->default(true);
            $table->boolean('built')->default(false);
            $table->boolean('with_acl')->default(false);
            $table->boolean('with_policy')->default(false);
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
        Schema::dropIfExists('cruds');
    }
}
