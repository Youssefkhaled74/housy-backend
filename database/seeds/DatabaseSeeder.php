<?php

use Illuminate\Database\Seeder;
use database\Seeders\CouponSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CouponSeeder::class,
            // Other seeders
        ]);
    }
}
