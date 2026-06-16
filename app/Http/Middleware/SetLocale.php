<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/*
|==============================================================================
| SetLocale middleware / Middleware de idioma (i18n)
|==============================================================================
| EN: Sets the app locale on EVERY web request from the 'locale' cookie. Thanks
|     to this, each page is served already rendered in the chosen language (the
|     default one otherwise) and the preference persists while browsing the whole
|     site, without relying on JavaScript. The language button points to the
|     /lang/{locale} route, which writes this cookie.
|     Supported languages come from config('appointments.locales') and the default
|     from config('appointments.default_locale').
| ES: Fija el locale de la aplicación en CADA petición web según la cookie
|     'locale'. Gracias a esto, cada página se sirve ya renderizada en el idioma
|     elegido (el de por defecto si no) y la preferencia se mantiene al navegar
|     por toda la web sin depender de JavaScript. El botón de idioma apunta a la
|     ruta /lang/{locale}, que escribe esta cookie.
|     Los idiomas soportados salen de config('appointments.locales') y el de por
|     defecto de config('appointments.default_locale').
|==============================================================================
*/
class SetLocale
{
    /**
     * EN: Supported languages (from config). The first one is the default.
     * ES: Idiomas soportados (desde config). El primero es el de por defecto.
     *
     * @return array<int, string>
     */
    public static function soportados(): array
    {
        return (array) config('appointments.locales', ['en']);
    }

    /**
     * EN: Default language when there is no valid cookie.
     * ES: Idioma por defecto cuando no hay cookie válida.
     */
    public static function porDefecto(): string
    {
        return (string) config('appointments.default_locale', 'en');
    }

    public function handle(Request $request, Closure $next): Response
    {
        // EN: Read the language cookie. If missing or unsupported, fall back to
        //     the default language.
        // ES: Leemos la cookie de idioma. Si no existe o no es un idioma soportado,
        //     caemos al idioma por defecto.
        $locale = (string) $request->cookie('locale');
        if (! in_array($locale, self::soportados(), true)) {
            $locale = self::porDefecto();
        }

        // EN: Set the locale for Blade __(), validations, Carbon, etc.
        // ES: Fijamos el locale para Blade __(), validaciones, Carbon, etc.
        App::setLocale($locale);

        return $next($request);
    }
}
