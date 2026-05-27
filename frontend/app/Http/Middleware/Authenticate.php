<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('token')) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
