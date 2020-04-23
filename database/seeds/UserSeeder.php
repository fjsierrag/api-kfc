<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection("sqlite")->table('users')->insert(
            [
                'name' => "Francisco Sierra",
                'email' => "francisco.sierra@kfc.com.ec",
                'password' => Hash::make("solo3sLacl@v3"),
            ]
        );
    }
}
