<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|==============================================================================
| AdminPanelPassword middleware / Middleware de contraseña del panel
|==============================================================================
| ES: Verja de contraseña sencilla para el panel de citas (/panel). No usa el
|     sistema de usuarios de Laravel: una única contraseña compartida (la del
|     dueño) definida en config('appointments.panel.password').
|     Al acertarla, la sesión guarda una HUELLA de la contraseña (HMAC con la
|     clave de la app), no un simple «sí». Así, si el dueño cambia la contraseña,
|     todas las sesiones abiertas dejan de valer al momento.
|     También está registrado como middleware persistente de Livewire (ver
|     AppServiceProvider): cada acción de los componentes del panel vuelve a
|     pasar por aquí, aunque alguien reutilice una copia antigua de la página.
| EN: Simple password gate for the bookings panel (/panel). It does NOT use
|     Laravel's user system: a single shared password (the owner's) defined in
|     config('appointments.panel.password').
|     On success the session stores a FINGERPRINT of the password (HMAC with the
|     app key), not a plain "yes". So if the owner changes the password, every
|     open session stops working at once.
|     It is also registered as Livewire persistent middleware (see
|     AppServiceProvider): every action of the panel components goes through
|     here again, even if someone replays an old copy of the page.
|==============================================================================
*/
class AdminPanelPassword
{
    /**
     * ES: Clave de sesión donde se guarda la huella de la contraseña.
     * EN: Session key holding the password fingerprint.
     */
    public const CLAVE_SESION = 'panel_huella';

    /**
     * ES: Huella de la contraseña actual del panel (HMAC-SHA256 con APP_KEY).
     *     Devuelve null si no hay contraseña configurada (el panel falla cerrado).
     * EN: Fingerprint of the current panel password (HMAC-SHA256 with APP_KEY).
     *     Returns null if no password is configured (the panel fails closed).
     */
    public static function huella(): ?string
    {
        $contrasena = (string) config('appointments.panel.password');

        if ($contrasena === '') {
            return null;
        }

        return hash_hmac('sha256', 'meetcita-panel|'.$contrasena, (string) config('app.key'));
    }

    /**
     * ES: ¿La sesión actual tiene una entrada válida al panel? Compara en tiempo
     *     constante la huella guardada con la de la contraseña de ahora.
     * EN: Does the current session hold a valid panel login? Compares, in
     *     constant time, the stored fingerprint with the current password's one.
     */
    public static function sesionValida(): bool
    {
        $esperada = self::huella();
        $guardada = session(self::CLAVE_SESION);

        return $esperada !== null
            && is_string($guardada)
            && hash_equals($esperada, $guardada);
    }

    /**
     * ES: Si la sesión no está autenticada en el panel, redirige al login del
     *     panel (o 403 en peticiones de Livewire y JSON).
     * EN: If the session isn't authenticated in the panel, redirect to the
     *     login (or 403 for Livewire and JSON requests).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::sesionValida()) {
            // ES: Las peticiones de Livewire no siguen redirecciones: 403 claro.
            // EN: Livewire requests don't follow redirects: plain 403.
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                abort(403);
            }

            return redirect()->route('panel.login');
        }

        return $next($request);
    }
}
