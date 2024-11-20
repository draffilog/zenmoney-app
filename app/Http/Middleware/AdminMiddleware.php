<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('AdminMiddleware: Starting middleware check');

        if (!auth()->check()) {
            Log::debug('AdminMiddleware: User not authenticated');
            return redirect()->route('login');
        }

        Log::debug('AdminMiddleware: User authenticated', [
            'user_id' => auth()->id(),
            'is_admin' => auth()->user()->isAdmin()
        ]);

        if (!auth()->user()->isAdmin()) {
            Log::debug('AdminMiddleware: User not admin');
            auth()->logout();
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        Log::debug('AdminMiddleware: Access granted');
        return $next($request);
    }
}
