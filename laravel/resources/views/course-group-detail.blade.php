@extends('app')

{{-- 教务端课程组详情 --}}
@section('content')
<Card class="content-head" style="min-height: 65px;">
	<div class="heading">
		<h1>{{ $is_new ? '新增课程组' : '查看课程组详情' }}</h1>
		@if ($course_group->id != 0)
		<p>
		创建时间：{{ $course_group->created_at }} / 更新时间：{{ $course_group->updated_at }}
		</p>
		@endif
	</div>
</Card>
<Card style="min-height: 250px;">
	<i-form ref="fields" action="{{ $route_action }}" method="post" :model="fields" :rules="field_rules" :label-width="100">
		{{ csrf_field() }}
		<Row>
			<Alert show-icon>带 <span style="color: red">*</span> 的字段为必填项</Alert>
		</Row>
		<Row>
			<i-col span="10">
				<Form-Item label="课程名称" prop="name">
					<i-input name="name" v-model="fields.name" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="6">
				<Form-Item label="课程协调人" label-width="100" prop="leader_tutor">
					<i-select name="leader_tutor"
						v-model="fields.leader_tutor"
						filterable remote {{ $locked ? '' : 'clearable' }}
						:remote-method="handleSearchTutor"
						:loading="loading_tutor"
						:disabled="readonly"
						style="width: 200px">
						<i-option v-for="(item, index) in teacher_data" :value="item.id" :label="item.name+item.account" :key="index">
							<span class="auto-complete-name" :department="item.department">@{{ item.name }}</span>
						</i-option>
					</i-select>
				</Form-Item>
			</i-col>
		</Row>
		<Row>
			<i-col span="12">
				<Form-Item label="任课老师" label-width="100" prop="members">
					<i-select name="members"
						v-model="fields.members"
						multiple filterable remote {{ $locked ? '' : 'clearable' }}
						:remote-method="handleSearchTutor"
						:loading="loading_tutor"
						:disabled="readonly">
						<i-option v-for="(item, index) in teacher_data" :value="item.id" :label="item.name+item.account" :key="index">
							<span class="auto-complete-name" :department="item.department">@{{ item.name }}</span>
						</i-option>
					</i-select>
				</Form-Item>
			</i-col>
		</Row>
        <Form-Item>
        @if (! $locked)
			<i-button type="primary" @click="handleSubmit('fields')">提交修改</i-button>
			@if ($is_new)
			<input type="hidden" name="after_new" />
			<i-button type="success" @click="handleSubmit('fields', 'new')">提交并继续新增</i-button>
			@endif
		@endif
		@if ($is_new)
			<i-button type="ghost" @click="location.href='{{ route('admin.course-group') }}'" style="margin-left: 8px">返回列表</i-button>
		@else
			<i-button type="ghost" @click="window.close()" style="margin-left: 8px">关闭页面</i-button>
		@endif
		@if ($user_type == 1 and $course_group->id != 0)
			<span style="float: right">
			<i-button type="success" @click="newCourseGroup">新增课程组</i-button>
			<i-button type="error" @click="dropCourseGroup">删除课程组</i-button>
			</span>
		@endif
        </Form-Item>
    </i-form>
</Card>
@stop

<?php
	$leader_tutor = $course_group->leaderTutor()->firstOrNew([]);
	$leader_tutor_json = $leader_tutor->toJson();
	$members_json = \App\Tutor::serialize($course_group->members);
	
	$pattern = '/(?<="account":)[^,]+/';
	$leader_tutor_json = preg_replace($pattern, '""', $leader_tutor_json);
	$members_json = preg_replace($pattern, '""', $members_json);
		
	$members_id = array();
	foreach($course_group->members as $member) {
		array_push($members_id, $member->id);
	}
?>
@section('Vue.data')
{
	teacher_data: [],
	readonly: {!! $locked ? '""' : 'null' !!},
	loading_tutor: false,
	
	fields: {
		name: {!! format_json($course_group->name) !!},
		leader_tutor: {{ $course_group->leader_tutor_id }},
		members: {!! '['.implode(',',$members_id).']' !!}
	},
	field_rules: {
		name: [
			{ required: true, message: '请输入课程名称', trigger: 'blur' }
		],
		leader_tutor: [
			{ type: 'number', min: 1, required: true, message: '请输入课程协调人姓名', trigger: 'blur' }
		],
		members: [
			{ type: 'array', min: 1, required: true, message: '请输入任课老师姓名', trigger: 'blur' }
		]
	}
}
@stop
 
@section('Vue.methods')
{
	handleSubmit: function(name, after_new) {
		this.$refs[name].validate(function(valid) {
			if (valid) {
				this.$Message.success({content: '请稍候', duration: 5});
				$(':hidden[name="after_new"]').val(after_new);
				$('form').submit();
			} else {
				this.$Message.error({content: '您填写的信息有误，请按提示修改！', duration: 5});
			}
		}.bind(this));                                                                                                                                                                                                                          
	},
	handleReset: function(name) {
		this.$refs[name].resetFields();
	},
	handleSearchTutor: function(value) {
		if(!value.length) {
			this.teacher_data = [];
			return;
		}
		this.loading_tutor = true;
		$.getJSON(route('{{ route_uri('api.search-tutor') }}', {keyword: value})).then(
			function(response, status) {
				if(status != 'success') {
					this.$Message.error({content: '获取资源失败，请稍后再试', duration: 3});
					return;
				}
				this.teacher_data = response.result;
				this.loading_tutor = false;
			}.bind(this));
	}@if ($user_type == 1),
	newCourseGroup: function() {
		location.href = '{{ route('admin.course-group.new') }}';
	},
	dropCourseGroup: function() {
		this.$Modal.confirm({
			content: '确定要删除该课程组吗？',
			onOk: function() {
				$('form').attr('action', '{{ route('admin.course-group.drop', ['id'=>$course_group->id]) }}').submit();
			}
		});
	}
	@endif
}
@stop

@section('Vue.mounted')
function() {
	{!! flushMessage() !!}
	
	this.teacher_data = [{!! $leader_tutor_json !!}].concat({!! $members_json !!});
}
@stop