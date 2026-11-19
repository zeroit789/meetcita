<?php

/*
|==============================================================================
| CSP — Content Security Policy / Política de seguridad de contenidos
|==============================================================================
| ES: Directivas que pone App\Http\Middleware\SecurityHeaders. {nonce} se
|     sustituye por el nonce de cada petición. Todo se sirve desde el propio
|     dominio: sin fuentes ni scripts de terceros. Si incrustas la página en tu
|     web con un iframe, añade tu dominio a frame-ancestors.
|     CSP_ENABLED=false la desactiva (solo para depurar).
| EN: Directives set by App\Http\Middleware\SecurityHeaders. {nonce} is
|     replaced by each request's nonce. Everything is served from the site
|     itself: no third-party fonts or scripts. If you embed the page in your
|     site with an iframe, add your domain to frame-ancestors.
|     CSP_ENABLED=false turns it off (for debugging only).
|==============================================================================
*/

return [
    'enabled' => env('CSP_ENABLED', true),

    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'nonce-{nonce}'", "'strict-dynamic'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:'],
        'font-src' => ["'self'"],
        'connect-src' => ["'self'"],
        'worker-src' => ["'self'", 'blob:'],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'self'"],
    ],
];
