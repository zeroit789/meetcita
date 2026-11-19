<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ES: Proxies de confianza desde la config (.env TRUSTED_PROXIES), no fijados
 *     en el código (F7). Solo una IP de confianza puede dictar la IP real del
 *     cliente con X-Forwarded-For; de cualquier otra se ignora la cabecera.
 * EN: Trusted proxies from the config (.env TRUSTED_PROXIES), not hard-coded
 *     (F7). Only a trusted IP may set the client's real IP with
 *     X-Forwarded-For; from any other one the header is ignored.
 */
class TrustedProxiesTest extends TestCase
{
    /**
     * ES: Ruta de prueba que devuelve la IP que ve Laravel.
     * EN: Test route that returns the IP Laravel sees.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_prueba-ip', fn () => request()->ip());
    }

    public function test_un_proxy_de_confianza_pasa_la_ip_real(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.0/8']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_prueba-ip')
            ->assertSee('203.0.113.7');
    }

    public function test_una_ip_que_no_es_de_confianza_no_puede_falsear_la_suya(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.0/8']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_prueba-ip')
            ->assertSee('198.51.100.9')
            ->assertDontSee('203.0.113.7');
    }

    public function test_sin_proxies_configurados_no_se_confia_en_nadie(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_prueba-ip')
            ->assertSee('10.1.2.3');
    }
}
