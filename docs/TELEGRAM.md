# Telegram bot

> **English** | [Español](#-español)
>
> How to set up a Telegram bot so you get an instant alert for every new booking and can **confirm or reject** it from your phone with two buttons. This is **optional** — without it, you manage everything from the `/panel`.

---

## 🇬🇧 English

### What you'll get

When configured, every new booking sends you a Telegram message with two buttons:

- **✅ Confirm** → marks the booking as confirmed, sends the confirmation email to the client and creates the Google Calendar event (if Google is configured).
- **❌ Not possible** → asks you to type a **reason**; your next message becomes the cancellation reason, which is emailed to the client.

You'll end up filling **three** values in your `.env`:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
```

Follow the steps below to get each one.

---

### Step 1 — Create the bot with @BotFather (TELEGRAM_BOT_TOKEN)

1. In Telegram, open a chat with **[@BotFather](https://t.me/BotFather)**.
2. Send the command **`/newbot`**.
3. Follow the prompts: give the bot a **name** and a **username** (the username must end in `bot`).
4. BotFather replies with a **token** that looks like `123456789:AAEx...`.
5. Copy it into your `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

### Step 2 — Get your own chat id (TELEGRAM_CHAT_ID)

This is the chat where alerts will arrive (your own personal chat). Two reliable methods:

**Method A — getUpdates (works for sure):**

1. Open a chat with **your bot** (search its username) and send it any message, e.g. `hello`.
2. In a browser, open (replace `<TOKEN>` with your bot token):
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
3. In the JSON response, find `"chat":{"id":...}`. That number is your **chat id**.

**Method B — @userinfobot:**

1. Open a chat with **[@userinfobot](https://t.me/userinfobot)** and send any message.
2. It replies with your numeric **Id**.

Then put it in your `.env`:
```env
TELEGRAM_CHAT_ID=123456789
```

> Only this chat id can operate the bot — actions from any other chat are ignored.

### Step 3 — Invent a webhook secret (TELEGRAM_WEBHOOK_SECRET)

Make up any random string. It secures the webhook: Telegram sends it back in a header and the app rejects any request that doesn't carry it.

```env
TELEGRAM_WEBHOOK_SECRET=a-long-random-string-you-invent
```

> A good way to generate one: `php -r "echo bin2hex(random_bytes(24));"`.

### Step 4 — Register the webhook

The project ships an artisan command to register the webhook with Telegram:

```bash
# Uses APP_URL + /telegram/webhook by default
php artisan telegram:set-webhook
```

- It points Telegram to **`APP_URL/telegram/webhook`** (using your `.env` `APP_URL`).
- To override the URL explicitly:
  ```bash
  php artisan telegram:set-webhook --url=https://yourdomain.com/telegram/webhook
  ```

> **Important:** Telegram requires a **public HTTPS URL**. `http://localhost` will not work.
> For **local testing**, expose your app with a tunnel such as **ngrok**:
> ```bash
> ngrok http 8000
> # then use the https URL ngrok gives you:
> php artisan telegram:set-webhook --url=https://abcd-1234.ngrok-free.app/telegram/webhook
> ```

### Step 5 — Send a test message

Verify the bot can reach you:

```bash
php artisan telegram:test
```

- If configured correctly, you'll receive a test message in your chat.
- You can pass a custom message: `php artisan telegram:test "my custom text"`.

### Done

From now on, each new booking pings your Telegram with the **Confirm** / **Not possible** buttons.

---

### How the flow works

1. A new booking arrives → the bot sends you a message with the booking details and two inline buttons.
2. **✅ Confirm** → the booking is confirmed (atomically, so a double tap can't duplicate anything), the confirmation email is sent to the client, and the Google Calendar event is created.
3. **❌ Not possible** → the bot replies *"Write the reason…"*. Your **next text message** is taken as the cancellation reason: the booking is cancelled, any calendar event is removed, and the reason is emailed to the client.
4. The original message is edited in place to show the final status (e.g. *"✅ CONFIRMED · email sent to client"*).

### Security note

- The webhook is **fail-closed**: it validates the `X-Telegram-Bot-Api-Secret-Token` header against your `TELEGRAM_WEBHOOK_SECRET`. If the secret is missing or doesn't match, the request is rejected with **403**.
- Only actions from your configured `TELEGRAM_CHAT_ID` are processed — anyone else is ignored.

### Troubleshooting

| Problem | Cause / fix |
|---|---|
| **No message arrives on a new booking** | Re-run `php artisan telegram:test`. If that fails, check `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID`. Confirm you sent your bot a message first (so the chat exists). |
| **Buttons do nothing** | The webhook isn't reachable. It needs a **public HTTPS** URL. Re-run `telegram:set-webhook` with the correct `--url` (use ngrok locally). |
| **`telegram:test` says "not configured"** | `TELEGRAM_BOT_TOKEN` and/or `TELEGRAM_CHAT_ID` are empty in `.env`. |
| **Webhook returns 403** | The secret doesn't match. Make sure `TELEGRAM_WEBHOOK_SECRET` is set **and** you ran `telegram:set-webhook` after setting it (the secret is registered with Telegram during that command). |
| **Works locally but not in production** | `APP_URL` must be your real HTTPS domain, and you must re-run `telegram:set-webhook` on the server. |

---
---

## 🇪🇸 Español

> [English](#-english) | **Español**

Cómo configurar un bot de Telegram para recibir un aviso al instante por cada nueva cita y poder **confirmarla o rechazarla** desde el móvil con dos botones. Es **opcional** — sin esto, gestionas todo desde el `/panel`.

### Qué consigues

Una vez configurado, cada nueva cita te envía un mensaje de Telegram con dos botones:

- **✅ Confirmar** → marca la cita como confirmada, envía el email de confirmación al cliente y crea el evento de Google Calendar (si Google está configurado).
- **❌ No me es posible** → te pide que escribas un **motivo**; tu siguiente mensaje se convierte en el motivo de cancelación, que se le envía por email al cliente.

Acabarás rellenando **tres** valores en tu `.env`:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
```

Sigue los pasos de abajo para sacar cada uno.

---

### Paso 1 — Crea el bot con @BotFather (TELEGRAM_BOT_TOKEN)

1. En Telegram, abre un chat con **[@BotFather](https://t.me/BotFather)**.
2. Envía el comando **`/newbot`**.
3. Sigue las indicaciones: dale al bot un **nombre** y un **username** (el username debe terminar en `bot`).
4. BotFather responde con un **token** con esta pinta: `123456789:AAEx...`.
5. Cópialo en tu `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

### Paso 2 — Obtén tu propio chat id (TELEGRAM_CHAT_ID)

Es el chat donde llegarán los avisos (tu chat personal). Dos métodos fiables:

**Método A — getUpdates (funciona seguro):**

1. Abre un chat con **tu bot** (busca su username) y envíale cualquier mensaje, p. ej. `hola`.
2. En un navegador, abre (sustituye `<TOKEN>` por el token de tu bot):
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
3. En la respuesta JSON, busca `"chat":{"id":...}`. Ese número es tu **chat id**.

**Método B — @userinfobot:**

1. Abre un chat con **[@userinfobot](https://t.me/userinfobot)** y envía cualquier mensaje.
2. Te responde con tu **Id** numérico.

Luego ponlo en tu `.env`:
```env
TELEGRAM_CHAT_ID=123456789
```

> Solo este chat id puede operar el bot — las acciones de cualquier otro chat se ignoran.

### Paso 3 — Inventa un secreto del webhook (TELEGRAM_WEBHOOK_SECRET)

Inventa cualquier cadena aleatoria. Asegura el webhook: Telegram la devuelve en una cabecera y la app rechaza cualquier petición que no la traiga.

```env
TELEGRAM_WEBHOOK_SECRET=una-cadena-larga-aleatoria-que-inventas
```

> Una buena forma de generarla: `php -r "echo bin2hex(random_bytes(24));"`.

### Paso 4 — Registra el webhook

El proyecto trae un comando de artisan para registrar el webhook en Telegram:

```bash
# Por defecto usa APP_URL + /telegram/webhook
php artisan telegram:set-webhook
```

- Apunta Telegram a **`APP_URL/telegram/webhook`** (usando el `APP_URL` de tu `.env`).
- Para indicar la URL explícitamente:
  ```bash
  php artisan telegram:set-webhook --url=https://tudominio.com/telegram/webhook
  ```

> **Importante:** Telegram exige una **URL pública con HTTPS**. `http://localhost` no funciona.
> Para **pruebas en local**, expón tu app con un túnel como **ngrok**:
> ```bash
> ngrok http 8000
> # luego usa la URL https que te da ngrok:
> php artisan telegram:set-webhook --url=https://abcd-1234.ngrok-free.app/telegram/webhook
> ```

### Paso 5 — Envía un mensaje de prueba

Verifica que el bot puede llegar a ti:

```bash
php artisan telegram:test
```

- Si está bien configurado, recibirás un mensaje de prueba en tu chat.
- Puedes pasar un mensaje propio: `php artisan telegram:test "mi texto personalizado"`.

### Listo

A partir de ahora, cada nueva cita te avisa por Telegram con los botones **Confirmar** / **No me es posible**.

---

### Cómo funciona el flujo

1. Llega una nueva cita → el bot te envía un mensaje con los datos de la cita y dos botones inline.
2. **✅ Confirmar** → la cita se confirma (de forma atómica, para que un doble clic no duplique nada), se envía el email de confirmación al cliente y se crea el evento de Google Calendar.
3. **❌ No me es posible** → el bot responde *"Escribe el motivo…"*. Tu **siguiente mensaje de texto** se toma como motivo de cancelación: la cita se cancela, se elimina cualquier evento de calendario y el motivo se le envía por email al cliente.
4. El mensaje original se edita en el sitio para mostrar el estado final (p. ej. *"✅ CONFIRMADA · email enviado al cliente"*).

### Nota de seguridad

- El webhook es **fail-closed**: valida la cabecera `X-Telegram-Bot-Api-Secret-Token` contra tu `TELEGRAM_WEBHOOK_SECRET`. Si el secreto falta o no coincide, la petición se rechaza con **403**.
- Solo se procesan las acciones de tu `TELEGRAM_CHAT_ID` configurado — cualquier otro se ignora.

### Solución de problemas

| Problema | Causa / solución |
|---|---|
| **No llega ningún mensaje al crear una cita** | Vuelve a ejecutar `php artisan telegram:test`. Si falla, revisa `TELEGRAM_BOT_TOKEN` y `TELEGRAM_CHAT_ID`. Confirma que primero le enviaste un mensaje a tu bot (para que el chat exista). |
| **Los botones no hacen nada** | El webhook no es accesible. Necesita una URL **pública con HTTPS**. Vuelve a ejecutar `telegram:set-webhook` con el `--url` correcto (usa ngrok en local). |
| **`telegram:test` dice "no configurado"** | `TELEGRAM_BOT_TOKEN` y/o `TELEGRAM_CHAT_ID` están vacíos en el `.env`. |
| **El webhook devuelve 403** | El secreto no coincide. Asegúrate de que `TELEGRAM_WEBHOOK_SECRET` esté puesto **y** de haber ejecutado `telegram:set-webhook` después de ponerlo (el secreto se registra en Telegram durante ese comando). |
| **Funciona en local pero no en producción** | `APP_URL` debe ser tu dominio HTTPS real, y debes volver a ejecutar `telegram:set-webhook` en el servidor. |
