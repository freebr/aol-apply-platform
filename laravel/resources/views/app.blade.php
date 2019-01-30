<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ site_location() }}-{{ env('APP_NAME_ZH') }}</title>
	
	<link rel="stylesheet" href="{{ asset('css/iview.css') }}"/>
	<link rel="stylesheet" href="{{ asset('css/global.css') }}"/>
	<script src="{{ asset('scripts/jquery.min.js') }}"></script>
	<script src="{{ asset('scripts/vue.min.js') }}"></script>
	<script src="{{ asset('scripts/iview.min.js') }}"></script>
	<script src="{{ asset('scripts/helper.js') }}"></script>
	
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="http://cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="http://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
	<div id="app" class="layout">
		<i-layout>
			<i-header class="layout-header">
				<a href="{{ route('home') }}"><div class="layout-logo"></div></a>
				<div class="layout-account">
				@if (null!==session('credential'))
				<p><Icon type="android-contacts" size="16"></Icon><span>欢迎您，<span style="font-weight: bold">{{ session('credential')['user']->Name }}</span> {{  user_title(session('credential')['type']) }}！</span>
				<span class="link-logout"><a href="{{ route('logout') }}"><Icon type="android-exit" size="16" color="green"></Icon> 退出系统</a></span></p>
				@endif
				</div>
			</i-header>
			<i-content :style="{padding: '0 50px'}">
				<Breadcrumb separator="&#xbb;" class="site-map">
				@foreach (route_chain(\Request::route()) as $route)
					<Breadcrumb-Item to="{{ $route === \Request::route() ? '' : '/'.route_uri($route->getName(),\Request::route()->parameters()) }}">{{ site_location($route->getName()) }}</Breadcrumb-Item>
				@endforeach
				</Breadcrumb>
				@yield('content')
			</i-content>
			<i-footer class="layout-footer-center">2017-{{ date("Y") }} &copy; {{ env('APP_NAME_ZH','') }}</i-footer>
		</i-layout>
	</div>
	<script>
		var vm=new Vue({
			el: '#app',
			data: @yield('Vue.data','null'),
			methods: @yield('Vue.methods','null'),
			mounted: @yield('Vue.mounted','null')
		});
	</script>
</body>
</html>
