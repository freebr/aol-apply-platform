@extends('app')

{{-- AOL申请详情 --}}
@section('content')
<Card class="content-head" style="min-height: 65px;">
	<div class="heading">
		<h1>{{ $new ? '填写AOL助教申请信息' : ($refill ? '修改AOL助教申请信息' : '查看AOL助教申请详情') }}</h1>
		@if (! $new)
		<p>
		创建时间：{{ $apply->created_at }} / 更新时间：{{ $apply->updated_at }}
		</p>
		@if ($user_type == 1 || $user_type == 3 && $cg_comment_count > 0)
		<p>
			<i-button type="primary" @click="location.href='{{ $route_export }}';"><Icon type="ios-download-outline" size="16" color="white"></Icon> 下载申请表</i-button>
		</p>
		@endif
		@endif
	</div>
</Card>
<Card>
	<i-form ref="fields" action="{{ $route_action }}" method="post" :model="fields" :rules="field_rules" :label-width="100">
		{{ csrf_field() }}
		<Row>
		@if ($new)
			<Alert show-icon>带 <span style="color: red">*</span> 的字段为必填项</Alert>
		@else
			<Alert show-icon :type="status_data[fields.status-1].alert" v-once>当前申请状态：@{{ status_data[fields.status-1].name }}
			<span slot="desc">@if ($user_type == 3)@{{ status_data[fields.status-1].desc }}@endif</span></Alert>
		@endif
		</Row>
		<Row>
			<i-col span="6">
				<Form-Item label="姓名" prop="stu_name">
					<i-input name="stu_name" v-model="fields.stu_name" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="6">
				<Form-Item label="学号" label-width="80" prop="stu_no">
					<i-input name="stu_no" v-model="fields.stu_no" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="5">
				<Form-Item label="性别" label-width="80" prop="gender">
					<Radio-Group v-model="fields.gender">
						<Radio name="gender[1]" label="1" :disabled="readonly">男</Radio>
						<Radio name="gender[2]" label="2" :disabled="readonly">女</Radio>
					</Radio-Group>
				</Form-Item>
			</i-col>
			@if ($apply->applicant->StudentType == 2)
			<i-col span="4">
				<Form-Item label="导师" label-width="50" prop="tutor">
					<i-select name="tutor"
						v-model="fields.tutor"
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
			@endif
		</Row>
 		<Row>
			<i-col span="6">
				<Form-Item label="学院" prop="school">
					<i-input name="school" v-model="fields.school" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="6">
				<Form-Item label="专业" label-width="80" prop="speciality">
					<i-input name="speciality" v-model="fields.speciality" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="6">
				<Form-Item label="手机号码" label-width="80" prop="mobile">
					<i-input name="mobile" v-model="fields.mobile" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
		</Row>
		<Row>
			<i-col span="7">
				<Form-Item label="身份证号码" prop="id_card_no">
					<i-input name="id_card_no" v-model="fields.id_card_no" :readonly="readonly"></i-input>
				</Form-Item>
			</i-col>
			<i-col span="10">
				<Form-Item label="工商银行账号" label-width="100" prop="bank_account">
				    <Poptip placement="top-start">
						<i-input name="bank_account" v-model="fields.bank_account" :readonly="readonly" style="width: 205px"></i-input>
						<span slot="content">
							<Icon type="information-circled" size="16" color="blue"></Icon> 请输入广州市工商银行账号
						</span>
					</Poptip>
				</Form-Item>
			</i-col>
		</Row>
		<Row>
			<i-col span="6">
				<Form-Item label="聘用助教校区" label-width="100" prop="work_at">
					<Radio-Group name="work_at" v-model="fields.work_at">
						<Radio name="work_at[0]" label="0" :disabled="readonly">五山校区</Radio>
						<Radio name="work_at[1]" label="1" :disabled="readonly">大学城校区</Radio>
					</Radio-Group>
				</Form-Item>
			</i-col>
		</Row>
		<Row>
			<Form-Item label="应聘课程组">
				<Row v-for="(item, index) in fields.course_groups">@if ($refill)
					<Row><i-col span="11"><Alert show-icon>您所选课程组没有审核通过您的申请，请重新选择</Alert></i-col></Row>
					@endif
					<Row style="margin-bottom: 30px">
						<i-col span="15">
							<Form-Item
								:key="index"
								:label="'第 ' + (index+1) + ' 课程组名称'"
								:prop="'course_groups.' + index + '.id'"
								:rules="{type: 'number', min: 1, required: true, message: '请填写第 ' + (index+1) + ' 个课程组的名称', trigger: 'blur'}"
								label-width="110">
								<i-select :name="'course_groups['+index+']'"
									v-model="item.id"
									filterable remote {{ $locked ? '' : 'clearable' }}
									:remote-method="handleSearchCourseGroup"
									:loading="loading_course_group"
									:disabled="course_group_apply_readonly"
									@on-change="handleCourseGroupChange(item)"
									style="width: 200px">
									<i-option v-for="cg in course_group_data" :value="cg.id" :label="cg.name" :key="cg.id">
										<span class="auto-complete-name">@{{ cg.name }}</span>
									</i-option>
								</i-select>
								<i-button type="text" size="small" v-if="readonly==null" @click="handleRemoveCourseGroup(index)" shape="circle" title="删除"><Icon type="android-cancel" size="16" color="#ed3f14"></Icon></i-button>
							</Form-Item>
						</i-col>
					</Row>
					<Row style="margin-bottom: 30px">
						<i-col span="7">
							<Form-Item label="课程组教师"
								:prop="'course_groups.' + index + '.pivot.course_group_teacher_id'"
								:rules="{ type: 'number', min: 1, required: true, message: '请选择课程组教师', trigger: 'blur' }">
								<i-select :name="'course_group_teachers['+index+']'"
									v-model="item.pivot.course_group_teacher_id"
									filterable remote {{ $locked ? '' : 'clearable' }}
									:remote-method="handleSearchTutor"
									:label="item.pivot.course_group_teacher_name"
									:loading="loading_tutor"
									:disabled="course_group_apply_readonly"
									style="width: 200px">
									<i-option v-for="(item, index) in teacher_data" :value="item.id" :label="item.name+item.account" :key="index">
										<span class="auto-complete-name" :department="item.department">@{{ item.name }}</span>
									</i-option>
								</i-select>
							</Form-Item>
						</i-col>
						<i-col span="6">
							<Form-Item label="课程组教学班数量"
								label-width="120"
								:prop="'course_groups.' + index + '.pivot.course_group_class_count'"
								:rules="{ type: 'number', min: 1, required: true, min: 1, message: '请输入课程组教学班数量', trigger: 'blur' }">
									<Input-Number :name="'course_group_class_counts['+index+']'" :max="20" :min="1" v-model="item.pivot.course_group_class_count" :readonly="course_group_apply_readonly"></Input-Number>
							</Form-Item>
						</i-col>
					</Row>@if (! $new && ! $refill)
					<Form-Item label="课程组意见" v-if="!!item.comment[0]"@if ($is_course_group_teacher)
						:prop="!item.comment[0].required?null:'course_groups.' + index + '.comment[0].content'"
						:rules="!item.comment[0].required?null:{required: true, message: '请填写第 ' + (index+1) + ' 个课程组的意见', trigger: 'blur'}"
					@endif style="margin-bottom: 20px">
						<Row>
							<i-col span="8">@if ($is_course_group_teacher)
								<Alert show-icon v-if="item.comment[0].required">请您在下面填写课程组对该申请的意见</Alert>
							@endif
								<p v-if="!!item.comment[0].author"><Icon type="information-circled" size="12" color="blue"></Icon> @{{ item.comment[0].author.name }} 老师在 @{{ item.comment[0].updated_at }} 提交</span></p>
								<i-input :name="'course_group_comments['+index+']'" v-model="item.comment[0].content" @if ($user_type!=1):readonly="item.comment[0].required?null:''"@endif type="textarea" rows="5"></i-input>
							</i-col>
						</Row>
					</Form-Item>
					<Form-Item label="是否审核通过" v-if="!!item.comment[0]"@if ($is_course_group_teacher)
						:prop="!item.comment[0].required?null:'course_groups.' + index + '.comment[0].is_pass'"
						:rules="!item.comment[0].required?null:{type: 'number', required: true, message: '请选择第 ' + (index+1) + ' 个课程组的审核结果', trigger: 'blur'}"
					@endif>
						<Row>
							<i-col span="8">
								<i-switch :name="'course_group_results['+index+']'"
									size="large" v-model="item.comment[0].is_pass"
									@if ($user_type!=1):disabled="item.comment[0].required?null:''"@endif
									:true-value="1" :false-value="0">
									<span slot="open">是</span><span slot="close">否</span>
								</i-switch>
								@if ($is_course_group_teacher)
									<Alert show-icon @if ($user_type!=1)v-if="item.comment[0].required"@endif>默认为审核通过，若审核不通过请点击将其切换为“否”</Alert>
								@endif
							</i-col>
						</Row>
					</Form-Item>@endif
				</Row>
				<Form-Item label=" " v-if="readonly==null">
					<Row>
						<i-col span="15">
							<i-button type="dashed" icon="plus-round" long @click="handleAddCourseGroup">新增课程组</i-button>
						</i-col>
					</Row>
				</Form-Item>
			</Form-Item>
		</Row>@if (! $new && $apply->applicant->StudentType == 2)
		<Form-Item label="导师意见" prop="tutor_comment.content">
			<Row>
				<i-col span="17">@if ($is_tutor && ! $cg_comment_count)
					<Alert show-icon>请您在下面填写对该申请的审核意见</Alert>
				@elseif (isset($tutor))
					<p v-if="!!fields.tutor_comment.updated_at"><Icon type="information-circled" size="12" color="blue"></Icon> {{ $tutor->name }} 老师在 @{{ fields.tutor_comment.updated_at }} 提交</span></p>
				@endif
					<i-input name="tutor_comment" v-model="fields.tutor_comment.content" :readonly="tutor_comment_readonly" type="textarea" rows="5"></i-input>
				</i-col>
			</Row>
		</Form-Item>
		@if (! $is_tutor || $apply->status !== $STATUS['Tutor-Auditing'])
		<Form-Item label="是否审核通过">
			<Row>
				<i-switch name="tutor_result" v-model="fields.tutor_comment.is_pass" :true-value="1" :false-value="0" :disabled="tutor_comment_readonly" size="large">
					<span slot="open">是</span><span slot="close">否</span>
				</i-switch>
			</Row>
		</Form-Item>
		@endif
		@endif
		<Row>
			<i-col span="17">
				<Form-Item label="备注" prop="memo">
					<i-input name="memo" v-model="fields.memo" {{ $user_type == 1 || $user_type == 3 && (! $locked || $refill) ? '' : 'readonly' }} type="textarea" rows="5"></i-input>
				</Form-Item>
			</i-col>
		</Row>@if ($user_type == 1)
		<Row>
			<i-col span="5">
				<Form-Item label="当前状态" prop="status">
					<i-select name="status" :placeholder="status_data[0].name" v-model="fields.status" {{ $user_type == 1 ? '' : 'disabled' }} style="width: 200px">
						<i-option v-for="(item, index) in status_data" :value="item.id">@{{ item.name }}</i-option>
					</i-select>
				</Form-Item>
			</i-col>
		</Row>@endif
        <Form-Item>
		@if ($user_type == 1)
            <i-button type="info" @click="handleSubmit('fields')">提交修改</i-button>
        @elseif ($is_tutor && ! $cg_comment_count &&
			in_array($apply->status, [$STATUS['Tutor-Auditing'], $STATUS['Tutor-Audit-Failed'], $STATUS['Tutor-Audit-Passed']]))
			<input type="hidden" name="audit_pass" />
            <i-button type="success" @click="handleSubmit('fields', 'pass')">审核通过</i-button>
			<i-button type="error" @click="handleSubmit('fields')">审核不通过</i-button>
		@elseif ($is_course_group_teacher &&
			in_array($apply->status, [$STATUS['CourseGroup-Auditing'], $STATUS['Tutor-Audit-Passed'], $STATUS['All-CourseGroups-Audited']]))
            <i-button type="info" @click="handleSubmit('fields')">提交审核意见</i-button>
		@elseif ($user_type == 3 && ($new || $refill || in_array($apply->status, [$STATUS['Tutor-Auditing'], $STATUS['CourseGroup-Auditing']])))
            <i-button type="info" @click="handleSubmit('fields')">提交修改</i-button>
		@elseif ($user_type == 3 && $has_failed)
            <i-button type="info" @click="location.href='{{ route('student.apply.refill') }}'">修改未获通过的申请</i-button>
		@endif
		@if ($user_type == 3)
			<i-button type="ghost" @click="location.href='{{ route('student') }}'" style="margin-left: 8px">返回首页</i-button>
		@else
            <i-button type="ghost" @click="window.close()" style="margin-left: 8px">关闭页面</i-button>
		@endif
		@if ($user_type == 1)
			<i-button type="error" @click="dropApply" style="float: right">删除申请</i-button>
		@endif
	   </Form-Item>
    </i-form>
</Card>
@stop

@section('Vue.data')
{
	teacher_data: [],
	course_group_teacher_data: [],
	course_group_data: [],
	status_data: {!! apply_status_data() !!},
	
	readonly: {!! $locked ? '""' : 'null' !!},
	tutor_comment_readonly: {!! $user_type == 1 || $is_tutor && ! $cg_comment_count ? 'null' : '""' !!},
	course_group_apply_readonly: {!! $locked && ! $refill ? '""' : 'null' !!},
	course_group_comment_readonly: {!! $user_type == 1 || $is_course_group_teacher ? 'null' : '""' !!},
	loading_tutor: false,
	loading_course_group_teacher: false,
	loading_course_group: false,
	fields: {
		stu_name: {!! format_json($apply->stu_name) !!},
		stu_no: '{{ $apply->stu_no }}',
		gender: '{{ $apply->gender }}',
		tutor: {{ $apply->tutor_id }},
		mobile: '{{ $apply->mobile }}',
		id_card_no: '{{ $apply->id_card_no }}',
		bank_account: '{{ $apply->bank_account }}',
		school: '{{ $apply->school }}',
		speciality: {!! format_json($apply->speciality) !!},
		work_at: '{{ $apply->work_at }}',
		course_groups: {!! \App\CourseGroup::serialize($apply->courseGroups) !!},
		@if ($apply->applicant->StudentType == 2)
		tutor_comment: {!! ($apply->tutorComment()->first() ?? new \App\Comment(['is_pass' => $user_type == 3 ? null : 1]))->toJson() !!},
		@endif
		memo: {!! format_json($apply->memo) !!},
		status: {{ $apply->status }}
	},
	field_rules: {
		stu_name: [
			{ required: true, message: '请输入您的姓名', trigger: 'blur' }
		],
		stu_no: [
			{ required: true, message: '请输入您的学号', trigger: 'blur' },
			{ pattern: '^\\d{12,}$', message: '学号格式不正确', trigger: 'blur' }
		],
		gender: [
			{ pattern: '^[12]$', required: true, message: '请选择您的性别', trigger: 'blur' }
		],
		@if ($apply->applicant->StudentType == 2)
		tutor: [
			{ type: 'number', min: 1, required: true, message: '请选择导师', trigger: 'blur' }
		],
		@endif
		school: [
			{ required: true, message: '请输入您所在的学院', trigger: 'blur' }
		],
		speciality: [
			{ required: true, message: '请输入您的专业', trigger: 'blur' }
		],
		mobile: [
			{ required: true, message: '请输入您的手机号码', trigger: 'blur' },
			{ pattern: '^\\d{11}$', message: '手机号码格式不正确', trigger: 'blur' }
		],
		id_card_no: [
			{ required: true, message: '请输入您的身份证号码', trigger: 'blur' },
			{ pattern: '^(\\d{18}|\\d{17}[Xx])$', message: '身份证号码格式不正确', trigger: 'blur' }
		],
		bank_account: [
			{ required: true, message: '请输入您的工商银行账号', trigger: 'blur' },
			{ length: 18, message: '工商银行账号格式错误', trigger: 'blur' }
		],
		work_at: [
			{ pattern: '^[01]$', required: true, message: '请选择聘用助教校区', trigger: 'blur' }
		],
		@if ($apply->applicant->StudentType == 2 && $is_tutor && ! $cg_comment_count)
		'tutor_comment.content': [
				{ required: true, message: '请填写您的审核意见', trigger: 'blur' }
		],
		@endif
		course_groups: [
			{ type: 'array', required: true, min: 1, message: '请填报至少一个课程组', trigger: 'blur' }
		]
	}
}
@stop
 
@section('Vue.methods')
{
	handleSubmit: function(name, is_pass) {
		this.$refs[name].validate(function(valid) {
			if (valid) {
				this.$Message.success({content: '请稍候', duration: 5});
				$(':hidden[name="audit_pass"]').val(is_pass);
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
	},
	handleSearchCourseGroup: function(value) {
		this.loading_course_group = true;
		$.getJSON(route('{{ route_uri('api.search-course-group') }}', {keyword: value})).then(
			function(response, status) {
				this.course_group_data = response.result;
				this.loading_course_group = false;
			}.bind(this));
	},
	handleAddCourseGroup: function() {
		if(this.fields.course_groups.length>={{ $max_course_group_count }}) {
			this.$Message.warning({content: '最多只能填报 {{ $max_course_group_count }} 个课程组！', duration: 3});
			return;
		}
		var comment = {
			content: '',
			index: this.fields.course_groups.length,
			required: 0,
			is_pass: {{ $user_type == 3 ? 'null' : '1' }}
		};
		this.fields.course_groups.push({
			id: '',
			name: '',
			index: this.fields.course_groups.length,
			pivot: {course_group_teacher_id: 0, course_group_class_count: 1},
			comment: [comment]
		});
	},
	handleRemoveCourseGroup: function(index) {
		if(this.fields.course_groups.length==1) {
			this.$Message.warning({content: '至少需要填报一个课程组！', duration: 3});
			return;
		}
		this.fields.course_groups.splice(index,1);
		this.fields.course_group_comments.splice(index,1);
	},
	handleCourseGroupChange: function(course_group) {
		var cg = this.course_group_data.find(function(item) {
			return course_group.id == item.id;
		});
		if(!cg||!cg.leader_tutor) return;
		cg.leader_tutor.account = '';	// 防止账号名显示出来
		this.teacher_data = new Array(cg.leader_tutor);
		course_group.pivot.course_group_teacher_id = cg.leader_tutor.id;
	}@if ($user_type == 1),
	dropApply: function() {
		this.$Modal.confirm({
			content: '确定要删除该申请吗？',
			onOk: function() {
				$('form').attr('action', '{{ route('admin.apply.drop', ['id'=>$apply->id]) }}').submit();
			}
		});
	}
	@endif
}
@stop

@section('Vue.mounted')
function() {
	{!! flushMessage() !!}
	this.teacher_data = [@if (isset($tutor)) {id: this.fields.tutor, name: {!! format_json($tutor->name) !!}, account: '', department: {!! format_json($tutor->department) !!} } @endif]
		.concat(this.fields.course_groups.map(function(item) { return {'id': item.pivot.course_group_teacher_id, name: item.course_group_teacher_name, account: '', department: item.course_group_teacher_department}; }));
	this.course_group_data = this.fields.course_groups;
	@if (! $apply->id)	{{-- 新的申请 --}}
	this.handleAddCourseGroup();
	@else
	$.each(this.fields.course_groups,function(index,item){if(!item.comment.length)item.comment.push({content:'',is_pass:{{ $user_type == 3 ? 'null' : '1' }}});
		@if ($is_course_group_teacher)
		item.comment[0].required=item.pivot.course_group_teacher_id=={{ $tutor_id }};
		@else
		item.comment[0].required=false;
		@endif
	}.bind(this));
	@endif
}
@stop