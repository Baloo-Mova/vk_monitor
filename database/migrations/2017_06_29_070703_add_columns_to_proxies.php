<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToProxies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proxies', function ($table) {
            $table->integer('api_id')->after('valid')->nullable();
            $table->string('api_secret_token', 50)->after('api_id')->nullable();
            $table->string('api_service_token', 100)->after('api_secret_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proxies', function ($table) {
            $table->dropColumn('api_id');
            $table->dropColumn('api_secret_token');
            $table->dropColumn('api_service_token');
        });
    }
}
