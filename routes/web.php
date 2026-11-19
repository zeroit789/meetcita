<?php

use App\Http\Controllers\AppointmentIcsController;
use App\Http\Controllers\GestionarCitaController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\SetLocale;
use App\Services\CaptchaService;
use Illuminate\Support\Facades\Route;

/*
|==============================================================================
| WEB ROUTES — ROUTE INDEX / ÍNDICE DE RUTAS
|==============================================================================
| ES: Mapa rápido para ubicar cada ruta del módulo de citas (orden de abajo):
| EN: Quick map to locate each route of the appointments module (in order below):
|
|  HOME       GET  /                               -> view('welcome') (demo landing)  [home]
|  CITAS      GET  /citas                          -> view('citas') (Livewire)        [citas]
|  IDIOMA     GET  /lang/{locale}                  -> closure (locale cookie)         [lang.switch]
|  CITAS ICS  GET  /cita/{reference}/calendario.ics -> AppointmentIcsController@show  [cita.ics]
|  GESTIONAR  GET  /cita/{reference}/gestionar     -> GestionarCitaController@show (firmada) [cita.gestionar]
|  CAPTCHA    GET  /altcha/challenge               -> closure (ALTCHA challenge JSON) [altcha.challenge]
|  TELEGRAM   POST /telegram/webhook               -> TelegramWebhookController@handle [telegram.webhook]
|  PANEL      GET/POST /panel/*                     -> PanelController (login/index/logout) [panel.*]
|
| ES: Cada throttle lleva un prefijo propio (3.er parámetro: ics, gestionar,
|     altcha, panel-login). Sin él, Laravel usa UN contador por IP para todas
|     las rutas limitadas: abrir varias veces la página de gestionar o pedir
|     retos del captcha agotaba los 5 intentos del login del panel.
| EN: Each throttle has its own prefix (3rd parameter: ics, gestionar,
|     altcha, panel-login). Without it, Laravel uses ONE counter per IP for
|     every throttled route: opening the manage page a few times or fetching
|     captcha challenges used up the panel login's 5 attempts.
|
| ES: La página de reserva (/citas) y el panel son herramientas, no contenido.
| EN: The booking page (/citas) and the panel are tools, not indexable content.
|==============================================================================
*/

// ─── HOME / INICIO ────────────────────────────────────────────────────────────
// ES: Landing demo. Página mínima que explica el proyecto y enlaza a /citas y al
//     repo. Sustitúyela por tu propia home si integras esto en una web.
// EN: Demo landing. A minimal page that explains the project and links to /citas
//     and the repo. Replace it with your own home if you embed this in a site.
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ─── CITAS / BOOKING ──────────────────────────────────────────────────────────
// ES: Página de reserva (el componente Livewire vive en la vista).
// EN: Booking page (the Livewire book-appointment component lives in the view).
Route::get('/citas', function () {
    return view('citas');
})->name('citas');

// ─── IDIOMA / LANGUAGE (i18n) ─────────────────────────────────────────────────
// ES: Cambio de idioma: fija la cookie 'locale' y vuelve a la página anterior.
//     SetLocale la lee en cada petición para servir la web en ese idioma.
// EN: Language switch: sets the 'locale' cookie and returns to the previous page.
//     SetLocale reads it on each request to serve the site in that language.
Route::get('/lang/{locale}', function (string $locale) {
    // ES: Solo aceptamos idiomas soportados; si no, el de por defecto.
    // EN: Only accept supported languages; otherwise the default one.
    $locale = in_array($locale, SetLocale::soportados(), true)
        ? $locale
        : SetLocale::porDefecto();

    // ES: Cookie de 1 año (525600 min). redirect()->back() deja al usuario donde estaba.
    // EN: 1-year cookie (525600 min). redirect()->back() keeps the user in place.
    return redirect()->back()->withCookie(cookie('locale', $locale, 525600));
})->name('lang.switch');

// ─── CITAS · ICS (add to calendar) / añadir al calendario ─────────────────────
// ES: Descarga del .ics de una cita: /cita/{reference}/calendario.ics. Sirve el
//     evento para Outlook / Apple Calendar (enlace en el email de confirmación).
//     throttle:20,1 -> máximo 20 descargas/min por IP (anti-abuso).
// EN: Download a booking's .ics: /cita/{reference}/calendario.ics. Serves the
//     event for Outlook / Apple Calendar (linked from the confirmation email).
//     throttle:20,1 -> max 20 downloads/min per IP (anti-abuse).
Route::get('/cita/{reference}/calendario.ics', [AppointmentIcsController::class, 'show'])
    ->middleware('throttle:20,1,ics')
    ->name('cita.ics');

// ─── CITAS · GESTIONAR (manage my booking) / gestionar mi cita ────────────────
// ES: Página del enlace FIRMADO de los correos al cliente para cancelar o
//     cambiar su cita sin cuenta. La firma (sobre la ruta relativa, como el
//     .ics), la caducidad y el token los valida el controlador ANTES de
//     enseñar nada; si el enlace no vale responde 403 con una página propia
//     y amable (en vez del middleware 'signed', que daba un 403 pelado). El
//     GET solo muestra; las acciones son POST (Livewire).
//     throttle:30,1 -> máximo 30 cargas/min por IP.
// EN: Page of the SIGNED link in the client emails to cancel or change the
//     booking without an account. The signature (over the relative path,
//     like the .ics), the expiry and the token are validated by the
//     controller BEFORE showing anything; if the link isn't valid it answers
//     403 with its own friendly page (instead of the 'signed' middleware,
//     which gave a bare 403). The GET only shows; actions are POST (Livewire).
//     throttle:30,1 -> max 30 loads/min per IP.
Route::get('/cita/{reference}/gestionar', [GestionarCitaController::class, 'show'])
    ->middleware('throttle:30,1,gestionar')
    ->name('cita.gestionar');

// ─── CAPTCHA (ALTCHA) ─────────────────────────────────────────────────────────
// ES: Reto del captcha propio (JSON). Sin caché y limitado a 30/min por IP.
// EN: Own captcha challenge (JSON). Not cached and limited to 30/min per IP.
Route::get('/altcha/challenge', fn (CaptchaService $captcha) => response()
    ->json($captcha->crearReto())
    ->header('Cache-Control', 'no-store, private'))
    ->middleware('throttle:30,1,altcha')
    ->name('altcha.challenge');

// ─── TELEGRAM (booking bot webhook) / webhook bot citas ───────────────────────
// ES: Webhook del bot de Telegram (gestión de citas). POST sin CSRF (ver bootstrap/app.php).
//     Su seguridad la da la cabecera X-Telegram-Bot-Api-Secret-Token (validada en el controlador).
// EN: Telegram bot webhook (booking management). POST without CSRF (see bootstrap/app.php).
//     Its security is the X-Telegram-Bot-Api-Secret-Token header (validated in the controller).
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

/*
|------------------------------------------------------------------------------
| Private bookings panel (/panel) / Panel privado de citas (/panel)
|------------------------------------------------------------------------------
| ES: Protegido con una contraseña única (config appointments.panel.password).
|     - /panel/login   -> formulario de login (GET) + intento (POST)
|     - /panel         -> tabla de citas (protegida por middleware 'appointments.panel')
|     - /panel/logout  -> cerrar sesión (POST)
| EN: Protected with a single password (config appointments.panel.password).
|     - /panel/login   -> login form (GET) + attempt (POST)
|     - /panel         -> bookings table (protected by 'appointments.panel' middleware)
|     - /panel/logout  -> log out (POST)
*/
Route::prefix('panel')->group(function () {
    // ES: Login del panel. EN: Panel login.
    Route::get('/login', [PanelController::class, 'showLogin'])->name('panel.login');
    // ES: throttle:5,1 -> máximo 5 intentos de login/min por IP (anti fuerza bruta).
    // EN: throttle:5,1 -> max 5 login attempts/min per IP (anti brute force).
    Route::post('/login', [PanelController::class, 'login'])
        ->middleware('throttle:5,1,panel-login')
        ->name('panel.login.attempt');

    // ES: Cerrar sesión. EN: Log out.
    Route::post('/logout', [PanelController::class, 'logout'])->name('panel.logout');

    // ES: Panel principal (requiere contraseña correcta). EN: Main panel (requires the correct password).
    Route::get('/', [PanelController::class, 'index'])
        ->middleware('appointments.panel')
        ->name('panel.index');
});
