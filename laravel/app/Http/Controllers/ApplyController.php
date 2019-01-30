<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\IOFactory as Spreadsheet_IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Image;
use PhpOffice\PhpWord\IOFactory as Word_IOFactory;
use App\AOLApply;
use App\Tutor;
use App\CourseGroup;
use App\Comment;

class ApplyController extends Controller
{
	const countListApply=20;
	const rewardPerClass=500;
	
	// 对指定的申请列表查询进行指定的状态过滤
	private function filterApply($query, $filter_type) {
		switch ($filter_type)  {
			case 'auditing': $is_pass = null; break;
			case 'passed': $is_pass = true; break;
			case 'failed': $is_pass = false; break;
			default: $is_pass = null; break;
		}
		if (isset($filter_type)) {
			if (null === $is_pass) {
				$query->whereDoesntHave('courseGroups.comment');
			} else {
				$query->whereHas('courseGroups.comment',
					function($q) use ($is_pass) { $q->where('is_pass', $is_pass)->whereRaw('aolapply_id = aolapplies.id'); });
			}
		}
		return;
	}
	
	// 显示申请列表
	private function listApplies(Request $request, $query, $list_type = 0) {
		$is_admin = session('credential')['user']->IsAdmin;
		$countApply = $query->count();
		$applies = (clone $query)->paginate(self::countListApply);
		if ($is_admin) {
			$countUnhandledApply = $query->whereIn('status',
				[AOLApply::$STATUS['Tutor-Auditing'],
				 AOLApply::$STATUS['CourseGroup-Auditing'],
				 AOLApply::$STATUS['Tutor-Audit-Passed']])->count();
		} else {
			$tutor_id = session('credential')['tutor']->id;
			if ($list_type == 1) {
				$countUnhandledApply = $query->where('status', AOLApply::$STATUS['Tutor-Auditing'])->count();
			} else {
				$countUnhandledApply = AOLApply::whereHas('courseGroups',
					function($q) use ($tutor_id) {
						$q->where('course_group_teacher_id', $tutor_id)
						  ->whereDoesntHave('comment', function($qq) {
							  $qq->whereRaw('aolapply_id = aolapplies.id');
						  });
					})->whereIn('status', [AOLApply::$STATUS['CourseGroup-Auditing'], AOLApply::$STATUS['Tutor-Audit-Passed']])->count();
			}
		}
		$currentPage = request()->input('page')?:session('apply-list.current_page')?:1;
		session()->put('apply-list.current_page', $currentPage);
		
		$arr_columns = ['stu_no'=>['学号',130,'left'],'stu_name'=>['姓名',90,'left'],
					    'bank_account'=>['工商银行账号',180],
					    'course_groups'=>['课程组名称',350],
					    'class_counts'=>['课程组教学班数量',80],
					    'rewards'=>['应发酬金（税前）',100]];
		$arr_applies = array();
		foreach($applies as $apply) {
			$course_groups = $apply->courseGroups();
			switch ($list_type) {
			case 0:
				$filter_type = $request->input('filter');
				switch ($filter_type) {
					case 'auditing': $is_pass = null; break;
					case 'passed': $is_pass = true; break;
					case 'failed': $is_pass = false; break;
					default: $is_pass = null; break;
				}
				if (isset($filter_type)) {
					if (null === $is_pass) {
						$course_groups->whereDoesntHave('comment');
					} else {
						$course_groups->whereHas('comment',
							function ($q) use ($apply, $is_pass) {
								$q->where('is_pass', $is_pass)->where('aolapply_id', $apply->id);
							});
					}
				}
				break;
			case 1: break;
			case 2: default:
				$course_groups->where('course_group_teacher_id', $tutor_id);
				break;
			}
			$tutor_comment = null;
			if ($apply->tutor_id != 0) {
				$comment = $apply->tutorComment()->first();
				if (! isset($comment)) {
					$type = 0;
					$name = '导师审核中';
				} elseif (0 == $comment->is_pass) {
					$type = 1;
					$name = '导师审核不通过';
				} elseif (1 == $comment->is_pass) {
					$type = 2;
					$name = '导师审核通过';
				}
				$tutor_comment = "{type:$type,name:'$name'},";
			}
			
			$arr_cg = array();
			$arr_cg_comment = array();
			$cg_count = $course_groups->count();
			foreach($course_groups->get() as $index => $cg) {
				$prefix = $cg_count>1 ? '('.($index+1).')' : '';
				array_push($arr_cg, ['name' => $prefix.$cg->name,
					'class_count' => $prefix.$cg->pivot->course_group_class_count,
					'reward' => $prefix.self::rewardPerClass * $cg->pivot->course_group_class_count]);
				$comment = $cg->comment->first();
				$name = $cg_count>1 ? $prefix : '课程组';
				if (! isset($comment)) {
					$type = 0;
					$name .= '审核中';
				} elseif (0 == $comment->is_pass) {
					$type = 1;
					$name .= '审核不通过';
				} elseif (1 == $comment->is_pass) {
					$type = 2;
					$name .= '审核通过';
				}
				array_push($arr_cg_comment, "{type:$type,name:'$name'}");
			}
			$group_names = implode('\n', array_pluck($arr_cg, 'name'));
			$class_counts = implode('\n', array_pluck($arr_cg, 'class_count'));
			$rewards = implode('\n', array_pluck($arr_cg, 'reward'));
			array_push($arr_applies,'{'.
				'id:'.$apply->id.','.
				'stu_no:'.format_json($apply->stu_no).','.
				'stu_name:'.format_json($apply->stu_name).','.
				'bank_account:'.format_json($apply->bank_account).','.
				'course_groups:'.format_json($group_names).','.
				'class_counts:'.format_json($class_counts).','.
				'rewards:'.format_json($rewards).','.
				'status:['.$tutor_comment.implode(',',$arr_cg_comment).']}'
			);
		}
		$arr_title_postfix = ['','（导师审核）','（课程组审核）'];
		$params = [
			'is_admin' => $is_admin,
			'countApply' => $countApply,
			'countUnhandledApply' => $countUnhandledApply,
			'countListApply' => self::countListApply,
			'filter_type' => $request->input('filter'),
			'currentPage' => $currentPage,
			'arr_columns' => '['.list_selection_column_def().','.implode(',',
				array_map(function($key) use ($arr_columns) {
					if(is_array($arr_columns[$key])) {
						$title=$arr_columns[$key][0];
						$width=$arr_columns[$key][1];
						$fixed=count($arr_columns[$key]) > 2 ? $arr_columns[$key][2] : null;
						return "{'title': '$title', 'key': '$key', 'width': $width, align: 'center'".
							(! isset($fixed) ? '' : ", 'fixed': '$fixed'").'}';
					} else {
						$title=$arr_columns[$key];
						return "{'title': '$title', 'key': '$key', align: 'center'}";
					}
				},array_keys($arr_columns))).','.
				apply_list_status_column_def().','.
				apply_list_action_column_def($is_admin).
				']',
			'arr_applies'=>'['.implode(',',$arr_applies).']',
			'title_postfix'=>$arr_title_postfix[$list_type]
		];
		return view('apply-list', $params);
	}
	
	// 教务端显示申请列表
	public function listAppliesForAdmin(Request $request) {
		$query = AOLApply::orderBy('status')->orderBy('updated_at', true);
		$filter_type = $request->input('filter');
		$this->filterApply($query, $filter_type);
		
		return $this->listApplies($request, $query);
	}
	
	// 导师端显示申请列表
	public function listAppliesForTutor(Request $request, $list_type = 1) {
		$tutor_id = session('credential')['tutor']->id;
		if (isset($list_type)) {
			session()->put('apply_list_type', $list_type);
		} else {
			$list_type = session('apply_list_type');
		}
		if ($list_type == 1) {	// 导师审核
			$query = AOLApply::where('tutor_id', $tutor_id)->orderBy('status')->orderBy('updated_at', true);
		} else {				// 课程组审核
			$query = AOLApply::whereHas('courseGroups',
				function($q) use ($tutor_id) {
					$q->where('course_group_teacher_id', $tutor_id);
				})->orderBy('status')->orderBy('updated_at', true);
		}
		return $this->listApplies($request, $query, $list_type);
	}
	
	// 显示申请详情
	public function showApply(Request $request, $id = null, $new = false, $refill = false) {
		$user = session('credential')['user'];
		$user_type = session('credential')['type'];
		
		if($user_type == 3) {
			// 学生用户，查找数据库是否有申请记录
			if ($new) {
				$apply = null;
			} else {
				if ($refill) {
					$apply = AOLApply::where('stu_id', $user->ID)->first();
					$apply->load(['courseGroups' => function($q) use ($apply) {
							$q->whereHas('comment', function($qq) use ($apply) {
								$qq->where('is_pass', false)->where('aolapply_id', $apply->id);
							})->with('comment');
						}]);
				} else {
					$apply = AOLApply::where('stu_id', $user->ID)->first();
				}
			}
			if(! isset($apply)) {
				$apply = new AOLApply(['applicant' => $user,
									   'stu_name' => $user->Name,
									   'stu_no' => $user->Account]);
			}
		} else {
			// 教务员和导师用户，按 ID 查找记录
			$apply = AOLApply::find($id);
			if(! isset($apply)) {
				return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
			}
		}
		
		$is_tutor = false;
		$is_course_group_teacher = false;
		$tutor_id = 0;
		if($user_type == 2) {
			$tutor_id = session('credential')['tutor']->id;
			if ($tutor_id == $apply->tutor_id) {
				// 导师审核
				$is_tutor = true;
			}
			if ($apply->courseGroups()->where('course_group_teacher_id', $tutor_id)->count()) {
				// 课程组审核
				$is_course_group_teacher = true;
			}
			if (!($is_tutor || $is_course_group_teacher)) {
				return view('error',['error_desc'=>'您没有审核该申请的权限。']);
			}
			if ($is_tutor && $is_course_group_teacher) {
				// 同时为导师和课程组教师的情况
				switch ($apply->status) {
				case AOLApply::$STATUS['Tutor-Auditing']:
				case AOLApply::$STATUS['Tutor-Audit-Failed']:
					// 导师待审核或审核不通过时，按导师显示
					$is_course_group_teacher = false;
					break;
				case AOLApply::$STATUS['Tutor-Audit-Passed']:
					// 导师审核通过时，判断导师的课程组审核状态
					if ($apply->courseGroups()->where('course_group_teacher_id', $tutor_id)
							  ->whereDoesntHave('comment', function($query) use ($apply) {
								  $query->where('aolapply_id', $apply->id); })->count()) {
						// 导师的课程组没有全部给出意见，按课程组教师显示
						$is_tutor = false;
					} else {
						// 导师的课程组已全部给出意见，按导师显示
						$is_course_group_teacher = false;
					}
				default: break;
				}
			}
			if (! $is_tutor) {
				$apply->load([
					'courseGroups' => function($query) use ($tutor_id) {
						$query->where('course_group_teacher_id', $tutor_id);
					},
					'courseGroupComments' => function($query) use ($tutor_id) {
						$query->where('author_id', $tutor_id);
					}
				]);
			}
		}
		$route_action = array('',
			route('admin.apply.update', ['id'=>$id]),
			route('tutor.apply.audit', ['id'=>$id]),
			route($refill?'student.apply.modify-refill':'student.apply.modify'))[$user_type];
		
		$cg_comment_count = $apply->courseGroupComments()->count();
		$has_failed = $apply->id && $apply->courseGroups()->whereHas('comment',function($q) use ($apply) {
			$q->where('is_pass', false)->where('aolapply_id', $apply->id);
		})->count() > 0;
		$locked = $user_type == 2 ||
			$user_type == 3 && $apply->id != 0 && (in_array($apply->status,
			[AOLApply::$STATUS['Tutor-Audit-Passed'],AOLApply::$STATUS['Tutor-Audit-Failed']]) ||
			$cg_comment_count > 0);
		return view('apply-detail',
			['apply' => $apply,
			 'new' => $apply->id == 0,
			 'refill' => $refill,
			 'cg_comment_count' => $cg_comment_count,
			 'has_failed' => $has_failed,
			 'tutor_id' => $tutor_id,
			 'user_type' => $user_type,
			 'is_tutor' => $is_tutor,
			 'is_course_group_teacher' => $is_course_group_teacher,
			 'tutor' => $apply->tutor()->first(),
			 'route_action' => $route_action, 'locked' => $locked,
			 'route_export' => $user_type == 1 ? route('admin.apply.export', ['id'=>$id]) : route('student.apply.export'),
			 'max_course_group_count' => 10, 'STATUS' => AOLApply::$STATUS]);
	}
	
	// 教务端更新申请信息
	public function updateApply(Request $request, $id) {
		$apply = AOLApply::find($id);
		if (! isset($apply)) {
			return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
		}
		
		$new_status = $apply->status;
		
		if ($apply->tutor_id) {
			$comment = $apply->tutorComment();
			if (null===$request->get('tutor_comment')) {
				// 意见为空，删除现有的导师意见
				$comment->delete();
				$new_status = AOLApply::$STATUS['Tutor-Auditing'];
			} else {
				$comment->updateOrCreate(
					['aolapply_id' => $apply->id],
					['author_id' => $apply->tutor_id,
					 'content' => $request->get('tutor_comment'),
					 'is_pass' => '1'==$request->get('tutor_result')]);
				$new_status = '1'==$request->get('tutor_result') ? AOLApply::$STATUS['Tutor-Audit-Passed'] : AOLApply::$STATUS['Tutor-Audit-Failed'];
			}
		}
		
		$arr_cg_ids = $request->get('course_groups');
		if (count($arr_cg_ids)) {
			$course_groups_info = array();
			$arr_cg_teacher = $request->get('course_group_teachers');
			$arr_cg_class_count = $request->get('course_group_class_counts');
			array_walk($arr_cg_ids,
				function($cg, $index) use (&$course_groups_info, $arr_cg_teacher, $arr_cg_class_count) {
					$course_groups_info[$cg] = ['course_group_teacher_id' => $arr_cg_teacher[$index],
												'course_group_class_count' => $arr_cg_class_count[$index]];
				}
			);
			
			$apply->courseGroups()->sync($course_groups_info);
			$apply->courseGroupComments()->whereNotIn('commentable_id', $arr_cg_ids)
				  ->forceDelete();
			
			$course_group_comments = $request->get('course_group_comments');
			$course_group_results = $request->get('course_group_results');
			array_walk($arr_cg_ids,
				function($cg_id, $index)
					use ($apply, $arr_cg_teacher, $course_group_comments, $course_group_results, $new_status) {
					$comments = $apply->courseGroupComments();
					if (null==$course_group_comments[$index]) {
						// 意见为空，删除现有的课程组意见
						$comments->where('commentable_id', $cg_id)
								 ->delete();
						$new_status = $apply->tutor_id ? AOLApply::$STATUS['Tutor-Audit-Passed'] : AOLApply::$STATUS['CourseGroup-Auditing'];
					} else {
						$comments->updateOrCreate(
							['aolapply_id' => $apply->id,
							 'commentable_id' => $cg_id,
							 'commentable_type' => 'course_groups'],
						    ['author_id' => $arr_cg_teacher[$index],
							 'content' => $course_group_comments[$index],
							 'is_pass' => '1' == $course_group_results[$index]]);
					}
				}
			);
			
			if (AOLApply::where('id', $id)
						->whereHas('courseGroups',function($q) use ($id) {
							$q->whereDoesntHave('comment',function($qq) use ($id) {
								$qq->where('aolapply_id', $id);
							});
				})->count()==0) {
				$new_status = AOLApply::$STATUS['All-CourseGroups-Audited'];
			}
		}
		
		if ($apply->status != $request->get('status')) {
			$new_status = $request->get('status');
		}
		
		$apply->update(['stu_name'=>$request->get('stu_name'),
						'stu_no'=>$request->get('stu_no'),
						'gender'=>array_search('on', $request->get('gender')),
						'tutor_id'=>$request->get('tutor') ?: 0,
						'mobile'=>$request->get('mobile'),
						'id_card_no'=>$request->get('id_card_no'),
						'bank_account'=>$request->get('bank_account'),
						'school'=>$request->get('school'),
						'speciality'=>$request->get('speciality'),
						'work_at'=>array_search('on', $request->get('work_at')),
						'memo'=>$request->get('memo'),
						'status'=>$new_status
						]);
		
		setMessage('success','修改成功');
		return redirect()->route('admin.apply.show', ['id'=>$id]);
	}
	
	// 导师端审核申请信息
	public function auditApply(Request $request, $id) {
		$apply = AOLApply::find($id);
		if(! isset($apply)) {
			return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
		}
		
		$tutor_id = session('credential')['tutor']->id;
		$is_tutor = false;
		$is_course_group_teacher = false;
		if ($tutor_id == $apply->tutor_id) {
			$is_tutor = true;
		}
		$apply->load(['courseGroups' => function($query) use ($tutor_id) {
			$query->where('course_group_teacher_id', $tutor_id);
		}]);
		if ($apply->courseGroups->count()) {
			$is_course_group_teacher = true;
		}
		if (!($is_tutor || $is_course_group_teacher)) {
			return view('error',['error_desc'=>'您没有审核该申请的权限。']);
		}
		if ($is_tutor && $is_course_group_teacher) {
			// 同时为导师和课程组教师的情况
			switch ($apply->status) {
			case AOLApply::$STATUS['Tutor-Auditing']:
			case AOLApply::$STATUS['Tutor-Audit-Failed']:
				// 导师待审核或审核不通过时，按导师显示
				$is_course_group_teacher = false;
				break;
			case AOLApply::$STATUS['Tutor-Audit-Passed']:
			case AOLApply::$STATUS['All-CourseGroups-Audited']:
				// 导师审核通过或全部课程组审核完成时，以课程组教师身份审核
				$is_tutor = false;
			default: break;
			}
		}
		
		if ($is_tutor) {
			// 导师审核
			$audit_pass = $request->get('audit_pass') == 'pass';
			if (null!==$request->get('tutor_comment')) {
				$comment = $apply->tutorComment()->updateOrCreate(
										 ['aolapply_id' => $apply->id],
										 ['author_id' => $tutor_id,
										  'content' => $request->get('tutor_comment'),
										  'is_pass' => $audit_pass]);
				$apply->update(['status' => $audit_pass ?
								AOLApply::$STATUS['Tutor-Audit-Passed'] :
								AOLApply::$STATUS['Tutor-Audit-Failed']]);
			}
		}
		if ($is_course_group_teacher) {
			// 课程组审核
			$course_group_comments = $request->get('course_group_comments');
			$course_group_results = $request->get('course_group_results');
			$comments = $apply->courseGroupComments();
			foreach($apply->courseGroups as $index=>$cg) {
				if (null===$course_group_comments[$index]) continue;
				$comments->updateOrCreate(
					['aolapply_id' => $id,
					 'commentable_id' => $cg->id,
					 'commentable_type' => 'course_groups'],
					['author_id' => $tutor_id,
					 'content' => $course_group_comments[$index],
					 'is_pass' => '1' == $course_group_results[$index]]);
			}
			
			if (AOLApply::where('id', $id)
						->whereHas('courseGroups',function($q) use ($id) {
							$q->whereDoesntHave('comment',function($qq) use ($id) {
								$qq->where('aolapply_id', $id);
							});
				})->count()==0) {
				$apply->update(['status' => AOLApply::$STATUS['All-CourseGroups-Audited']]);
			}
		}
		
		setMessage('success','审核成功');
		return redirect()->route('tutor.apply.show', ['id'=>$id]);
	}
	
	// 学生端提交/修改申请信息
	public function modifyApply(Request $request) {
		$user = session('credential')['user'];
		$params = ['stu_id'=>$user->ID,
				   'stu_name'=>$request->get('stu_name'), #$user->Name,
				   'stu_no'=>$request->get('stu_no'), #$user->Account,
				   'gender'=>array_search('on', $request->get('gender')),
				   'tutor_id'=>$user->StudentType == 2 ? $request->get('tutor') : 0,
				   'mobile'=>$request->get('mobile'),
				   'id_card_no'=>strtoupper($request->get('id_card_no')),
				   'bank_account'=>$request->get('bank_account'),
				   'school'=>$request->get('school'),
				   'speciality'=>$request->get('speciality'),
				   'work_at'=>array_search('on', $request->get('work_at')),
				   'memo'=>$request->get('memo')
				  ];
		if (session('credential')['user']->StudentType == 1) {
			$params['status'] = AOLApply::$STATUS['CourseGroup-Auditing'];
		} else {
			$params['status'] = AOLApply::$STATUS['Tutor-Auditing'];
		}
		$apply = AOLApply::where('stu_id', $user->ID)->first();
		if(isset($apply)) {
			$apply->update($params);
		} else {
			$apply = AOLApply::create($params);
		}
		
		$arr_cg_ids = $request->get('course_groups');
		if(count($arr_cg_ids)>0) {
			$course_groups_info = array();
			$arr_cg_teacher = $request->get('course_group_teachers');
			$arr_cg_class_count = $request->get('course_group_class_counts');
			array_walk($arr_cg_ids,
				function($cg, $index) use (&$course_groups_info, $arr_cg_teacher, $arr_cg_class_count) {
					$course_groups_info[$cg] = ['course_group_teacher_id' => $arr_cg_teacher[$index],
												'course_group_class_count' => $arr_cg_class_count[$index]];
				}
			);
			
			$apply->courseGroups()->sync($course_groups_info);
			$apply->courseGroupComments()->whereNotIn('commentable_id', $arr_cg_ids)
				  ->forceDelete();
		}
		
		setMessage('success', $apply->wasRecentlyCreated ? '提交成功' : '修改成功');
		return redirect()->route('student.apply');
	}
	
	// 学生端修改申请信息（重选课程组）
	public function refillApply(Request $request) {
		$user = session('credential')['user'];
		$apply = AOLApply::where('stu_id', $user->ID)->first();
		if(! isset($apply)) {
			return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
		}
		$status = $apply->tutor_id ? AOLApply::$STATUS['Tutor-Audit-Passed'] : AOLApply::$STATUS['CourseGroup-Auditing'];
		$apply->update(['memo'=>$request->get('memo'),
						'status' => $status]);
		
		$arr_cg_ids = $request->get('course_groups');
		if(count($arr_cg_ids)>0) {
			// 检测所选是否存在已通过的课程组
			$course_groups_passed = $apply->courseGroups()->whereHas('comment',
				function($q) use ($apply) { $q->where('is_pass', true)->where('aolapply_id', $apply->id); })
				->whereIn('course_groups.id', $arr_cg_ids)->get();
			if ($course_groups_passed->count()) {
				// 存在
				$arr_name = array();
				$course_groups_passed->each(function ($cg) use (&$arr_name) {
					array_push($arr_name, $cg->name);
				});
				return view('error', ['error_desc' =>
					"以下课程组已经审核通过您的申请，不能再次选择！\n".implode('、', $arr_name)]);
			}
			
			/*
			// 检测所选是否存在不通过的课程组
			$course_groups_failed = $apply->courseGroups()->whereHas('comment',
				function($q) use ($apply) { $q->where('is_pass', false)->where('aolapply_id', $apply->id); })
				->whereIn('course_groups.id', $arr_cg_ids)->get();
			if ($course_groups_failed->count()) {
				// 存在
				$arr_name = array();
				$course_groups_failed->each(function ($cg) use (&$arr_name) {
					array_push($arr_name, $cg->name);
				});
				return view('error', ['error_desc' =>
					"以下课程组已经审核不通过您的申请，不能再次选择！\n".implode('、', $arr_name)]);
			}
			*/
			
			// 删除与审核不通过的课程组的关联
			$course_groups_failed = $apply->courseGroups()->whereHas('comment',
				function($q) use ($apply) { $q->where('is_pass', false)->where('aolapply_id', $apply->id); });
			
			$course_groups_failed->get()->each(function($cg) use ($apply) {
				$cg->comment->first()->delete();
				$apply->courseGroups()->detach($cg->id);
			});
			
			$course_groups_info = array();
			$arr_cg_teacher = $request->get('course_group_teachers');
			$arr_cg_class_count = $request->get('course_group_class_counts');
			array_walk($arr_cg_ids,
				function($cg, $index) use (&$course_groups_info, $arr_cg_teacher, $arr_cg_class_count) {
					$course_groups_info[$cg] = ['course_group_teacher_id' => $arr_cg_teacher[$index],
												'course_group_class_count' => $arr_cg_class_count[$index]];
				}
			);
			
			$apply->courseGroups()->attach($course_groups_info);
		}
		
		setMessage('success', $apply->wasRecentlyCreated ? '提交成功' : '修改成功');
		return redirect()->route('student.apply');
	}
	
	// 导出申请汇总表
	public function exportApplyList(Request $request) {
		$applies = AOLApply::orderBy('status', true);
		$filter_type = $request->input('filter');
		$this->filterApply($applies, $filter_type);
		if (null !== request()->input('range')) {
			$range = explode(',',request()->input('range'));
			$applies->whereIn('id', $range);
		}
		$applies = $applies->get();
		$countApply = $applies->count();
		$filename = gmdate('YmdHisU') . '.xlsx';
		$excel = new SpreadSheet();
		$excel->getProperties()->setCreator(env('APP_NAME_CH'))
							   ->setCompany('华南理工大学工商管理学院')
							   ->setTitle('华工工管AOL助教申请汇总表')
							   ->setDescription("AOL申请记录数目: $countApply 项");
		
		$sheet = $excel->setActiveSheetIndex(0);
		$sheet->setCellValue('A1', '序号')
			  ->setCellValue('B1', '学号')
			  ->setCellValue('C1', '姓名')
			  ->setCellValue('D1', '工商银行账号')
			  ->setCellValue('E1', '课程组名称')
			  ->setCellValue('F1', '课程组教学班数量')
			  ->setCellValue('G1', '应发酬金（税前）');
		list($total_rewards, $base_row_num, $row_num) = [0, 2, 0];
		foreach($applies as $apply_index => $apply) {
			$arr_cg = array();
			$cg_count = $apply->courseGroups->count();
			foreach($apply->courseGroups as $index => $cg) {
				$prefix = $cg_count>1 ? '('.($index+1).')' : '';
				$reward = self::rewardPerClass * $cg->pivot->course_group_class_count;
				$cg_info = ['name' => $prefix.$cg->name,
					'class_count' => $prefix.$cg->pivot->course_group_class_count];
				if ($cg->comment->count() && $cg->comment->first()->is_pass) {
					// 只计算审核通过的课程组酬金
					$cg_info['reward'] = $prefix . $reward;
					$total_rewards += $reward;
				} else {
					$cg_info['reward'] = $prefix . '-';
				}
				array_push($arr_cg, $cg_info);
			}
			$group_names = implode("\r\n", array_pluck($arr_cg, 'name'));
			$class_counts = implode("\r\n", array_pluck($arr_cg, 'class_count'));
			$rewards = implode("\r\n", array_pluck($arr_cg, 'reward'));
			
			$row_num = $base_row_num + $apply_index;
			$sheet->setCellValue('A'.$row_num, $apply_index + 1)
				  ->setCellValue('B'.$row_num, $apply->stu_no)
				  ->setCellValue('C'.$row_num, $apply->stu_name)
				  ->setCellValue('D'.$row_num, $apply->bank_account)
				  ->setCellValue('E'.$row_num, $group_names)
				  ->setCellValue('F'.$row_num, $class_counts)
				  ->setCellValue('G'.$row_num, $rewards);
		}
		
		// 将单元格的格式设为字符串
		$range = $sheet->getStyle('A2:G'.$row_num);
		$range->setQuotePrefix(true);
		
		// 输出合计应发酬金
		$row_num = $applies->count() + 2;
		$sheet->setCellValue('A'.$row_num, '合计')
			  ->mergeCells('A'.$row_num.':F'.$row_num)
			  ->setCellValue('G'.$row_num, '¥'.$total_rewards);
		
		// 设置单元格可换行
		$range = $sheet->getStyle('A1:G'.$row_num);
		$range->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
		
		// 设置表头格式
		$header = $sheet->getStyle('A1:G1');
		$header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$header->getFont()->setBold(true);
		
		// 设置列宽
		$arr_col_width = ['A' => 7, 'B' => 18, 'C' => 16, 'D' => 20, 'E' => 38,
						  'F' => 9, 'G' => 19];
		foreach($arr_col_width as $col => $width) {
			$sheet->getColumnDimension($col)->setWidth($width);
		}
		
		// 设置行高
		for ($i = 2; $i <= 1 + $countApply; $i ++) {
			$sheet->getRowDimension("$i")->setRowHeight(100);
		}
		
		// 为表格加边框
		$arr_style = [
			'borders' => [
				'left' => [
					'borderStyle' => Border::BORDER_THIN,
				],
				'top' => [
					'borderStyle' => Border::BORDER_THIN,
				],
				'right' => [
					'borderStyle' => Border::BORDER_THIN,
				],
				'bottom' => [
					'borderStyle' => Border::BORDER_THIN,
				],
			]
		];
		foreach($sheet->getRowIterator() as $row) {
			$cols = $row->getCellIterator();
			foreach($cols as $cell) {
				$cell->getStyle()->applyFromArray($arr_style);
			}
		}
		
		$row_num ++;
		// 输出酬金计算公式
		$sheet->setCellValue('A'.$row_num, '* 应发酬金=课程组教学班数量*500')
			  ->getStyle('A'.$row_num)->getFont()->getColor()
			  ->setARGB(Color::COLOR_RED);
		
		$sheet->setTitle('助教汇总表');
		$writer = Spreadsheet_IOFactory::createWriter($excel, 'Xlsx');
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename=\"$filename\"");
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0
		$writer->save('php://output');
	}
	
	// 导出申请信息表
	public function exportApply(Request $request, $id = null) {
		$user = session('credential')['user'];
		$user_type = session('credential')['type'];
		
		if($user_type == 3) {
			$apply = AOLApply::where('stu_id', $user->ID)->first();
		} else {
			$apply = AOLApply::where('id', $id)->first();
		}
		if(! isset($apply)) {
			return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
		}
		$tutor_name = $apply->tutor()->firstOrNew([])->name;
		$tutor_comment = $apply->tutorComment()->first();
		if (! isset($tutor_comment)) {
			$tutor_comment_content = '';
			$tutor_comment_sign = '';
			$tutor_comment_date = '';
		} else {
			$tutor_comment_content = $tutor_comment->content;
			$tutor_comment_sign = $tutor_name;
			$tutor_comment_date = date('Y 年 n 月 j 日', $tutor_comment->updated_at->timestamp);
		}
		
		$profile_field_value = [
			'stu_name' => $apply->stu_name,
			'stu_no' => $apply->stu_no,
			'gender' => array('', '男', '女')[$apply->gender],
			'tutor' => $tutor_name,
			'school' => $apply->school,
			'speciality' => $apply->speciality,
			'mobile' => $apply->mobile,
			'id_card_no' => $apply->id_card_no,
			'bank_account' => $apply->bank_account
		];
		
		$pxPerCmWidth = 28.56;
		$pxPerCmHeight = 28.35;
		$field_pos_size = [
			'stu_name' => [2.52, 0.9, 3.4, 0.71],
			'stu_no' => [7.5, 0.92, 2.22, 0.73],
			'gender' => [11.28, 0.9, 1, 0.71],
			'tutor' => [13.92, 0.9, 2.1, 0.71],
			'school' => [2.52, 1.75, 3.4, 0.71],
			'speciality' => [7.36, 1.75, 3.3, 0.71],
			'mobile' => [12.52, 1.75, 3.5, 0.71],
			'id_card_no' => [2.55, 2.66, 4.7, 0.71],
			'bank_account' => [10.99, 2.61, 5, 0.71],
			'course_group_teacher' => [2.57, 4.06, 3.46, 0.71],
			'work_at_0' => [12.31, 3.95, 0.42, 0.55],
			'work_at_1' => [12.31, 4.46, 0.42, 0.55],
			'course_group_name' => [2.5, 5.12, 4.7, 1.85],
			'course_group_class_count' => [11.06, 5.73, 3.46, 0.71],
			'tutor_comment' => [0.3, 7.87, 15.82, 1.88],
			'tutor_comment_sign' => [10.14, 9.7, 3.46, 0.71],
			'tutor_comment_date' => [12, 10.47, 3.8, 0.71],
			'course_group_comment' => [0.3, 12.03, 15.82, 3.52],
			'course_group_comment_sign' => [11.12, 15.55, 3.46, 0.71],
			'course_group_comment_date' => [12, 16.29, 3.8, 0.71],
			'memo' => [1.62, 22.21, 14.5, 0.71]
		];
		
		$word = new PhpWord();
		$word->getDocInfo()
			 ->setCreator(env('APP_NAME_CH'))
			 ->setCompany('华南理工大学工商管理学院')
			 ->setTitle('华南理工大学工商管理学院研究生兼任“助教”工作申请表');
		foreach($apply->courseGroups as $cg) {
			$cg_comment = $cg->comment()->first();
			if (! $cg_comment instanceof Comment) {
				$cg_comment_content = '';
				$cg_comment_sign = '';
				$cg_comment_date = '';
			} else {
				$cg_comment_content = $cg_comment->content;
				$cg_comment_sign = $cg->leaderTutor()->firstOrNew([])->name;
				$cg_comment_date = date('Y 年 n 月 j 日', $cg_comment->updated_at->timestamp);
			}
			
			$field_value = array_merge($profile_field_value, [
				'course_group_teacher' => $cg->course_group_teacher_name,
				'work_at_0' => $apply->work_at == 0 ? '√': '',
				'work_at_1' => $apply->work_at == 1 ? '√': '',
				'course_group_name' => $cg->name,
				'course_group_class_count' => $cg->pivot->course_group_class_count,
				'tutor_comment' => $tutor_comment_content,
				'tutor_comment_sign' => $tutor_comment_sign,
				'tutor_comment_date' => $tutor_comment_date,
				'course_group_comment' => $cg_comment_content,
				'course_group_comment_sign' => $cg_comment_sign,
				'course_group_comment_date' => $cg_comment_date,
				'memo' => $apply->memo]);
			
			// 一个课程组一页申请
			$section = $word->addSection();
			$section->addImage(base_path('template/app.png'),
				array('width' => 16.28 * $pxPerCmWidth,
					  'height' => 23.48 * $pxPerCmHeight,
					  'positioning' => 'relative',
					  'wrappingStyle' => 'behind'));
			
			foreach($field_pos_size as $field_name => $pos_size) {
				if (! strlen($field_value[$field_name])) continue;
				$textbox_styles = array(
					'marginLeft'  => $pos_size[0] * $pxPerCmWidth,
					'marginTop'   => $pos_size[1] * $pxPerCmHeight,
					'innerMarginLeft'  => 0,
					'innerMarginTop'   => 0,
					'width'       => $pos_size[2] * $pxPerCmWidth,
					'height'      => $pos_size[3] * $pxPerCmHeight,
					'borderColor' => 'null',
					'positioning' => 'absolute',
					'hpos'		  => Image::POSITION_ABSOLUTE,
					'vpos'   	  => Image::POSITION_ABSOLUTE,
					'posHorizontalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
					'posVerticalRel'   => Image::POSITION_RELATIVE_TO_MARGIN,
					'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
				);
				$text_styles = array();
				if ($field_name == 'stu_no') {
					// 学号文本需调整字号和内边距
					$textbox_styles['innerMarginLeft'] = 0;
					$textbox_styles['innerMarginTop'] = 0;
					if (strlen($field_value['stu_no']) <= 12) {
						// 12 字符以内的学号文本需调整位置使其垂直居中
						$offset = 0.2;
						$textbox_styles['marginTop'] = ($pos_size[1] + $offset) * $pxPerCmHeight;
						$textbox_styles['height'] = ($pos_size[3] - $offset) * $pxPerCmHeight;
					}
					$text_styles['size'] = 8;
				}
				$textbox = $section->addTextBox($textbox_styles);
				$textbox->addText($field_value[$field_name], $text_styles);
			}
		}
		$filename = gmdate('YmdHisU') . '.docx';
		$writer = Word_IOFactory::createWriter($word, 'Word2007');
		ob_end_clean();
		header('Content-Type: application/msword');
		header("Content-Disposition: attachment;filename=\"$filename\"");
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0
		$writer->save('php://output');
	}
	
	// 删除申请信息
	public function dropApply(Request $request, $id) {
		if (stripos($id, ',') == 0) {	// 单条记录
			$apply = AOLApply::find($id);
			if(! isset($apply)) {
				return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
			}
			// 删除该申请的应聘课程组关联信息
			$apply->courseGroups()->detach();
			// 删除该申请的审核意见
			Comment::where('aolapply_id', $id)->delete();
			AOLApply::destroy($id);
		} else {	// 多条记录
			$arr = explode(',', $id);
			$applies = AOLApply::whereIn('id', $arr)->get();
			if(! isset($applies) || $applies->count() < count($arr)) {
				return view('error',['error_desc'=>'系统找不到指定的申请记录。']);
			}
			foreach($applies as $apply) {
				// 删除所选申请的应聘课程组关联信息
				$apply->courseGroups()->detach();
			}
			// 删除所选申请的审核意见
			Comment::whereIn('aolapply_id', $arr)->delete();
			AOLApply::destroy($arr);
		}
		setMessage('success','记录删除成功');
		return redirect()->route('admin.apply');
	}
}

?>