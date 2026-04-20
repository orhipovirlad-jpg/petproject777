<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireCabinetAuth
{
    /**
     * @param Closure(Request): (Response|RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (Auth::check()) {
            return $next($request);
        }

        return redirect()
            ->route('cabinet.login-page')
            ->withErrors(['auth' => 'Сначала войдите в кабинет.']);
    }
}
