<?php

use Illuminate\Database\Seeder;

class AOLAppliesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$faker = Faker\Factory::create();
        factory(App\AOLApply::class, 50)->create()->each(
			function($apply) use ($faker) {
				$apply->courseGroups()->attach(
					 $faker->numberBetween(1,10),
					 ['course_group_teacher_id'=>$faker->numberBetween(1,50),
					 'course_group_class_count'=>$faker->numberBetween(1,10)]);
			}
		);
    }
}
