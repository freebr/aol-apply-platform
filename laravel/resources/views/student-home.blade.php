@extends('app')

{{-- 学生端首页 --}}
@section('content')
<Card class="content-head" style="min-height: 100px;">
	<div class="heading">
		<h1>欢迎使用{{ env('APP_NAME_ZH') }}</h1>
		@if ($step == 0)
		<p>要开始申请助教工作，请先 <i-button type="primary" small @click="newApply"><Icon type="compose" size="16" color="white"></Icon> 填写申请信息</i-button></p>
		@elseif ($step == 1)
		<p>您的申请信息已提交，正在等待导师审核，您可以 <i-button type="primary" small @click="showApply"><Icon type="android-list" size="16" color="white"></Icon> 查看申请信息</i-button></p>
		<p>申请提交时间：{{ $time_submit->toDateTimeString() }}</p>
		@elseif ($step == 2)
		<p>您的申请信息已提交，正在等待课程组审核，详情请 <i-button type="primary" small @click="showApply"><Icon type="android-list" size="16" color="white"></Icon> 查看申请信息</i-button></p>
		<p>申请提交时间：{{ $time_submit->toDateTimeString() }}</p>
		@elseif ($step == 3)
		<p>很抱歉，您的申请信息未能获导师审核通过，详情请 <i-button type="primary" small @click="showApply"><Icon type="android-list" size="16" color="white"></Icon> 查看审核意见</i-button>，或者您可以<i-button type="primary" small @click="newApply"><Icon type="compose" size="16" color="white"></Icon> 重新填写申请</i-button></p>
		<p>导师审核时间：{{ $time_tutor_comment->toDateTimeString() }}</p>
		@else
			@if ($apply->tutor_id && isset($time_tutor_comment))
		<p>您的申请信息已由导师审核通过，详情请 <i-button type="primary" small @click="showApply"><Icon type="android-list" size="16" color="white"></Icon> 查看审核意见</i-button></p>
		<p>导师审核通过时间：{{ $time_tutor_comment->toDateTimeString() }}</p>
			@else
		<p>您的申请信息已提交，详情请 <i-button type="primary" small @click="showApply"><Icon type="android-list" size="16" color="white"></Icon> 查看申请信息</i-button></p>
			@endif
		<p>课程组审核状态：</p>
		<ul class="course-group-audit">
		@foreach ($course_groups as $cg)
		<li>{{ $cg->name }}：{!! $render_status($cg->comment->count() ? $cg->comment->first()->is_pass : null) !!}</li>
		@endforeach
		</ul>
		@if ($fail_count > 0)
		<p>有 {{ $fail_count }} 个课程组未审核通过您的申请，请 <i-button type="primary" small @click="refillApply"><Icon type="compose" size="16" color="white"></Icon> 修改申请信息</i-button></p>
		@endif
		@endif
	</div>
</Card>
@stop

@section('Vue.methods')
{
	newApply: function() {
		location.href = '{{ route('student.apply.new') }}';
	},
	showApply: function() {
		location.href = '{{ route('student.apply') }}';
	},
	refillApply: function() {
		location.href = '{{ route('student.apply.refill') }}';
	}
}
@stop