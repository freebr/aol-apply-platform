<?php

use Illuminate\Database\Seeder;
use App\CourseGroup;
use App\Tutor;

class CourseGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$faker = Faker\Factory::create();
        factory(CourseGroup::class, 10)->create()->each(
			function($cg) use ($faker) {
				$members_id = array();
				for($i=0;$i<5;$i++) {
					do {
						$id = $faker->numberBetween(1,100);
					} while($id == $cg->leader_tutor_id or in_array($id, $members_id));
					array_push($members_id, $id);
				}
				$cg->members()->attach($members_id);
			}
		);
    }
}
