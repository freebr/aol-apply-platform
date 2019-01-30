<?php

use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\ApplyController;
use App\TeacherUser;
use App\StudentUser;
use App\Tutor;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::pattern('type','[0-9]+');
Route::pattern('uid','[0-9]+');
Route::pattern('id','[0-9,]+');
Route::pattern('list_type','[12]');
Route::group(['prefix'=>env('APP_NAME')], function() {
	Route::get('/', function() {
		if (! session()->has('credential')) return view('not-login');
		switch(session('credential')['type']) {
		case 1:
			return Redirect::route('admin');
		case 2:
			return Redirect::route('tutor');
		case 3:
		default:
			return Redirect::route('student');
		}
	})->name('home');
	Route::get('login/{type}/{uid}', ['as'=>'login','uses'=>'PrimaryController@login']);
	Route::get('logout', ['as'=>'logout','uses'=>'PrimaryController@logout']);
	
	// 教务端
	Route::group(['prefix'=>'admin','as'=>'admin'], function() {
		Route::get('/', ['uses'=>'PrimaryController@adminHome']);
		
		// AOL 助教申请
		Route::group(['prefix'=>'apply','as'=>'.apply'], function() {
			Route::get('/', ['uses'=>'ApplyController@listAppliesForAdmin']);
			Route::get('export-list', ['as'=>'.export-list','uses'=>'ApplyController@exportApplyList']);
			Route::group(['prefix'=>'{id}'], function() {
				Route::get('/', ['as'=>'.show','uses'=>'ApplyController@showApply']);
				Route::post('edit', ['as'=>'.update','uses'=>'ApplyController@updateApply']);
				Route::any('drop', ['as'=>'.drop','uses'=>'ApplyController@dropApply']);
				Route::get('export', ['as'=>'.export','uses'=>'ApplyController@exportApply']);
			});
		});
		
		// 课程组
		Route::group(['prefix'=>'course-group','as'=>'.course-group'], function() {
			Route::get('/', ['uses'=>'CourseGroupController@listCourseGroups']);
			Route::get('new', ['as'=>'.new','uses'=>'CourseGroupController@showCourseGroup']);
			Route::post('add', ['as'=>'.add','uses'=>'CourseGroupController@updateCourseGroup']);
			Route::group(['prefix'=>'{id}'], function() {
				Route::get('/', ['as'=>'.show','uses'=>'CourseGroupController@showCourseGroup']);
				Route::post('edit', ['as'=>'.update','uses'=>'CourseGroupController@updateCourseGroup']);
				Route::any('drop', ['as'=>'.drop','uses'=>'CourseGroupController@dropCourseGroup']);
			});
		});
	});
	
	// 导师端
	Route::group(['prefix'=>'tutor','as'=>'tutor'], function() {
		Route::get('/', ['uses'=>'PrimaryController@tutorHome']);
		
		// AOL 助教申请
		Route::get('apply-{list_type}', ['as'=>'.apply-type','uses'=>'ApplyController@listAppliesForTutor']);
		Route::group(['prefix'=>'apply','as'=>'.apply'], function() {
			Route::get('/', ['uses'=>'ApplyController@listAppliesForTutor']);
			Route::group(['prefix'=>'{id}'], function() {
				Route::get('/', ['as'=>'.show','uses'=>'ApplyController@showApply']);
				Route::post('audit', ['as'=>'.audit','uses'=>'ApplyController@auditApply']);
				Route::get('print', ['as'=>'.print','uses'=>'ApplyController@printApply']);
			});
		});
		
		// 课程组
		Route::group(['prefix'=>'course-group','as'=>'.course-group'], function() {
			Route::get('/', ['uses'=>'CourseGroupController@listCourseGroups']);
			Route::group(['prefix'=>'{id}'], function() {
				Route::get('/', ['as'=>'.show','uses'=>'CourseGroupController@showCourseGroup']);
			});
		});
	});
	
	// 学生端
	Route::group(['prefix'=>'student','as'=>'student'], function() {
		Route::get('/', ['uses'=>'PrimaryController@studentHome']);
		
		// AOL 助教申请
		Route::group(['prefix'=>'apply','as'=>'.apply'], function() {
			Route::get('/', ['uses'=>'ApplyController@showApply']);
			Route::get('new', ['as'=>'.new', function(){return with(new ApplyController)->showApply(request(),null,true);}]);
			Route::get('refill', ['as'=>'.refill', function(){return with(new ApplyController)->showApply(request(),null,false,true);}]);
			Route::post('modify', ['as'=>'.modify', 'uses'=>'ApplyController@modifyApply']);
			Route::post('modify-refill', ['as'=>'.modify-refill', 'uses'=>'ApplyController@refillApply']);
			Route::get('export', ['as'=>'.export','uses'=>'ApplyController@exportApply']);
		});
	});
	
	// API
	Route::group(['prefix'=>'api','as'=>'api'], function() {
		Route::get('search-tutor/{keyword}', ['as'=>'.search-tutor','uses'=>'PrimaryController@searchTutor']);
		Route::get('search-course-group/{keyword}', ['as'=>'.search-course-group','uses'=>'PrimaryController@searchCourseGroup']);
	});
});