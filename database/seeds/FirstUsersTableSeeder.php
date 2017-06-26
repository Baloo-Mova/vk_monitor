<?php

use Illuminate\Database\Seeder;
use App\User;
class FirstUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(User::find(1) == null){
            User::create([
                'name'     => 'admin',
                'password' => bcrypt('admin'),
                'email' => 'admin@admin.com',
                'api_token' =>str_random(60),
            ]);
        }
    }
}
