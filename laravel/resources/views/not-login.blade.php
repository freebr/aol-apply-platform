@extends('app')

{{-- 未登录提示 --}}
@section('content')
<p>您必须登录学院电子院务系统后才能使用本平台。</p>
@stop

@section('Vue.methods')
{
	goBack: function() {
		location.href = '//www.cnsba.com';
	}
}
@stop

@section('Vue.mounted')
function() {
	this.$Modal.error({
		'title': '未登录',
		'content': '<p>您必须登录学院电子院务系统后才能使用本平台。</p>',
		'okText': '去学院首页登录',
		'onOk': this.goBack
	});
}
@stop