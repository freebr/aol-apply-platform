@extends('app')

{{-- 教务端首页 --}}
@section('content')
<Card style="min-height: 60px; margin-bottom: 10px;">
	<p>
		<i-button type="primary" @click="location.href='{{ route('admin.apply') }}';"><Icon type="android-list" size="16" color="white"></Icon> 查看申请列表</i-button>
		<i-button type="primary" @click="location.href='{{ route('admin.course-group') }}';"><Icon type="university" size="16" color="white"></Icon> 查看课程组列表</i-button>
	</p>
</Card>
<Card class="content-head" style="min-height: 100px;">
	<div class="heading">
		<h1>概览</h1>
		@if (! $countUnhandledApply)
		<p>目前没有等待处理的申请。</p>
		@else
		<p>目前共有 <a href="{{ route('admin.apply') }}">{{ $countUnhandledApply }} 项申请</a>等待处理。</p>
		@endif
	</div>
</Card>
@stop