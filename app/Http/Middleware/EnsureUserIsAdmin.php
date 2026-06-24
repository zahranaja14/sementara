public function handle(Request $request, Closure $next)
{
    if (auth()->check() && auth()->user()->is_admin == 1) {
        return $next($request);
    }
    return redirect('/dashboard');
} 