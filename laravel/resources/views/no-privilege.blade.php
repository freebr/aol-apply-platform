@extends('app')

{{-- 拒绝访问提示 --}}
@section('content')
<p>您没有权限访问本页面。</p>
@stop

@section('Vue.methods')
{
	goBack: function() {
		history.go(-1);
	}
}
@stop

@section('Vue.mounted')
function() {
	this.$Modal.error({
		'title': '拒绝访问',
		'content': '<p>您没有权限访问本页面。</p>',
		'okText': '返回',
		'onOk': this.goBack
	});
}
@stop