<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        // Memisahkan role yang dipisahkan oleh koma
        $roles = explode(',', $roles);

        // Pastikan user sudah login
        if (Auth::check()) {
            $user = Auth::user();
            
            // Cek apakah role pengguna ada dalam array roles yang diteruskan
            if (!in_array($user->role, $roles)) {
                return response()->json(['message' => 'You do not have permission to access this resource'], 403);
            }
        } else {
            // Jika pengguna belum login
            return response()->json(['message' => 'You must be logged in first'], 401);
        }

        return $next($request);
    }
}
