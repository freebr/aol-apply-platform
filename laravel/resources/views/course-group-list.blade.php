@extends('app')

{{-- 教务端课程组列表 --}}
@section('content')
<Card class="content-head" style="min-height: 65px;">
	<div class="heading">
		<h1>查看课程组列表</h1>
		<p>@if (! $countCourseGroup)
		@if ($is_admin) 没有可用的课程组。@else 您没有加入任何课程组。@endif
		@else
		@if ($is_admin) 共有 {{ $countCourseGroup }} 个课程组。
		@else 您加入了 {{ $countCourseGroup }} 个课程组。@endif
		@endif
		<span v-if="selection.length>0">已选中 @{{ selection.length }} 项</span></p>
		<p>
			<i-button type="primary" @click="location.reload();"><Icon type="android-refresh" size="16" color="white"></Icon> 刷新列表</i-button>
			@if ($is_admin)
			<i-button type="success" @click="location.href='{{ route('admin.course-group.new') }}';"><Icon type="android-list" size="16" color="white"></Icon> 新增课程组</i-button>
			<i-button type="error" v-if="selection.length>0" @click="dropSelection"><Icon type="trash-a" size="16" color="white"></Icon> 删除选定课程组</i-button>
			@endif
		</p>
	</div>
</Card>
<Card>
	<anchor id="list" />
	<div class="pagination-panel">
	<Page :total="{{ $countCourseGroup }}"
		  :page-size="20"
		  :current="{{ $currentPage }}"
		show-elevator @on-change="onPagination"></Page>
	</div>
	<i-table border stripe height="450" :columns="cols" :data="course_groups" class="course-group-list" @on-selection-change="onSelectionChanged">
	</i-table>
	<div class="pagination-panel">
	<Page :total="{{ $countCourseGroup }}"
		  :page-size="20"
		  :current="{{ $currentPage }}"
		show-elevator @on-change="onPagination"></Page>
	</div>
</Card>
@stop

@section('Vue.data')
{
	cols: {!! $arr_columns !!},
	course_groups: {!! $arr_course_groups !!},
	selection: []
}
@stop

@section('Vue.methods')
{
	onPagination: function(page_num) {
		location.href = '?page='+page_num+'#list';
	},
	onSelectionChanged: function(new_selection) {
		this.selection = new_selection;
	},
	showCourseGroup: function(id) {
		window.open(route('{{ route_uri($route_show) }}',{'id':id}), '_blank');
	},
	dropCourseGroup: function(id) {
		if(this.$Modal.confirm({
			'title': '删除课程组',
			'content': '<p>确定要删除该课程组吗？填报该课程组的申请将受到影响！</p>',
			'onOk': function() { location.href = route('{{ route_uri('admin.course-group.drop') }}',{'id':id}); }
		})) {
			return;
		}
	},
	dropSelection: function() {
		if(this.$Modal.confirm({
			'title': '删除选定课程组',
			'content': '<p>确定要删除所选 '+this.selection.length+' 个课程组吗？填报这些课程组的申请将受到影响！</p>',
			'onOk': function() {
				var ids = this.selection.map(function(item) {
					return item.id;
				});
				location.href = route('{{ route_uri('admin.course-group.drop') }}',{'id':ids.join(',')})
			}.bind(this)
		})) {
			return;
		}
	}
}
@stop

@section('Vue.mounted')
function() {
	{!! flushMessage() !!}
}
@stop