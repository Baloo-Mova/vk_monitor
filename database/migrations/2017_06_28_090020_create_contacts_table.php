<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('accounts_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->tinyInteger('type')->unsigned()->default(0);
            $table->string('smtp_address')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->tinyInteger('valid')->default(1);

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
        //
        Schema::drop('accounts_data');
    }
}
