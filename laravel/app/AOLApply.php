<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Tutor;
use App\CourseGroup;
use App\AOLApplyCourseGroup;
use App\Comment;

class AOLApply extends Model
{
	use SoftDeletes;

	protected $table = 'aolapplies';

	protected $primaryKey = 'id';
	
	protected $fillable = ['status','stu_id','stu_name',
						   'stu_no','gender','tutor_id','school',
						   'speciality','mobile','id_card_no',
	                       'bank_account','work_at','memo','applicant'];

	protected $attributes = ['id' => 0, 'tutor_id' => 0, 'status' => 0, 'school' => '工商管理学院'];
	
	protected $with = ['applicant'];
	
	protected $dates = ['deleted_at'];
	
	public static $STATUS = ['Tutor-Auditing' => 1,
							 'CourseGroup-Auditing' => 2,
							 'Tutor-Audit-Failed' => 3,
							 'Tutor-Audit-Passed' => 4,
							 'All-CourseGroups-Audited' => 5];
	public static $STATUS_INFO = [1=>['info', ['android-person', 'blue'], '待导师审核', '在导师审核前，您可以修改您的申请。'],
								  2=>['info', ['ios-people', 'blue'], '待课程组审核', '在课程组审核前，您可以修改您的申请。'],
								  3=>['error', ['close-circled', 'red'], '导师审核不通过', null],
								  4=>['success', ['checkmark-circled', 'green'], '导师审核通过，待课程组审核', null],
								  5=>['info', ['checkmark-circled', 'blue'], '全部课程组审核完成', '您可以下载申请表的 Word 格式文件并打印纸质版。']];
	
	public function applicant() {
		return $this->belongsTo(StudentUser::class, 'stu_id', 'ID')
					->withDefault(['Name' => '未知']);
	}
	
	public function tutor() {
		return $this->belongsTo(Tutor::class)->withDefault(['name' => '未知']);
	}
	
	public function courseGroups() {
		return $this->belongsToMany(CourseGroup::class,
						'aolapply_course_groups','aolapply_id','course_group_id')
					->withPivot('course_group_teacher_id','course_group_class_count')
					->leftJoin('tutors', 'course_group_teacher_id', '=', 'tutors.id')
					->select('course_groups.*',
						'tutors.name as course_group_teacher_name',
						'tutors.department as course_group_teacher_department')
					->with(['comment' => (function($q) {
							$q->where('aolapply_id', $this->id);
						})])
					->withTimestamps();
	}
	
	public function tutorComment() {
		$tutor = $this->tutor()->firstOrNew([]);
		return $tutor->comment($this->id);
	}
	
	public function courseGroupComments() {
		return $this->hasMany(Comment::class, 'aolapply_id')
				    ->where('commentable_type', 'course_groups')
				    ->with('author');
	}
}

?>