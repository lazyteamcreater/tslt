<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                    'အကောင့်ဝင်ရောက်ရန် လိုအပ်ပါသည်။',
            ], 401);
        }

        if (
            !$user->is_active ||
            !$user->isAdmin()
        ) {
            return response()->json([
                'message' =>
                    'Admin အသုံးပြုသူများသာ အသုံးပြုနိုင်ပါသည်။',
            ], 403);
        }

        return $next($request);
    }
}
