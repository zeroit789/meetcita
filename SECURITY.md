# Política de seguridad · Security policy

## Español

### Versiones con soporte

| Versión | Soporte |
|---|---|
| 1.x | Sí: recibe arreglos de seguridad |
| < 1.0.0 | No: actualiza a la última 1.x |

### Cómo informar de una vulnerabilidad

**No abras un issue público.** Usa el formulario privado de GitHub:
pestaña **Security** del repositorio, botón **Report a vulnerability**
(«Informar de una vulnerabilidad»). Solo lo vemos tú y el mantenedor.

Incluye, si puedes:

- qué parte falla (página de reserva, panel, `.ics`, webhook de Telegram…) y la versión o el commit;
- los pasos para reproducirlo y qué consigue quien lo aproveche;
- si ya lo has visto explotado en algún sitio.

Qué puedes esperar:

- respuesta en un máximo de **7 días**;
- si se confirma, un arreglo y un aviso de seguridad publicado en GitHub con la versión corregida;
- tu nombre en los créditos del aviso, si quieres.

Prueba solo contra tu propia instalación. No accedas a datos de clientes de otros
negocios ni hagas pruebas que tumben un servicio.

### Buenas prácticas al instalar Meetcita

- Pon una contraseña larga en `APPOINTMENTS_PANEL_PASSWORD`. Cambiarla cierra todas las sesiones abiertas del panel.
- Sirve la web solo por HTTPS y rellena `TRUSTED_PROXIES` con la IP de tu proxy.
- Ten el scheduler en marcha (`php artisan schedule:run` cada minuto): envía los correos y borra los datos antiguos.
- Actualiza a menudo (`composer update` y `pnpm update`) y pasa `composer audit`.

## English

### Supported versions

| Version | Supported |
|---|---|
| 1.x | Yes: receives security fixes |
| < 1.0.0 | No: upgrade to the latest 1.x |

### How to report a vulnerability

**Do not open a public issue.** Use GitHub's private form: the repository's
**Security** tab, **Report a vulnerability** button. Only you and the maintainer
can see it.

If you can, include:

- which part is affected (booking page, panel, `.ics`, Telegram webhook…) and the version or commit;
- the steps to reproduce it and what an attacker gains;
- whether you have seen it exploited anywhere.

What to expect:

- an answer within **7 days**;
- if confirmed, a fix and a security advisory published on GitHub with the fixed version;
- your name in the advisory credits, if you wish.

Only test against your own installation. Do not access other businesses' client
data or run tests that take a service down.

### Good practices when installing Meetcita

- Set a long password in `APPOINTMENTS_PANEL_PASSWORD`. Changing it closes every open panel session.
- Serve the site over HTTPS only and fill `TRUSTED_PROXIES` with your proxy's IP.
- Keep the scheduler running (`php artisan schedule:run` every minute): it sends the emails and deletes old data.
- Update often (`composer update` and `pnpm update`) and run `composer audit`.
