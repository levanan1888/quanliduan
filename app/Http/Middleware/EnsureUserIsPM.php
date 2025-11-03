<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure user is a Project Manager (PM)
 */
class EnsureUserIsPM
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role !== 'PM') {
            return response()->json([
                'message' => 'Only Project Managers can perform this action.',
            ], 403);
        }

        return $next($request);
    }
}

