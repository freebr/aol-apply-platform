<?php

use Faker\Generator as Faker;

$factory->define(App\CourseGroup::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
		'leader_tutor_id' => $faker->numberBetween(1,80)
    ];
});
