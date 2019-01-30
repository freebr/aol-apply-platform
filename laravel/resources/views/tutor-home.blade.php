@extends('app')

{{-- 导师端首页 --}}
@section('content')
<Card style="min-height: 60px; margin-bottom: 10px;">
	<p>
		<Badge count="{{ $countUnhandledTutorApply }}" overflow-count="100">
			<i-button type="primary" @click="location.href='{{ route('tutor.apply-type', ['list_type' => 1]) }}';"><Icon type="android-list" size="16" color="white"></Icon> 导师审核</i-button>
		</Badge>
		<Badge count="{{ $countUnhandledCGApply }}" overflow-count="100">
			<i-button type="primary" @click="location.href='{{ route('tutor.apply-type', ['list_type' => 2]) }}';" style="margin-left: 20px"><Icon type="android-list" size="16" color="white"></Icon> 课程组审核</i-button>
		</Badge>
		<i-button type="primary" @click="location.href='{{ route('tutor.course-group') }}';" style="margin-left: 20px"><Icon type="university" size="16" color="white"></Icon> 查看课程组列表</i-button>
	</p>
</Card>
<Card class="content-head" style="min-height: 100px;">
	<div class="heading">
		<h1>概览</h1>
		@if (! $countUnhandledApply)
		<p>目前没有等待处理的申请。</p>
		@else
		<p>目前共有 {{ $countUnhandledApply }} 项申请等待处理，
	其中等待导师审核 <a href="{{ route('tutor.apply-type', ['list_type' => 1]) }}">{{ $countUnhandledTutorApply }} 项</a>，
	等待课程组审核 <a href="{{ route('tutor.apply-type', ['list_type' => 2]) }}">{{ $countUnhandledCGApply }} 项</a>。</p>
		@endif
	</div>
</Card>
@stop