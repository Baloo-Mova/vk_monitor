<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollEmailTelegramToNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('notifications', function ($table) {
            $table->integer('reserved')->after('message')->default(0);
            $table->integer('email_sended')->after('message')->default(0);
            $table->string('email')->after('message')->nullable();

            $table->integer('telegram_sended')->after('message')->default(0);
            $table->integer('telegram_id')->after('message')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('notifications', function ($table) {
            $table->dropColumn('email');
            $table->dropColumn('telegram_sended');
            $table->dropColumn('telegram_id');
            $table->dropColumn('email_sended');

        });
    }
}
