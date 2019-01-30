@extends('app')

@section('content')
<div class="error-card">
<Card style="width:100%;min-height:160px">
<div class="error-icon">
<Icon type="close-circled" size="64" color="red"></Icon>
</div>
<div class="error-desc">
<span>{{ $error_desc }}</span>
</div>
<div class="error-btn"><i-button type="info" @click="goBack">返 回</i-button></div>
</Card>
</div>
@stop

@section('Vue.methods')
{
	goBack: function() {
		history.go(-1);
	}
}
@stop