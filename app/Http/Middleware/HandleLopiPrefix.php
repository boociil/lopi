<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleLopiPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();

        // Jika baseUrl bukan /lopi tapi URL diawali dengan /lopi
        if ($request->getBaseUrl() !== '/lopi' && str_starts_with($uri, '/lopi')) {
            // 1. Static asset gambar: /lopi/public/img/... atau /lopi/img/...
            if (preg_match('#^/lopi/(public/)?img/(.+)$#', $uri, $matches)) {
                $file = explode('?', $matches[2])[0];
                $path = public_path('img/' . $file);
                if (file_exists($path)) {
                    return response()->file($path);
                }
            }

            // 2. Static asset build (Vite): /lopi/public/build/... atau /lopi/build/...
            if (preg_match('#^/lopi/(public/)?build/(.+)$#', $uri, $matches)) {
                $file = explode('?', $matches[2])[0];
                $path = public_path('build/' . $file);
                if (file_exists($path)) {
                    return response()->file($path);
                }
            }

            // 3. Static asset favicon / umum: /lopi/public/...
            if (preg_match('#^/lopi/public/(.+)$#', $uri, $matches)) {
                $file = explode('?', $matches[1])[0];
                $path = public_path($file);
                if (file_exists($path) && !is_dir($path)) {
                    return response()->file($path);
                }
            }

            // 4. Route biasa: strip prefix '/lopi'
            $newUri = substr($uri, 5); // buang '/lopi'
            if ($newUri === '' || $newUri === false || $newUri[0] !== '/') {
                $newUri = '/' . $newUri;
            }

            $server = $request->server->all();
            $server['REQUEST_URI'] = $newUri;

            $newRequest = $request->duplicate(null, null, null, null, null, $server);
            app()->instance('request', $newRequest);

            return $next($newRequest);
        }

        return $next($request);
    }
}
