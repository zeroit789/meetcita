<?php

use App\Http\Middleware\AdminPanelPassword;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|==============================================================================
| Application bootstrap / Arranque de la aplicación
|==============================================================================
| ES: Conecta el enrutado, el middleware y el manejo de excepciones del módulo de
|     citas. Claves: confiar en el proxy inverso, fijar el idioma en cada petición
|     web, dar alias al middleware de contraseña del panel, y eximir el webhook de
|     Telegram del CSRF (su seguridad es su cabecera secreta).
| EN: Wires up routing, middleware and exception handling for the appointments
|     module. Highlights: trust the reverse proxy, set the locale on every web
|     request, alias the panel password middleware, and exempt the Telegram
|     webhook from CSRF (its security is its secret header).
|==============================================================================
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ES: Proxies de confianza: las IPs salen de config/trustedproxy.php
        //     (TRUSTED_PROXIES en el .env), no del código. Antes estaba fija la
        //     red de un despliegue concreto (10.0.1.0/24) y, detrás de otro
        //     proxy, el límite de reservas por IP se volvía común para todos.
        //     Aquí solo se eligen las cabeceras X-Forwarded-* que se aceptan.
        // EN: Trusted proxies: the IPs come from config/trustedproxy.php
        //     (TRUSTED_PROXIES in .env), not from the code. It used to hard-code
        //     one deployment's network (10.0.1.0/24) and, behind another proxy,
        //     the per-IP booking limit became shared by everyone.
        //     Here we only choose which X-Forwarded-* headers are accepted.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        // ES: i18n: fija el idioma (locale) en cada petición web según la cookie
        //     'locale', así cada página se sirve ya en el idioma elegido y la
        //     preferencia se mantiene al navegar por toda la web.
        // EN: i18n: set the locale on every web request from the 'locale' cookie,
        //     so each page is served already in the chosen language and the
        //     preference persists while browsing the whole site.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // ES: Cabeceras de seguridad (CSP con nonce, nosniff, etc.) en todas las respuestas.
        // EN: Security headers (CSP with nonce, nosniff, etc.) on every response.
        $middleware->append(SecurityHeaders::class);

        // ES: Alias para proteger el panel de citas con contraseña
        //     (config appointments.panel.password).
        // EN: Alias to protect the bookings panel with a password
        //     (config appointments.panel.password).
        $middleware->alias([
            'appointments.panel' => AdminPanelPassword::class,
            'setlocale' => SetLocale::class,
        ]);

        // ES: El webhook de Telegram es un POST externo (sin sesión ni token CSRF):
        //     se excluye de la verificación CSRF. Su seguridad la da el secreto de
        //     la cabecera X-Telegram-Bot-Api-Secret-Token (validado en el controlador).
        // EN: The Telegram webhook is an external POST (no session nor CSRF token):
        //     exempt it from CSRF verification. Its security is the secret in the
        //     X-Telegram-Bot-Api-Secret-Token header (validated in the controller).
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    // ES: Excepciones: las rutas api/* responderían con JSON (valor por defecto
    //     del esqueleto de Laravel; este proyecto no define rutas api/*).
    // EN: Exceptions: api/* routes would get JSON responses (Laravel skeleton
    //     default; this project defines no api/* routes).
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
