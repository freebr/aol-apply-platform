<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\AOLApply;
use App\Tutor;
use App\CourseGroup;
use App\Comment;

class CourseGroupController extends Controller
{
	const countListCourseGroup=20;
	
	// 显示课程组列表
	public function listCourseGroups(Request $request) {
		
		$is_admin = session('credential')['user']->IsAdmin;
		$query = CourseGroup::orderBy('name');
		if (session('credential')['type'] == 2) {
			// 导师端，只显示导师所在的课程组
			$tutor_id = session('credential')['tutor']->id;
			$query->whereHas('members', function($q) use ($tutor_id) {
				$q->whereLeaderTutorId($tutor_id)->orWhere('tutor_id', $tutor_id);
			});
		}
		$countCourseGroup=$query->count();
		$course_groups=$query->paginate(self::countListCourseGroup);
		$currentPage=request()->input('page')?:session('course-group-list.current_page')?:1;
		session()->put('course-group-list.current_page', $currentPage);
		
		$arr_columns=['name'=>'课程名称','leader_tutor_name'=>'课程协调人',
					  'member_names'=>'任课老师'];
		$arr_course_groups=[];
		foreach($course_groups as $cg) {
			$members = Tutor::names($cg->members);
			$member_names = implode('、', $members);
			array_push($arr_course_groups,'{'.
				'id:'.$cg->id.','.
				'name:'.format_json($cg->name).','.
				'leader_tutor_name:'.format_json($cg->leaderTutor()->first()->name).','.
				'member_names:'.format_json($member_names).'}'
			);
		}
		$params=[
			'is_admin' => $is_admin,
			'countCourseGroup' => $countCourseGroup,
			'currentPage' => $currentPage,
			'route_show' => $is_admin ? 'admin.course-group.show' : 'tutor.course-group.show',
			'arr_columns'=>'['.($is_admin?list_selection_column_def().',':'').implode(',',
				array_map(function($key) use ($arr_columns) {
					if(is_array($arr_columns[$key])) {
						$title=$arr_columns[$key][0];
						$width=$arr_columns[$key][1];
						return "{'title': '$title', 'key': '$key', 'width': $width, align: 'center'}";
					} else {
						$title=$arr_columns[$key];
						return "{'title': '$title', 'key': '$key', align: 'center'}";
					}
				},array_keys($arr_columns))).','.
				course_group_list_action_column_def($is_admin).
			']',
			'arr_course_groups'=>'['.implode(',',$arr_course_groups).']'
		];
		return view('course-group-list', $params);
	}
	
	// 显示课程组详情
	public function showCourseGroup(Request $request, $id = null) {
		$user_type = session('credential')['type'];
		if(1 != $user_type && 2 != $user_type) {
			return view('error',['error_desc'=>'您没有查看课程组的权限。']);
		}
		if (isset($id)) {
			$cg = CourseGroup::find($id);
			if(! isset($cg)) {
				return view('error',['error_desc'=>'系统找不到指定的课程组记录。']);
			}
			$route_action = '/'.route_uri('admin.course-group.update', ['id'=>$cg->id]);
		} else {
			$cg = new CourseGroup();
			$route_action = '/'.route_uri('admin.course-group.add');
		}
		$locked = 1 !== $user_type;
		return view('course-group-detail',
			['course_group'=>$cg, 'is_new' => $cg->id == 0, 'user_type'=>$user_type,
			 'route_action' => $route_action, 'locked'=>$locked]);
	}
	
	// 更新课程组信息
	public function updateCourseGroup(Request $request, $id = null) {
		$params = ['name'=>$request->get('name'),
					 'leader_tutor_id'=>$request->get('leader_tutor')
				  ];
		if (isset($id)) {
			$cg = CourseGroup::find($id);
			if(! isset($cg)) {
				return view('error',['error_desc'=>'系统找不到指定的课程组记录。']);
			}
			$cg->update($params);
		} else {
			$cg = CourseGroup::create($params);
		}
		
		$members_id = explode(',',$request->get('members'));
		$cg->members()->sync($members_id);
		
		setMessage('success', $cg->wasRecentlyCreated ? '新增成功' : '修改成功');
		if ($request->get('after_new') == 'new') {
			return redirect()->route('admin.course-group.new');
		} else {
			return redirect()->route('admin.course-group');
		}
	}
	
	// 删除课程组及关联信息
	public function dropCourseGroup(Request $request, $id) {
		if (stripos($id, ',') == 0) {	// 单条记录
			$cg = CourseGroup::find($id);
			if(! isset($cg)) {
				return view('error',['error_desc'=>'系统找不到指定的课程组记录。']);
			}
			// 删除应聘该课程组的申请的关联信息
			$cg->applies()->detach();
			// 删除该课程组的成员的关联信息
			$cg->members()->detach();
			// 删除该课程组的审核意见
			Comment::where('commentable_id', $id)->where('commentable_type', 'course_groups')
				   ->delete();
			CourseGroup::destroy($id);
		} else {	// 多条记录
			$arr = explode(',', $id);
			$course_groups = CourseGroup::whereIn('id', $arr)->get();
			if(! isset($course_groups) || $course_groups->count() < count($arr)) {
				return view('error',['error_desc'=>'系统找不到指定的课程组记录。']);
			}
			foreach($course_groups as $cg) {
				// 删除应聘所选课程组的申请的关联信息
				$cg->courseGroups()->detach();
				// 删除所选课程组的成员的关联信息
				$cg->members()->detach();
				// 删除所选课程组的审核意见
				Comment::where('commentable_id', $cg->id)->where('commentable_type', 'course_groups')
					   ->delete();
			}
			CourseGroup::destroy($arr);
		}
		setMessage('success','记录删除成功');
		return redirect()->route('admin.course-group');
	}
}

?>