<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PlanningValidation
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface && in_array($e->getStatusCode(), [409, 422]) && ! $request->isMethodSafe() && ! $request->expectsJson()) {
                return back()->withInput($request->except(['_token', 'file']))->withErrors(['planning' => $e->getMessage() ?: 'Please check the selected record and try again.']);
            }
            throw $e;
        }
    }
}
