<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/*
|==============================================================================
| Middleware de idioma (i18n) / SetLocale middleware
|==============================================================================
| ES: Fija el locale de la aplicación en CADA petición web, por este orden:
|       1. La cookie 'locale', si trae un idioma soportado. La escribe la ruta
|          /lang/{locale} (el botón de idioma) y dura un año.
|       2. Sin cookie (primera visita): el primer idioma soportado de la
|          cabecera Accept-Language del navegador, respetando sus prioridades
|          (q). "en-US" cuenta como "en". No se guarda cookie: solo el selector
|          la crea.
|       3. Si nada de lo anterior encaja: config('appointments.default_locale').
|     Así cada página se sirve ya renderizada en su idioma, sin JavaScript. Los
|     idiomas soportados salen de config('appointments.locales').
| EN: Sets the app locale on EVERY web request, in this order:
|       1. The 'locale' cookie, if it holds a supported language. Written by the
|          /lang/{locale} route (the language button); it lasts one year.
|       2. No cookie (first visit): the first supported language in the
|          browser's Accept-Language header, honouring its priorities (q).
|          "en-US" counts as "en". No cookie is stored: only the switcher sets it.
|       3. If nothing matches: config('appointments.default_locale').
|     So every page is served already rendered in its language, without
|     JavaScript. Supported languages come from config('appointments.locales').
|==============================================================================
*/
class SetLocale
{
    /**
     * ES: Idiomas soportados (desde config).
     * EN: Supported languages (from config).
     *
     * @return array<int, string>
     */
    public static function soportados(): array
    {
        return (array) config('appointments.locales', ['en']);
    }

    /**
     * ES: Idioma por defecto cuando no hay cookie válida ni idioma del navegador soportado.
     * EN: Default language when there is no valid cookie nor supported browser language.
     */
    public static function porDefecto(): string
    {
        return (string) config('appointments.default_locale', 'en');
    }

    /**
     * ES: Fija el idioma de la petición: cookie, luego navegador, luego por defecto.
     * EN: Sets the request language: cookie, then browser, then default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ES: 1) Cookie de idioma elegida con el selector (si es un idioma soportado).
        // EN: 1) Language cookie picked with the switcher (if it is supported).
        $locale = (string) $request->cookie('locale');

        if (! in_array($locale, self::soportados(), true)) {
            // ES: 2) y 3) Sin cookie válida: navegador o, si no encaja, el de por defecto.
            // EN: 2) and 3) No valid cookie: browser or, if nothing matches, the default.
            $locale = self::delNavegador($request) ?? self::porDefecto();
        }

        // ES: Fijamos el locale para Blade __(), validaciones, Carbon, etc.
        // EN: Set the locale for Blade __(), validations, Carbon, etc.
        App::setLocale($locale);

        return $next($request);
    }

    /**
     * ES: Primer idioma soportado de la cabecera Accept-Language, en el orden de
     *     preferencia del navegador. Compara solo el idioma principal ("en_US" →
     *     "en"). Devuelve null si no hay cabecera o ninguno encaja.
     * EN: First supported language in the Accept-Language header, in the
     *     browser's order of preference. Compares only the primary language
     *     ("en_US" → "en"). Returns null if there is no header or no match.
     */
    public static function delNavegador(Request $request): ?string
    {
        // ES: getLanguages() ya viene ordenado por prioridad (q) y normalizado ("en_US").
        // EN: getLanguages() is already sorted by priority (q) and normalized ("en_US").
        foreach ($request->getLanguages() as $idioma) {
            $principal = strtolower(strtok($idioma, '_-'));

            if (in_array($principal, self::soportados(), true)) {
                return $principal;
            }
        }

        return null;
    }
}
