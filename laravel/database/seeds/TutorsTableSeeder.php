<?php

use Illuminate\Database\Seeder;
use App\TeacherUser;
use App\Tutor;

class TutorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$tutors = TeacherUser::all();
        $users = TeacherUser::whereIn('DepartmentID',[1,2,3,4,5,6,7,12])
							->get()->reject(function($user) use ($tutors) {
			return $tutors->has('account_id', $user->id);
		});
		
		$users->each(function($user) use ($tutors) {
			Tutor::create(
				['account_id'=>$user->ID,
				 'account'=>$user->Account,
				 'name'=>$user->Name,
				 'gender'=>$user->Gender,
				 'department'=>$user->Department]);
		});
    }
}
