<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'message' => 'User not found.',
                ], 401);
            }
        } catch (TokenExpiredException) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token has expired.',
            ], 401);
        } catch (TokenInvalidException) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token is invalid.',
            ], 401);
        } catch (JWTException) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token not provided.',
            ], 401);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token is invalid.',
            ], 401);
        }

        return $next($request);
    }
}
