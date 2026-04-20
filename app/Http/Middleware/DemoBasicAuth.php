<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoBasicAuth
{
    private const DEMO_CREDENTIAL = 'demo';

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) $request->getUser();
        $password = (string) $request->getPassword();

        if ($user === self::DEMO_CREDENTIAL && $password === self::DEMO_CREDENTIAL) {
            return $next($request);
        }

        return response('Demo access only.', 401, [
            'WWW-Authenticate' => 'Basic realm="MarginFlow Demo", charset="UTF-8"',
        ]);
    }
}
