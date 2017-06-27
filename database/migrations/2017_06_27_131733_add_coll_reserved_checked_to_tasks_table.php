<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollReservedCheckedToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('tasks', function ($table) {
            $table->integer('reserved')->after('email')->default(0);
            $table->integer('checked')->after('email')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('tasks', function ($table) {
            $table->dropColumn('reserved');
            $table->dropColumn('checked');

        });
    }
}
