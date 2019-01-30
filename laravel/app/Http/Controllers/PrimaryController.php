<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\AOLApply;
use App\TeacherUser;
use App\StudentUser;
use App\Tutor;
use App\CourseGroup;

class PrimaryController extends Controller
{
	public function login(Request $request) {
		$usertype = intval($request->route('type'));
		switch($usertype) {
			case 1: # 教务员
				$user = TeacherUser::where(['ID'=>$request->route('uid')])->first();
				break;
			case 2: # 教师
				$user = TeacherUser::where(['ID'=>$request->route('uid')])->first();
				$tutor = Tutor::where('account_id', $user->ID)->first();
				break;
			case 3:	# 学生
				$user = StudentUser::find($request->route('uid'));
				break;
			default:
				return \Redirect::to('//www.cnsba.com');
		}
		if(! isset($user) || $usertype == 2 && ! isset($tutor)) {
			return view('error', ['error_desc'=>'登录失败，用户不存在，或您没有使用本系统的权限。']);
		}
		$credential = ['user'=>$user, 'type'=>$usertype, 'login_time'=>date('Y-m-d H:i:s')];
		
		if(2 == $usertype) $credential['tutor'] = $tutor;
		session()->flush();
		session()->put('credential', $credential);
		
		$route_name = ['','admin','tutor','student'];
		return \Redirect::route($route_name[$usertype]);
	}
	
	public function logout(Request $request) {
		session()->flush();
		return \Redirect::to('//www.cnsba.com');
	}
	
	public function adminHome(Request $request) {
		$countUnhandledApply = AOLApply::whereIn('status',
			[AOLApply::$STATUS['Tutor-Auditing'],
			 AOLApply::$STATUS['CourseGroup-Auditing'],
			 AOLApply::$STATUS['Tutor-Audit-Passed']])->count();
		return view('admin-home', ['countUnhandledApply'=>$countUnhandledApply]);
	}
	
	public function tutorHome(Request $request) {
		if (! array_key_exists('tutor', session('credential'))) return \Redirect::route('home');
		$tutor_id = session('credential')['tutor']->id;
		$countUnhandledTutorApply = AOLApply::where('tutor_id', $tutor_id)
			->where('status', AOLApply::$STATUS['Tutor-Auditing'])->count();
		$countUnhandledCGApply = AOLApply::whereHas('courseGroups',
			function($q) use ($tutor_id) {
				$q->where('course_group_teacher_id', $tutor_id)
				  ->whereDoesntHave('comment', function($qq) {
					$qq->whereRaw('aolapply_id = aolapplies.id');
				});
			}
		)->whereIn('status', [AOLApply::$STATUS['CourseGroup-Auditing'], AOLApply::$STATUS['Tutor-Audit-Passed']])->count();
		$countUnhandledApply = $countUnhandledTutorApply + $countUnhandledCGApply;
		return view('tutor-home', ['countUnhandledApply' => $countUnhandledApply,
			'countUnhandledTutorApply' => $countUnhandledTutorApply,
			'countUnhandledCGApply' => $countUnhandledCGApply]);
	}
	
	public function studentHome(Request $request) {
		$student = session('credential')['user'];
		$apply = AOLApply::where('stu_no', $student->Account)->first();
		if (! isset($apply)) {
			$step = 0;
		} else {
			$step = $apply->status;
		}
		$params = ['step' => $step, 'apply' => $apply];
		if ($step != 0) {
			$params['time_submit'] = $apply->updated_at;
			$params['time_tutor_comment'] = $apply->tutorComment()->firstOrNew([])->updated_at;
			$params['time_course_group_comment'] = $apply->courseGroupComments()
				->orderBy('updated_at', true)->firstOrNew([])->updated_at;
			$params['course_groups'] = $apply->courseGroups;
			$params['fail_count'] = $apply->courseGroups()->whereHas('comment',function($q) use ($apply) {
					$q->where('is_pass', false)->where('aolapply_id', $apply->id);
				})->count();
			$params['render_status'] = function($is_pass) {
				$icon_type = ['information-circled','close-circled','checkmark-circled'];
				$icon_color = ['blue','red','green'];
				$status_name = ['待审核','审核不通过','审核通过'];
				$index = ! isset($is_pass) ? 0 : $is_pass + 1;
				return <<<StatusDef
<Icon type="{$icon_type[$index]}" size="16" color="$icon_color[$index]" style="margin-right:5px"></Icon>$status_name[$index]
StatusDef;
			};
		}
		return view('student-home', $params);
	}
	
	public function searchTutor($keyword) {
		if(! isset($keyword)) return '';
		$arr=Tutor::where('name', 'like', '%'.$keyword.'%')
				  ->orWhere('account', 'like', $keyword.'%')
				  ->orderBy('name')->take(50)->get()->all();
		$objects=array_map(function($item) {
			return "{\"id\": $item->id, \"name\": ".format_json($item->name).
				   ", \"account\": ".format_json($item->account).
				   ", \"department\": ".format_json($item->department)."}";
		}, $arr);
		return '{"result":['.implode(',',$objects).']}';
	}
	
	public function searchCourseGroup($keyword) {
		if(! isset($keyword)) return '';
		$arr=CourseGroup::where('name', 'like', '%'.$keyword.'%')
						->orderBy('name')->take(50)->get()->all();
		$objects=array_map(function($item) {
			return "{\"id\": $item->id, \"name\": ".format_json($item->name).
				   ", \"leader_tutor\": ".$item->leaderTutor()->first()->toJson().'}';
		}, $arr);
		return '{"result":['.implode(',',$objects).']}';
	}
	
}
