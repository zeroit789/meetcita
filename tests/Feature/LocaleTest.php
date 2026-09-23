<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ES: Tests del idioma de la web (middleware SetLocale): cookie elegida con el
 *     selector y, si no hay cookie, detección por la cabecera Accept-Language.
 * EN: Tests for the site language (SetLocale middleware): cookie picked with
 *     the switcher and, without a cookie, detection from Accept-Language.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_cookie_ni_cabecera_usa_el_idioma_por_defecto(): void
    {
        // ES: El cliente de pruebas de Symfony manda "en-us" por defecto: lo vaciamos.
        // EN: Symfony's test client sends "en-us" by default: we blank it.
        $this->withHeader('Accept-Language', '')->get('/citas')->assertOk();

        $this->assertSame('es', app()->getLocale());
    }

    public function test_sin_cookie_detecta_el_idioma_del_navegador(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/citas')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_respeta_el_orden_de_preferencia_del_navegador(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8,es;q=0.7')->get('/citas');

        $this->assertSame('en', app()->getLocale());
    }

    public function test_un_idioma_no_soportado_cae_al_de_por_defecto(): void
    {
        config(['appointments.default_locale' => 'en']);

        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->get('/citas');

        $this->assertSame('en', app()->getLocale());
    }

    public function test_la_cookie_manda_sobre_el_navegador(): void
    {
        $this->withCookie('locale', 'es')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/citas');

        $this->assertSame('es', app()->getLocale());
    }

    public function test_el_selector_guarda_la_cookie_de_idioma(): void
    {
        $this->from('/citas')->get('/lang/en')
            ->assertRedirect('/citas')
            ->assertCookie('locale', 'en');
    }

    public function test_el_selector_ignora_idiomas_no_soportados(): void
    {
        $this->from('/citas')->get('/lang/fr')->assertCookie('locale', 'es');
    }
}
