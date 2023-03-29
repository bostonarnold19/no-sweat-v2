<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\User\Entities\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::firstOrCreate([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password'=> bcrypt('admin'),
            'remember_token' => Str::random(10),
        ]);

        // event(new Registered($user));

        // User::factory(100)->create();
    }
}
