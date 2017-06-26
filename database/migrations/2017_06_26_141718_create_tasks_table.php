<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //ID задачи,
        // ID пользователя,
        // URL сообщества ВК,
        // ключевое слово для поиска в посте,
        // целевое время выхода поста,
        // режим уведомления (в случае успеха, в случае неудачи, в обоих случаях),
        // ID Telegram,
        // Email,
        // время создания задачи)
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->usigned()->index();
            $table->string('vk_link')->nullable();
            $table->string('find_query')->nullable();
            $table->timestamp('date_post_publication')->nullable();
            $table->tinyInteger('notification_mode')->unsigned()->default(2);
            $table->integer('telegram_id')->nullable();
            $table->string('email')->nullable();
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
        Schema::drop('tasks');
    }
}
