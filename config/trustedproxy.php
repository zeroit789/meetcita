<?php

/*
|==============================================================================
| Trusted proxies / Proxies de confianza
|==============================================================================
| ES: IPs o rangos CIDR del proxy inverso que tienes delante (Caddy, Nginx,
|     Traefik, Coolify, Cloudflare…), separados por comas. Solo desde esas IPs
|     se acepta la cabecera X-Forwarded-For con la IP real del cliente.
|     - Vacío (por defecto): no se confía en ningún proxy. Si hay un proxy
|       delante, Laravel verá su IP y el límite de reservas por IP pasará a
|       ser común para todos: rellénalo.
|     - "*": confía en quien te llame. Úsalo SOLO si el servidor no es
|       accesible más que a través del proxy.
|     Lo lee el middleware TrustProxies de Laravel (config('trustedproxy.proxies')).
| EN: IPs or CIDR ranges of the reverse proxy in front of you (Caddy, Nginx,
|     Traefik, Coolify, Cloudflare…), comma-separated. Only from those IPs is
|     the X-Forwarded-For header with the client's real IP accepted.
|     - Empty (default): no proxy is trusted. If there is a proxy in front,
|       Laravel will see its IP and the per-IP booking limit becomes shared
|       by everyone: fill it in.
|     - "*": trust whoever calls you. Use it ONLY if the server can only be
|       reached through the proxy.
|     Read by Laravel's TrustProxies middleware (config('trustedproxy.proxies')).
|==============================================================================
*/

return [
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
