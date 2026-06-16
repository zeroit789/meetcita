# Google Calendar + Meet

> **English** | [Español](#-español)
>
> How to connect Google Calendar so confirmed bookings appear in your calendar and online meetings get an **automatic Google Meet link**. This is **optional** — without it, bookings still work (see [Graceful degradation](#graceful-degradation)).

---

## 🇬🇧 English

### What you'll get

When configured, every **confirmed** booking:

- Creates an event in your Google Calendar.
- If the meeting is **online**, Google generates a **Meet link** automatically and emails it to the client.
- Cancelling a booking deletes its calendar event and notifies the invitees.
- The system also **reads** your calendar so it never offers a slot you're already busy in.

You'll end up filling **five** values in your `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REFRESH_TOKEN=
GOOGLE_CALENDAR_ID=primary
GOOGLE_REDIRECT_URI=http://localhost
```

Follow the steps below to get each one.

---

### Step 1 — Create a project in Google Cloud Console

1. Go to **[console.cloud.google.com](https://console.cloud.google.com)** and sign in with the Google account whose calendar you want to use.
2. In the top bar, click the **project selector** → **New Project**.
3. Give it a name (e.g. `My Appointments`) and click **Create**.
4. Make sure that new project is **selected** in the top bar before continuing.

### Step 2 — Enable the Google Calendar API

1. In the left menu go to **APIs & Services → Library**.
2. Search for **"Google Calendar API"**.
3. Open it and click **Enable**.

### Step 3 — Configure the OAuth consent screen

1. Go to **APIs & Services → OAuth consent screen**.
2. **User type:** choose **External** (unless you have a Google Workspace org and want Internal). Click **Create**.
3. Fill the required fields: **App name**, **User support email**, **Developer contact email**. Save and continue.
4. **Scopes:** click **Add or remove scopes** and add the calendar events scope:
   `https://www.googleapis.com/auth/calendar.events`
   (this is the only scope the app requests). Save and continue.
5. **Test users:** while the app is in *Testing* mode, add the Google account(s) you'll authorize as **test users** (your own email). Save and continue.

> You can leave the app in **Testing** mode for personal use. See [Troubleshooting](#troubleshooting) for the implications.

### Step 4 — Create OAuth 2.0 credentials

1. Go to **APIs & Services → Credentials**.
2. Click **+ Create credentials → OAuth client ID**.
3. **Application type:** choose **Web application**.
4. Give it a name (e.g. `Appointments client`).
5. Under **Authorized redirect URIs**, click **+ Add URI** and enter your redirect URI.
   - It must **exactly match** the value of `GOOGLE_REDIRECT_URI` in your `.env`.
   - The default in this project is **`http://localhost`** — so add exactly `http://localhost`.
6. Click **Create**. Google shows your **Client ID** and **Client Secret**.

### Step 5 — Put the Client ID and Secret in your .env

Copy the two values into your `.env`:

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost
```

> `GOOGLE_REDIRECT_URI` must match the redirect URI you added in step 4, character for character.

### Step 6 — Get the refresh token (two-step command)

The project ships an artisan command, `google:auth`, that gets the long-lived **refresh token**. It runs in **two steps**:

```bash
# Step 6.1 — print the authorization URL
php artisan google:auth
```

- It prints a URL. **Open it in your browser**, sign in with the Google account and **authorize** calendar access.
- Google then gives you a **code** (an authorization code).

```bash
# Step 6.2 — exchange that code for the tokens
php artisan google:auth --code=THE_CODE_GOOGLE_GAVE_YOU
```

- This prints a line ready to paste:
  ```env
  GOOGLE_REFRESH_TOKEN=1//0g...your-refresh-token
  ```
- Copy that whole line into your `.env`.

> The command uses `access_type=offline` and `prompt=consent`, which forces Google to return a refresh token. If it ever reports *"Google did not return a refresh_token"*, revoke the app at [myaccount.google.com/permissions](https://myaccount.google.com/permissions) and run step 6.1 again.

### Step 7 — Choose the target calendar (GOOGLE_CALENDAR_ID)

```env
GOOGLE_CALENDAR_ID=primary
```

- **`primary`** = the main calendar of the authorized account (the usual choice).
- To use a **specific** calendar instead: open **[Google Calendar](https://calendar.google.com)** → hover the calendar in the left sidebar → **⋮ → Settings and sharing** → scroll to **Integrate calendar** → copy the **Calendar ID** (it looks like an email, e.g. `abc123@group.calendar.google.com`) and paste it as `GOOGLE_CALENDAR_ID`.

### Done

That's it. From now on, confirming a booking creates the calendar event (with a Meet link if it's online), and cancelling it removes the event.

---

### Graceful degradation

If `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` **or** `GOOGLE_REFRESH_TOKEN` is empty, the integration is simply **off**:

- Bookings still work end to end.
- Online meetings get **no automatic Meet link**.
- The calendar is **not** synced (events aren't created/deleted) and busy detection from Google is skipped.

No errors are thrown — any Google failure is caught and logged, and the booking flow continues.

### Troubleshooting

| Problem | Cause / fix |
|---|---|
| **`redirect_uri_mismatch`** | The redirect URI in Google Cloud doesn't match `GOOGLE_REDIRECT_URI`. They must be **identical** (including `http`/`https`, no trailing slash differences). |
| **"Google did not return a refresh_token"** | Google only returns it on the first consent. Revoke access at [myaccount.google.com/permissions](https://myaccount.google.com/permissions), then run `php artisan google:auth` again. |
| **`invalid_grant`** | The authorization code expired (use it quickly), was already used, or the refresh token was revoked. Redo step 6. |
| **App in *Testing* / "unverified app" screen** | Normal while in Testing mode. Make sure your account is listed as a **test user** (step 3.5). For wider use, submit the app for verification in the consent screen. |
| **Events not appearing** | Check `GOOGLE_CALENDAR_ID` points to the right calendar, and review `storage/logs/laravel.log` — Google errors are logged there. |

---
---

## 🇪🇸 Español

> [English](#-english) | **Español**

Cómo conectar Google Calendar para que las citas confirmadas aparezcan en tu calendario y las citas online reciban un **enlace de Google Meet automático**. Es **opcional** — sin esto, las citas siguen funcionando (ver [Degradación con gracia](#degradación-con-gracia)).

### Qué consigues

Una vez configurado, cada cita **confirmada**:

- Crea un evento en tu Google Calendar.
- Si la cita es **online**, Google genera un **enlace de Meet** automáticamente y se lo envía por email al cliente.
- Al cancelar una cita, se borra su evento de calendario y se avisa a los invitados.
- El sistema también **lee** tu calendario para no ofrecer nunca un hueco en el que ya estás ocupado.

Acabarás rellenando **cinco** valores en tu `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REFRESH_TOKEN=
GOOGLE_CALENDAR_ID=primary
GOOGLE_REDIRECT_URI=http://localhost
```

Sigue los pasos de abajo para sacar cada uno.

---

### Paso 1 — Crea un proyecto en Google Cloud Console

1. Entra en **[console.cloud.google.com](https://console.cloud.google.com)** e inicia sesión con la cuenta de Google cuyo calendario quieres usar.
2. En la barra superior, pulsa el **selector de proyecto** → **Nuevo proyecto**.
3. Ponle un nombre (p. ej. `Mis Citas`) y pulsa **Crear**.
4. Asegúrate de que ese nuevo proyecto esté **seleccionado** en la barra superior antes de continuar.

### Paso 2 — Habilita la Google Calendar API

1. En el menú de la izquierda ve a **APIs y servicios → Biblioteca**.
2. Busca **"Google Calendar API"**.
3. Ábrela y pulsa **Habilitar**.

### Paso 3 — Configura la pantalla de consentimiento OAuth

1. Ve a **APIs y servicios → Pantalla de consentimiento de OAuth**.
2. **Tipo de usuario:** elige **Externo** (salvo que tengas una organización Google Workspace y quieras Interno). Pulsa **Crear**.
3. Rellena los campos obligatorios: **Nombre de la app**, **Correo de asistencia al usuario**, **Correo del desarrollador**. Guarda y continúa.
4. **Scopes (permisos):** pulsa **Añadir o quitar permisos** y añade el scope de eventos del calendario:
   `https://www.googleapis.com/auth/calendar.events`
   (es el único scope que pide la app). Guarda y continúa.
5. **Usuarios de prueba:** mientras la app esté en modo *Prueba*, añade como **usuarios de prueba** la(s) cuenta(s) de Google que vas a autorizar (tu propio email). Guarda y continúa.

> Puedes dejar la app en modo **Prueba** para uso personal. Mira [Solución de problemas](#solución-de-problemas) para sus implicaciones.

### Paso 4 — Crea credenciales OAuth 2.0

1. Ve a **APIs y servicios → Credenciales**.
2. Pulsa **+ Crear credenciales → ID de cliente de OAuth**.
3. **Tipo de aplicación:** elige **Aplicación web**.
4. Ponle un nombre (p. ej. `Cliente Citas`).
5. En **URIs de redirección autorizados**, pulsa **+ Añadir URI** e introduce tu redirect URI.
   - Debe **coincidir EXACTAMENTE** con el valor de `GOOGLE_REDIRECT_URI` de tu `.env`.
   - El valor por defecto de este proyecto es **`http://localhost`** — así que añade exactamente `http://localhost`.
6. Pulsa **Crear**. Google te muestra tu **Client ID** y tu **Client Secret**.

### Paso 5 — Pon el Client ID y el Secret en tu .env

Copia los dos valores en tu `.env`:

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=http://localhost
```

> `GOOGLE_REDIRECT_URI` debe coincidir con el redirect URI que añadiste en el paso 4, carácter por carácter.

### Paso 6 — Obtén el refresh token (comando de dos pasos)

El proyecto trae un comando de artisan, `google:auth`, que obtiene el **refresh token** (token de larga duración). Funciona en **dos pasos**:

```bash
# Paso 6.1 — imprime la URL de autorización
php artisan google:auth
```

- Imprime una URL. **Ábrela en el navegador**, inicia sesión con la cuenta de Google y **autoriza** el acceso al calendario.
- Google te devuelve entonces un **code** (código de autorización).

```bash
# Paso 6.2 — canjea ese código por los tokens
php artisan google:auth --code=EL_CODIGO_QUE_TE_DA_GOOGLE
```

- Esto imprime una línea lista para pegar:
  ```env
  GOOGLE_REFRESH_TOKEN=1//0g...tu-refresh-token
  ```
- Copia esa línea entera en tu `.env`.

> El comando usa `access_type=offline` y `prompt=consent`, lo que obliga a Google a devolver un refresh token. Si alguna vez avisa de que *"Google no devolvió refresh_token"*, revoca el acceso de la app en [myaccount.google.com/permissions](https://myaccount.google.com/permissions) y vuelve a ejecutar el paso 6.1.

### Paso 7 — Elige el calendario destino (GOOGLE_CALENDAR_ID)

```env
GOOGLE_CALENDAR_ID=primary
```

- **`primary`** = el calendario principal de la cuenta autorizada (lo habitual).
- Para usar un calendario **concreto**: abre **[Google Calendar](https://calendar.google.com)** → pasa el ratón sobre el calendario en la barra izquierda → **⋮ → Configuración y uso compartido** → baja hasta **Integrar el calendario** → copia el **ID del calendario** (parece un email, p. ej. `abc123@group.calendar.google.com`) y pégalo como `GOOGLE_CALENDAR_ID`.

### Listo

Eso es todo. A partir de ahora, confirmar una cita crea el evento de calendario (con enlace de Meet si es online), y cancelarla elimina el evento.

---

### Degradación con gracia

Si `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` **o** `GOOGLE_REFRESH_TOKEN` están vacíos, la integración simplemente está **apagada**:

- Las citas siguen funcionando de principio a fin.
- Las citas online **no** obtienen enlace de Meet automático.
- El calendario **no** se sincroniza (no se crean/borran eventos) y se omite la detección de ocupación desde Google.

No se lanza ningún error — cualquier fallo de Google se captura y se registra, y el flujo de reserva continúa.

### Solución de problemas

| Problema | Causa / solución |
|---|---|
| **`redirect_uri_mismatch`** | El redirect URI de Google Cloud no coincide con `GOOGLE_REDIRECT_URI`. Deben ser **idénticos** (incluido `http`/`https`, sin diferencias de barra final). |
| **"Google no devolvió refresh_token"** | Google solo lo devuelve en el primer consentimiento. Revoca el acceso en [myaccount.google.com/permissions](https://myaccount.google.com/permissions) y ejecuta de nuevo `php artisan google:auth`. |
| **`invalid_grant`** | El código de autorización caducó (úsalo rápido), ya se usó, o el refresh token fue revocado. Rehaz el paso 6. |
| **App en modo *Prueba* / pantalla de "app no verificada"** | Es normal en modo Prueba. Asegúrate de que tu cuenta esté listada como **usuario de prueba** (paso 3.5). Para un uso más amplio, manda la app a verificación en la pantalla de consentimiento. |
| **Los eventos no aparecen** | Comprueba que `GOOGLE_CALENDAR_ID` apunta al calendario correcto y revisa `storage/logs/laravel.log` — los errores de Google se registran ahí. |
