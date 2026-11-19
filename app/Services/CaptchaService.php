<?php

namespace App\Services;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Illuminate\Support\Facades\Cache;
use Throwable;

/*
|==============================================================================
| CaptchaService — captcha propio ALTCHA / own ALTCHA captcha
|==============================================================================
| ES: Captcha de prueba de trabajo (ALTCHA): el navegador resuelve un reto
|     matemático en segundo plano. Sin cookies, sin terceros y sin imágenes que
|     descifrar: el reto lo genera y lo comprueba este mismo servidor.
|       - crearReto(): reto firmado con HMAC (GET /altcha/challenge).
|       - verificar(): comprueba la solución y que no se haya usado antes.
|     Se desactiva con ALTCHA_ENABLED=false (p. ej. en una intranet).
| EN: Proof-of-work captcha (ALTCHA): the browser solves a math challenge in
|     the background. No cookies, no third parties and no images to decipher:
|     this very server creates and checks the challenge.
|       - crearReto(): HMAC-signed challenge (GET /altcha/challenge).
|       - verificar(): checks the solution and that it wasn't used before.
|     Turn it off with ALTCHA_ENABLED=false (e.g. on an intranet).
|==============================================================================
*/
class CaptchaService
{
    /**
     * ES: ¿Está activado el captcha? EN: Is the captcha enabled?
     */
    public function activado(): bool
    {
        return (bool) config('services.altcha.enabled', true);
    }

    /**
     * ES: Crea un reto nuevo (array listo para devolver como JSON al widget).
     *     El contador aleatorio fija cuánto trabajo cuesta resolverlo.
     * EN: Creates a new challenge (array ready to return as JSON to the widget).
     *     The random counter sets how much work solving it takes.
     *
     * @return array<string, mixed>
     */
    public function crearReto(): array
    {
        $min = (int) config('services.altcha.counter_min');
        $max = (int) config('services.altcha.counter_max');

        return $this->altcha()->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2,
            cost: (int) config('services.altcha.cost'),
            counter: random_int($min, max($min, $max)),
            expiresAt: time() + (int) config('services.altcha.expires'),
        ))->toArray();
    }

    /**
     * ES: Comprueba la solución que manda el widget (base64 o JSON). Cada reto
     *     vale una sola vez: se apunta su firma en caché hasta que caduca.
     * EN: Checks the solution sent by the widget (base64 or JSON). Each
     *     challenge works once: its signature is cached until it expires.
     */
    public function verificar(?string $payload): bool
    {
        $payload = trim((string) $payload);
        if ($payload === '' || strlen($payload) > 8192) {
            return false;
        }

        try {
            $datos = str_starts_with($payload, '{')
                ? Payload::fromArray((array) json_decode($payload, true, 512, JSON_THROW_ON_ERROR))
                : Payload::fromBase64($payload);

            $resultado = $this->altcha()->verifySolution(new VerifySolutionOptions(
                payload: $datos,
                algorithm: new Pbkdf2,
            ));
        } catch (Throwable) {
            return false; // ES: mal formado o manipulado / EN: malformed or tampered
        }

        if (! $resultado->verified) {
            return false;
        }

        // ES: Un solo uso por reto. EN: One use per challenge.
        $clave = 'altcha_usado:'.hash('sha256', (string) $datos->challenge->signature);

        return Cache::add($clave, true, (int) config('services.altcha.expires') + 60);
    }

    /**
     * ES: Cliente ALTCHA con claves derivadas de ALTCHA_HMAC_KEY (o de APP_KEY).
     * EN: ALTCHA client with keys derived from ALTCHA_HMAC_KEY (or APP_KEY).
     */
    public function altcha(): Altcha
    {
        $base = (string) (config('services.altcha.hmac_key') ?: config('app.key'));

        return new Altcha(
            hmacSignatureSecret: hash_hmac('sha256', 'altcha-reto', $base),
            hmacKeySignatureSecret: hash_hmac('sha256', 'altcha-clave', $base),
        );
    }
}
