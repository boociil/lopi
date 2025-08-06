<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Header_Json_Remove
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post')) {
            $response = $next($request);
            if($response  instanceof JsonResponse){
                $response->header('Content-Type', 'text/html;');
            }
            return $response;
        }else{
            return $next($request);
        }

    }
}
