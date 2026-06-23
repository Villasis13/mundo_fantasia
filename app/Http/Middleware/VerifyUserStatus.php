<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si el usuario está autenticado
        if (Auth::check()) {
            // Verificar si el estado en la sesión coincide con el estado actual del usuario en la base de datos
            if (Auth::user()->users_estado != 1) {
                // Usuario inactivo, realizar acciones necesarias (por ejemplo, cerrar sesión)
                Auth::logout();
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
