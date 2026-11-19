<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ES: Cabeceras de seguridad en todas las páginas: CSP con nonce (sin
 *     'unsafe-eval' ni dominios de terceros), nosniff, Referrer-Policy,
 *     Permissions-Policy y protección contra el encuadre en otras webs.
 * EN: Security headers on every page: CSP with a nonce (no 'unsafe-eval' nor
 *     third-party domains), nosniff, Referrer-Policy, Permissions-Policy and
 *     protection against being framed by other sites.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_citas_lleva_las_cabeceras(): void
    {
        $respuesta = $this->get('/citas')->assertOk();

        $csp = (string) $respuesta->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('bunny.net', $csp);
        $this->assertSame('nosniff', $respuesta->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $respuesta->headers->get('Referrer-Policy'));
        $this->assertNotNull($respuesta->headers->get('Permissions-Policy'));
        $this->assertNull($respuesta->headers->get('X-Powered-By'));
    }

    public function test_los_scripts_en_linea_llevan_el_nonce_de_la_cabecera(): void
    {
        $respuesta = $this->get('/citas');
        preg_match("/'nonce-([A-Za-z0-9]+)'/", (string) $respuesta->headers->get('Content-Security-Policy'), $m);

        $html = $respuesta->getContent();
        preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/', $html, $inline);
        $this->assertNotEmpty($inline[1]);
        foreach ($inline[1] as $atributos) {
            $this->assertStringContainsString('nonce="'.$m[1].'"', $atributos);
        }
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
    }

    public function test_el_panel_tambien_lleva_csp(): void
    {
        $this->get('/panel/login')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotNull($this->get('/panel/login')->headers->get('Content-Security-Policy'));
    }
}
