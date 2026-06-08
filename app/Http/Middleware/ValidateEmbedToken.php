<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Website;

class ValidateEmbedToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-EMBED-TOKEN');

        $website = Website::where('embed_token', $token)->first();

        if (!$website) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        if (!$website->is_active) {
            return response()->json([
                'error' => 'Website is inactive',
            ], 403);
        }

        // if ($website->verify_domain) {

        //     $origin = $request->header('Origin');
        //     $host = parse_url(
        //         $origin,
        //         PHP_URL_HOST
        //     );
        //     if ($host !== $website->domain) {
        //          return response()->json([
        //     'error' => 'Domain mismatch'
        //         ], 403);

        //     }

        // }

        $request->merge([
            'website' => $website
        ]);

        return $next($request);
    }
}
