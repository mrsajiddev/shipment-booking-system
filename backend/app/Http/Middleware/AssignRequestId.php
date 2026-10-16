<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * GLOBAL middleware.
 * Gives every request an ID, adds it to every log line and to the response header.
 * When a user reports a problem, the ID lets you find all its log lines quickly.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        $request->headers->set('X-Request-Id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);                          // <- the rest of the app runs here

        $response->headers->set('X-Request-Id', $requestId);  // added on the way back out

        return $response;
    }
}
