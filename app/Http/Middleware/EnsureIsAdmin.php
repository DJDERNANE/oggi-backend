<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/login') || $request->is('admin/logout')) {
            return $next($request);
        }

        // Check if the user is authenticated and has the 'admin' role
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Logout the user and clear session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the admin login page
        return redirect()->back()->with('error', 'Access denied. Only admins can log in.');
    }
}
