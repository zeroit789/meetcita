<?php

use App\Http\Middleware\AdminPanelPassword;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|==============================================================================
| Application bootstrap / Arranque de la aplicación
|==============================================================================
| EN: Wires up routing, middleware and exception handling for the appointments
|     module. Highlights: trust the reverse proxy, set the locale on every web
|     request, alias the panel password middleware, and exempt the Telegram
|     webhook from CSRF (its security is its secret header).
| ES: Conecta el enrutado, el middleware y el manejo de excepciones del módulo de
|     citas. Claves: confiar en el proxy inverso, fijar el idioma en cada petición
|     web, dar alias al middleware de contraseña del panel, y eximir el webhook de
|     Telegram del CSRF (su seguridad es su cabecera secreta).
|==============================================================================
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar solo en el proxy inverso real (red Docker interna de Coolify:
        // 10.0.1.0/24). Antes se usaba '*' (todos los proxies), lo que permitía
        // falsificar X-Forwarded-For y evadir los rate limits por IP.
        // Con el CIDR concreto, solo Caddy puede inyectar esa cabecera.
        $middleware->trustProxies(
            at: '10.0.1.0/24',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        // EN: i18n: set the locale on every web request from the 'locale' cookie,
        //     so each page is served already in the chosen language and the
        //     preference persists while browsing the whole site.
        // ES: i18n: fija el idioma (locale) en cada petición web según la cookie
        //     'locale', así cada página se sirve ya en el idioma elegido y la
        //     preferencia se mantiene al navegar por toda la web.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // EN: Alias to protect the bookings panel with a password
        //     (config appointments.panel.password).
        // ES: Alias para proteger el panel de citas con contraseña
        //     (config appointments.panel.password).
        $middleware->alias([
            'appointments.panel' => AdminPanelPassword::class,
            'setlocale'          => SetLocale::class,
        ]);

        // EN: The Telegram webhook is an external POST (no session nor CSRF token):
        //     exempt it from CSRF verification. Its security is the secret in the
        //     X-Telegram-Bot-Api-Secret-Token header (validated in the controller).
        // ES: El webhook de Telegram es un POST externo (sin sesión ni token CSRF):
        //     se excluye de la verificación CSRF. Su seguridad la da el secreto de
        //     la cabecera X-Telegram-Bot-Api-Secret-Token (validado en el controlador).
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
