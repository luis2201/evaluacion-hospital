<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToInitialSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! User::query()->exists()) {
            return redirect()->route('setup.create');
        }

        return $next($request);
    }
}
