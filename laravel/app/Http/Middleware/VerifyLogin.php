<?php namespace App\Http\Middleware;

use Closure;

class VerifyLogin {
	
	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct()
	{
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$route_name = $request->route()->getName();
		if($route_name != 'login' && ! session()->has('credential')) {
			// 未登录
			return response()->view('not-login');
		}
		
		$credential = session('credential');
		if(starts_with($route_name, 'admin') && 1 != $credential['type'] ||
		   starts_with($route_name, 'tutor') && 2 != $credential['type'] ||
		   starts_with($route_name, 'student') && 3 != $credential['type']) {
			// 没有权限访问
			return response()->view('no-privilege');
		}
		
		return $next($request);
	}

}
