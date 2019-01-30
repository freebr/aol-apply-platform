<?php

use Faker\Generator as Faker;

$factory->define(App\AOLApply::class, function (Faker $faker) {
    return ['status'=>1,
			# ME,MPAcc,MBA,EMBA
			'stu_id'=>$faker->randomElement([22892,25624,25623,25625]),
			'stu_name'=>$faker->name,
			'stu_no'=>$faker->numerify('2012########'),
			'gender'=>$faker->randomElement([1,2]),
			'tutor_id'=>$faker->numberBetween(1,50),
			'speciality'=>$faker->randomElement(['工业工程','物流工程','企业管理','市场营销']),
			'mobile'=>$faker->randomElement(['131','132','137','158','159','176','177','181','182']).
					  $faker->numerify('########'),
			'id_card_no'=>$faker->numerify('######19##########'),
			'bank_account'=>$faker->bankAccountNumber,
			'work_at'=>$faker->randomElement([0,1]),
			'memo'=>$faker->text(10)  
    ];
});
