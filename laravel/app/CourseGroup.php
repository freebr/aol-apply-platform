<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\AOLApply;
use App\Tutor;

class CourseGroup extends Model
{
	use SoftDeletes;
	
	protected $table = 'course_groups';

	protected $primaryKey = 'id';
	
	protected $fillable = ['name','leader_tutor_id'];

	protected $attributes = ['id' => 0, 'name' => '', 'leader_tutor_id' => 0];

	protected $dates = ['deleted_at'];
	
	public function applies() {
		return $this->belongsToMany(AOLApply::class,
						'aolapply_course_groups','course_group_id','aolapply_id')
					->select('aolapplies.*')
					->withPivot('course_group_teacher_id','course_group_class_count')
					->withTimestamps();
	}
	
	public function comment() {
		return $this->morphMany(\App\Comment::class, 'commentable');
	}
	
	public function leaderTutor() {
		return $this->belongsTo(Tutor::class, 'leader_tutor_id')->withDefault(['name' => '未知']);
	}
	
	public function members() {
		return $this->belongsToMany(Tutor::class,
						'course_group_tutors', 'course_group_id', 'tutor_id')
					->select('tutors.*');
	}
	
	public static function names($course_groups) {
		$arr=array();
		foreach($course_groups as $cg) {
			array_push($arr, $cg->name);
		}
		return $arr;
	}
}
