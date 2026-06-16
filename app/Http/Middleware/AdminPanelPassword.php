<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|==============================================================================
| AdminPanelPassword middleware / Middleware de contraseña del panel
|==============================================================================
| EN: Simple password gate for the bookings panel (/panel). It does NOT use
|     Laravel's user system: a single shared password (the owner's) defined in
|     config('appointments.panel.password'). When the user gets it right a session
|     flag is set and they can see the panel until they log out or the session expires.
| ES: Verja de contraseña sencilla para el panel de citas (/panel). No usa el
|     sistema de usuarios de Laravel: una única contraseña compartida (la del
|     dueño) definida en config('appointments.panel.password'). Cuando el usuario
|     la acierta, se marca un flag en la sesión y puede ver el panel hasta que
|     cierre sesión o caduque la sesión.
|==============================================================================
*/
class AdminPanelPassword
{
    /**
     * EN: If the session isn't authenticated in the panel, redirect to the login.
     * ES: Si la sesión no está autenticada en el panel, redirige al login del panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // EN: Session flag set by the login on a correct password.
        // ES: Flag de sesión que pone el login al acertar la contraseña.
        if (! $request->session()->get('panel_autenticado', false)) {
            return redirect()->route('panel.login');
        }

        return $next($request);
    }
}
