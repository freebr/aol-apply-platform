<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
		Relation::morphMap([
			'aolapplies' => 'App\AOLApply',
			'tutors' =>	'App\Tutor',
			'course_groups' => 'App\CourseGroup',
			'comments' => 'App\Comment'
		]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
