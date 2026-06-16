<?php

use App\Http\Controllers\AppointmentIcsController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|==============================================================================
| WEB ROUTES — ROUTE INDEX / ÍNDICE DE RUTAS
|==============================================================================
| EN: Quick map to locate each route of the appointments module (in order below):
| ES: Mapa rápido para ubicar cada ruta del módulo de citas (orden de abajo):
|
|  HOME       GET  /                               -> view('welcome') (demo landing)  [home]
|  CITAS      GET  /citas                          -> view('citas') (Livewire)        [citas]
|  IDIOMA     GET  /lang/{locale}                  -> closure (locale cookie)         [lang.switch]
|  CITAS ICS  GET  /cita/{reference}/calendario.ics -> AppointmentIcsController@show  [cita.ics]
|  TELEGRAM   POST /telegram/webhook               -> TelegramWebhookController@handle [telegram.webhook]
|  PANEL      GET/POST /panel/*                     -> PanelController (login/index/logout) [panel.*]
|
| EN: The booking page (/citas) and the panel are tools, not indexable content.
| ES: La página de reserva (/citas) y el panel son herramientas, no contenido.
|==============================================================================
*/

// ─── HOME / INICIO ────────────────────────────────────────────────────────────
// EN: Demo landing. A minimal page that explains the project and links to /citas
//     and the repo. Replace it with your own home if you embed this in a site.
// ES: Landing demo. Página mínima que explica el proyecto y enlaza a /citas y al
//     repo. Sustitúyela por tu propia home si integras esto en una web.
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ─── CITAS / BOOKING ──────────────────────────────────────────────────────────
// EN: Booking page (the Livewire book-appointment component lives in the view).
// ES: Página de reserva (el componente Livewire vive en la vista).
Route::get('/citas', function () {
    return view('citas');
})->name('citas');

// ─── IDIOMA / LANGUAGE (i18n) ─────────────────────────────────────────────────
// EN: Language switch: sets the 'locale' cookie and returns to the previous page.
//     SetLocale reads it on each request to serve the site in that language.
// ES: Cambio de idioma: fija la cookie 'locale' y vuelve a la página anterior.
//     SetLocale la lee en cada petición para servir la web en ese idioma.
Route::get('/lang/{locale}', function (string $locale) {
    // EN: Only accept supported languages; otherwise the default one.
    // ES: Solo aceptamos idiomas soportados; si no, el de por defecto.
    $locale = in_array($locale, \App\Http\Middleware\SetLocale::soportados(), true)
        ? $locale
        : \App\Http\Middleware\SetLocale::porDefecto();

    // EN: 1-year cookie (525600 min). redirect()->back() keeps the user in place.
    // ES: Cookie de 1 año (525600 min). redirect()->back() deja al usuario donde estaba.
    return redirect()->back()->withCookie(cookie('locale', $locale, 525600));
})->name('lang.switch');

// ─── CITAS · ICS (add to calendar) / añadir al calendario ─────────────────────
// EN: Download a booking's .ics: /cita/{reference}/calendario.ics. Serves the
//     event for Outlook / Apple Calendar (linked from the confirmation email).
//     throttle:20,1 -> max 20 downloads/min per IP (anti-abuse).
// ES: Descarga del .ics de una cita: /cita/{reference}/calendario.ics. Sirve el
//     evento para Outlook / Apple Calendar (enlace en el email de confirmación).
//     throttle:20,1 -> máximo 20 descargas/min por IP (anti-abuso).
Route::get('/cita/{reference}/calendario.ics', [AppointmentIcsController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('cita.ics');

// ─── TELEGRAM (booking bot webhook) / webhook bot citas ───────────────────────
// EN: Telegram bot webhook (booking management). POST without CSRF (see bootstrap/app.php).
//     Its security is the X-Telegram-Bot-Api-Secret-Token header (validated in the controller).
// ES: Webhook del bot de Telegram (gestión de citas). POST sin CSRF (ver bootstrap/app.php).
//     Su seguridad la da la cabecera X-Telegram-Bot-Api-Secret-Token (validada en el controlador).
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

/*
|------------------------------------------------------------------------------
| Private bookings panel (/panel) / Panel privado de citas (/panel)
|------------------------------------------------------------------------------
| EN: Protected with a single password (config appointments.panel.password).
|     - /panel/login   -> login form (GET) + attempt (POST)
|     - /panel         -> bookings table (protected by 'appointments.panel' middleware)
|     - /panel/logout  -> log out (POST)
| ES: Protegido con una contraseña única (config appointments.panel.password).
|     - /panel/login   -> formulario de login (GET) + intento (POST)
|     - /panel         -> tabla de citas (protegida por middleware 'appointments.panel')
|     - /panel/logout  -> cerrar sesión (POST)
*/
Route::prefix('panel')->group(function () {
    // EN: Panel login. ES: Login del panel.
    Route::get('/login', [PanelController::class, 'showLogin'])->name('panel.login');
    // EN: throttle:5,1 -> max 5 login attempts/min per IP (anti brute force).
    // ES: throttle:5,1 -> máximo 5 intentos de login/min por IP (anti fuerza bruta).
    Route::post('/login', [PanelController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('panel.login.attempt');

    // EN: Log out. ES: Cerrar sesión.
    Route::post('/logout', [PanelController::class, 'logout'])->name('panel.logout');

    // EN: Main panel (requires the correct password). ES: Panel principal (requiere contraseña correcta).
    Route::get('/', [PanelController::class, 'index'])
        ->middleware('appointments.panel')
        ->name('panel.index');
});
