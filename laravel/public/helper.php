<?php

	function route_uri($name,$params=null) {
		$route=\Route::getRoutes()->getByName($name);
		if(! isset($route)) return null;
		$uri=$route->uri();
		if(isset($params)) {
			foreach($params as $param_name=>$value) {
				$uri=preg_replace("/\\{{$param_name}\\??\\}/",$value,$uri);
			}
		}
		if(substr($name,-6,6)=='.apply' && session()->has('current_page')) {
			// 若为列表页面地址则附加页码
			$uri.='?page='.session('current_page');
		}
		return $uri;
	}
	function route_chain($route) {
		$parts=explode('.', $route->getName());
		$route_name='';
		return array_map(function($item) use (&$route_name) {
			strlen($route_name)&&($route_name.='.');
			$route_name.=$item;
			return \Route::getRoutes()->getByName($route_name);
		},$parts);
	}
	function site_location($name=null) {
		if(!isset($name)) $name=\Request::route()->getName();
		global $sitemap;
		return $sitemap[$name];
	}

	// 设置要在客户端显示的消息提示
	function setMessage($type, $content, $duration=3) {
		session()->put('message', ['type'=>$type, 'content'=>$content, 'duration'=>$duration]);
		return;
	}
	
	// 返回显示消息的脚本，清除消息设置
	function flushMessage($vm='this') {
		$message=session()->pull('message');
		if(! isset($message)) return;
		return "$vm.\$Message.".$message['type'].
			   '({content:'.format_json($message['content']).
			   ',duration:'.$message['duration'].'})';
	}
	
	// 返回字符串的 SQL 表示形式
	function format_sql($str) {
		static $replace_configs=["'"=>"''",
							   "\r"=>"'+CHAR(13)+'",
							   "\n"=>"'+CHAR(10)+'"];
		foreach($replace_configs as $char_from=>$char_to) {
			$str=str_replace($char_from,$char_to,$str);
		}
		return "'$str'";
	}

	// 返回字符串的 JSON 表示形式
	function format_json($str) {
		static $replace_configs=['"'=>'\"',
							   "\r"=>'\r',
							   "\n"=>'\n'];
		foreach($replace_configs as $char_from=>$char_to) {
			$str=str_replace($char_from,$char_to,$str);
		}
		return "\"$str\"";
	}
	
	function user_title($type) {
		return $type==1||$type==2 ? '老师' : '同学';
	}
	
	function list_selection_column_def() {
		return <<<SelectionDef
{	type: 'selection',
	width: 53,
	fixed: 'left',
}
SelectionDef;
	}
	
	function apply_list_status_column_def() {
		return <<<StatusDef
{	title: '状态',
	key: 'status',
	width: 180,
	align: 'center',
	fixed: 'right',
	render: (h, params) => {
		var icon_type = ['information-circled','close-circled','checkmark-circled'];
		var icon_color = ['blue','red','green'];
		var arr=[];
		for(i=0;i<params.row.status.length;i++) {
			var item = params.row.status[i];
			arr.push(h('span', [i>0?'\\n':'',
				h('Icon', {
					props: {
						type: icon_type[item.type],
						size: 16,
						color: icon_color[item.type]
					},
					style: {
						marginRight: '5px'
					}
			}),item.name]));
		}
		return arr;
	}
	
}
StatusDef;
	}
	
	function apply_list_action_column_def($is_admin = false) {
		$view_def = <<<ViewDef
			h('Button', {
				props: {
					type: 'info',
					size: 'small'
				},
				style: {
					marginRight: '5px'
				},
				on: {
					click: () => {
						vm.showApply(params.row.id)
					}
				}
			}, '查 看')
ViewDef;
		$audit_def = <<<AuditDef
			h('Button', {
				props: {
					type: 'success',
					size: 'small'
				},
				style: {
					marginRight: '5px'
				},
				on: {
					click: () => {
						vm.showApply(params.row.id)
					}
				}
			}, '审 核')
AuditDef;
		$delete_def = ! $is_admin ? null : <<<DeleteDef
			h('Button', {
				props: {
					type: 'error',
					size: 'small'
				},
				style: {
					marginLeft: '5px'
				},
				on: {
					click: () => {
						vm.dropApply(params.row.id)
					}
				}
			}, '删 除')
DeleteDef;
		if ($is_admin) {
			$view_or_audit_def = $view_def;
		} else {
			$view_or_audit_def = "1 == params.row.status || 2 == params.row.status ? $audit_def : $view_def";
		}
		return <<<ColumnDef
{	title: '操作',
	key: 'action',
	width: 160,
	align: 'center',
	fixed: 'right',
	render: (h, params) => {
		return h('div', [
			$view_or_audit_def,
			$delete_def
		]);
	}
}
ColumnDef;
	}
	
	function course_group_list_action_column_def($is_admin = false) {
		$delete_def = ! $is_admin ? null : <<<DeleteDef
		,h('Button', {
				props: {
					type: 'error',
					size: 'small'
				},
				style: {
					marginLeft: '5px'
				},
				on: {
					click: () => {
						vm.dropCourseGroup(params.row.id)
					}
				}
			}, '删 除')
DeleteDef;
		return <<<ButtonDef
{	title: '操作',
	key: 'action',
	width: 180,
	align: 'center',
	fixed: 'right',
	render: (h, params) => {
		return h('div', [
			h('Button', {
				props: {
					type: 'primary',
					size: 'small'
				},
				style: {
					marginRight: '5px'
				},
				on: {
					click: () => {
						vm.showCourseGroup(params.row.id)
					}
				}
			}, '查 看')
			$delete_def
		]);
	}
}
ButtonDef;
	}
	
	function apply_status_data() {
		$arr = array();
		foreach(\App\AOLApply::$STATUS_INFO as $id => $props) {
			$icon = $props[1];
			array_push($arr, "{id: $id, alert: '$props[0]', icon: ['$icon[0]','$icon[1]'], name: '$props[2]', desc: '$props[3]'}");
		}
		return '['.implode(',', $arr).']';
	}
?>