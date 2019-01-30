<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TutorsTableSeeder::class);
        $this->call(CourseGroupsTableSeeder::class);
        $this->call(AOLAppliesTableSeeder::class);
    }
}
