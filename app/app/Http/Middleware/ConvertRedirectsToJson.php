<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;

class ConvertRedirectsToJson
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->expectsJson() && $response instanceof RedirectResponse) {
            return response()->json([
                'redirect' => $response->getTargetUrl(),
                'success' => session('success'),
                'message' => session('message'),
            ]);
        }

        return $response;
    }
}

