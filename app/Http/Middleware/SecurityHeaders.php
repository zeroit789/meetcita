<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/*
|==============================================================================
| SecurityHeaders — cabeceras de seguridad / security headers
|==============================================================================
| ES: Añade a cada respuesta:
|       - Content-Security-Policy con un nonce por petición (config/csp.php).
|         Vite y Livewire ponen ese nonce solos en sus <script>; los scripts en
|         línea de los layouts lo llevan con Vite::cspNonce(). Sin
|         'unsafe-eval': Livewire va en modo csp_safe (config/livewire.php).
|       - X-Content-Type-Options, Referrer-Policy, Permissions-Policy y
|         X-Frame-Options; HSTS solo si la petición llega por HTTPS.
|       - Quita X-Powered-By (no anunciar la versión de PHP).
| EN: Adds to every response:
|       - Content-Security-Policy with a per-request nonce (config/csp.php).
|         Vite and Livewire add that nonce to their <script> tags by
|         themselves; the layouts' inline scripts carry it via
|         Vite::cspNonce(). No 'unsafe-eval': Livewire runs in csp_safe mode
|         (config/livewire.php).
|       - X-Content-Type-Options, Referrer-Policy, Permissions-Policy and
|         X-Frame-Options; HSTS only when the request comes over HTTPS.
|       - Removes X-Powered-By (don't advertise the PHP version).
|==============================================================================
*/
class SecurityHeaders
{
    /**
     * ES: Genera el nonce ANTES de pintar la vista y pone las cabeceras al final.
     * EN: Generates the nonce BEFORE rendering the view and sets the headers at the end.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $respuesta = $next($request);

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        $respuesta->headers->remove('X-Powered-By');

        if (config('csp.enabled', true)) {
            $respuesta->headers->set('Content-Security-Policy', $this->politica($nonce));
        }

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // ES: HSTS solo por HTTPS (por HTTP el navegador la ignora y en local molestaría).
        // EN: HSTS only over HTTPS (browsers ignore it over HTTP and it would get in the way locally).
        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }

    /**
     * ES: Monta la política a partir de config/csp.php sustituyendo {nonce}.
     * EN: Builds the policy from config/csp.php replacing {nonce}.
     */
    private function politica(string $nonce): string
    {
        $partes = [];
        foreach (config('csp.directives', []) as $directiva => $fuentes) {
            $valor = str_replace('{nonce}', $nonce, implode(' ', (array) $fuentes));
            $partes[] = trim($directiva.' '.$valor);
        }

        return implode('; ', $partes);
    }
}
