<?php

namespace Tests;

use App\Http\Middleware\AdminPanelPassword;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * ES: Clase base de los tests de Laravel (arranca la aplicación). Deja un
 *     entorno determinista: configuración de citas fija (sin depender del .env
 *     local), Google y Telegram desactivados y un "ahora" congelado.
 * EN: Base class for Laravel tests (boots the application). Sets up a
 *     deterministic environment: fixed appointments config (independent of the
 *     local .env), Google and Telegram disabled and a frozen "now".
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * ES: "Ahora" de todos los tests: lunes 21-09-2026 a las 08:00 en Madrid,
     *     antes de la apertura (09:30), así hoy tiene todos sus huecos libres.
     * EN: "Now" for every test: Monday 2026-09-21 08:00 in Madrid, before
     *     opening time (09:30), so today still has all its slots free.
     */
    public const AHORA = '2026-09-21 08:00:00';

    /**
     * ES: Prepara la configuración y congela el reloj antes de cada test.
     * EN: Prepares the config and freezes the clock before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ES: Horario y reglas de negocio fijos (los valores por defecto del repo).
        // EN: Fixed schedule and business rules (the repo defaults).
        config([
            'appointments.timezone' => 'Europe/Madrid',
            'appointments.schedule.days_ahead' => 14,
            'appointments.schedule.slot_minutes' => 30,
            'appointments.schedule.open' => '09:30',
            'appointments.schedule.close' => '18:00',
            'appointments.schedule.durations' => [30, 60],
            'appointments.schedule.weekdays' => [1, 2, 3, 4, 5],
            // ES: Sin horario por día: manda el atajo open/close + weekdays.
            // EN: No per-day schedule: the open/close + weekdays shortcut rules.
            'appointments.schedule.hours' => [],
            'appointments.schedule.max_attendees' => 10,
            'appointments.modalities.online' => true,
            'appointments.modalities.in_person' => true,
            'appointments.locales' => ['es', 'en'],
            'appointments.default_locale' => 'es',
            'appointments.brand.owner_email' => 'owner@example.com',
            'appointments.panel.password' => 'secreto-de-test',
            // ES: Autogestión con 24 h de plazo. EN: Self-service with 24 h notice.
            'appointments.self_service.enabled' => true,
            'appointments.self_service.hours_before' => 24,
            'appointments.brand.phone' => '',
            // ES: Recordatorios activos desde las 09:00. EN: Reminders on from 09:00.
            'appointments.reminders.enabled' => true,
            'appointments.reminders.from' => '09:00',
            // ES: Botón de WhatsApp apagado (como por defecto). EN: WhatsApp button off (as by default).
            'appointments.whatsapp.link' => false,
            'appointments.whatsapp.country_code' => '34',
            // ES: Integraciones externas apagadas: ningún test llama a Google ni a Telegram.
            // EN: External integrations off: no test calls Google or Telegram.
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.refresh_token' => null,
            'services.telegram.token' => null,
            'services.telegram.chat_id' => null,
            'services.telegram.webhook_secret' => null,
            'services.altcha.enabled' => false,
        ]);

        // ES: Reloj congelado en la zona del negocio.
        // EN: Clock frozen in the business time zone.
        Carbon::setTestNow(Carbon::parse(self::AHORA, 'Europe/Madrid'));
    }

    /**
     * ES: Simula una sesión válida del panel (la misma huella que deja el login).
     * EN: Simulates a valid panel session (the same fingerprint the login sets).
     */
    protected function entrarAlPanel(): static
    {
        session([AdminPanelPassword::CLAVE_SESION => AdminPanelPassword::huella()]);

        return $this;
    }

    /**
     * ES: Descongela el reloj al terminar cada test.
     * EN: Unfreezes the clock after each test.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
