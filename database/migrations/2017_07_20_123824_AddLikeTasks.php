<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLikeTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('like_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->usigned()->index();
            $table->string('vk_link');
            $table->string('find_query');
            $table->timestamp('date_post_publication');
            $table->timestamp('post_checked_time')->nullable();
            $table->tinyInteger('notification_mode')->unsigned()->default(2);
            $table->integer('telegram_id')->nullable();
            $table->string('email');
            $table->integer('likes_number');
            $table->integer('reserved')->default(0);
            $table->integer('checked')->default(0);
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
        Schema::drop('like_tasks');
    }
}
