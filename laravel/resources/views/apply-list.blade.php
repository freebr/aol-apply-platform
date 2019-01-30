@extends('app')

{{-- 申请列表 --}}
@section('content')
<Card class="content-head" style="min-height: 65px;">
	<div class="heading">
		<h1>查看AOL助教申请列表{{ $title_postfix }}</h1>
		<p>@if (! $countApply)
		暂无申请。
		@else
		共有 {{ $countApply }} 项申请，
			@if (! $countUnhandledApply)
		没有等待处理的申请。
			@else
		其中 {{ $countUnhandledApply }} 项申请等待处理。
			@endif
		@endif
		<span v-if="selection.length>0">已选中 @{{ selection.length }} 项</span></p>
		<p>
			@if ($is_admin)
			<i-button type="primary" @click="exportApplyList();"><Icon type="ios-download-outline" size="16" color="white"></Icon> 导出申请列表</i-button>
			<Button-Group>
				<i-button @click="filterApply()">
					全部
				</i-button>
				<i-button type="info" @click="filterApply('auditing')">
					待课程组审核
				</i-button>
				<i-button type="success" @click="filterApply('passed')">
					课程组通过
				</i-button>
				<i-button type="warning" @click="filterApply('failed')">
					课程组未通过
				</i-button>
			</Button-Group>
			@endif
			<i-button type="info" @click="this.loading = true; location.reload();"><Icon type="android-refresh" size="16" color="white"></Icon> 刷新</i-button>
			<i-button type="error" v-if="selection.length>0" @click="dropSelection"><Icon type="trash-a" size="16" color="white"></Icon> 删除选定申请</i-button>
		</p>
	</div>
</Card>
<Card>
	<anchor id="list" />
	<div class="pagination-panel">
	<Page :total="{{ $countApply }}"
		  :page-size="{{ $countListApply }}"
		  :current="{{ $currentPage }}"
		show-elevator @on-change="onPagination"></Page>
	</div>
	<i-table border stripe :columns="cols" :data="applies" class="apply-list" @on-selection-change="onSelectionChanged">
	</i-table>
	<div class="pagination-panel">
	<Page :total="{{ $countApply }}"
		  :page-size="{{ $countListApply }}"
		  :current="{{ $currentPage }}"
		show-elevator @on-change="onPagination"></Page>
	</div>
</Card>
<script>
	var status_data = {!! apply_status_data() !!};
</script>
@stop

@section('Vue.data')
{
	cols: {!! $arr_columns !!},
	applies: {!! $arr_applies !!},
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
	filterApply: function(type) {
		switch (type) {
		case undefined:
			uri = '{{ route('admin.apply') }}';
			break;
		case 'auditing':
			uri = '{{ route('admin.apply', ['filter'=>'auditing']) }}';
			break;
		case 'passed':
			uri = '{{ route('admin.apply', ['filter'=>'passed']) }}';
			break;
		case 'failed': default:
			uri = '{{ route('admin.apply', ['filter'=>'failed']) }}';
			break;
		}
		location.href = uri;
	},
	showApply: function(id) {
		window.open(route('{{ route_uri($is_admin ? 'admin.apply.show' : 'tutor.apply.show') }}',{'id':id}), '_blank');
	},
	exportApplyList: function(id) {
		var ids = this.selection.map(function(item) {
			return item.id;
		});
		var qs = [];
		if (this.selection.length) qs.push('range='+ids.join(','));
		@if (isset($filter_type)) qs.push('filter={{ $filter_type }}');
		@endif
		if (qs.length) qs[0]='?'+qs[0];
		window.open('/{{ route_uri('admin.apply.export-list') }}'+qs.join('&'), '_blank');
	},
	dropApply: function(id) {
		if(this.$Modal.confirm({
			'title': '删除申请',
			'content': '<p>确定要删除该申请吗？</p>',
			'onOk': function() { location.href=route('{{ route_uri('admin.apply.drop') }}',{'id':id}); }
		})) {
			return;
		}
	},
	dropSelection: function() {
		if(this.$Modal.confirm({
			'title': '删除选定申请',
			'content': '<p>确定要删除所选 '+this.selection.length+' 个申请吗？</p>',
			'onOk': function() {
				var ids = this.selection.map(function(item) {
					return item.id;
				});
				location.href = route('{{ route_uri('admin.apply.drop') }}',{'id':ids.join(',')})
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