{{--
  ES: Nota de protección de datos al pie de los correos al cliente (RGPD art.
      13): por qué le llega, para qué se usan sus datos, cuánto se guardan y
      cómo pedir el borrado. Estilos en línea (apto para correo).
  EN: Data-protection note at the foot of the client emails (GDPR art. 13):
      why they get it, what their data is used for, how long it is kept and
      how to ask for deletion. Inline styles (email-safe).
--}}
<p style="margin:18px 0 0 0; font-family:Arial,Helvetica,sans-serif; color:#6b7280; font-size:11px; line-height:1.55;">
    @php($meses = (int) config('appointments.privacy.retention_months', 12))
    {{-- ES: Con la purga desactivada (0) no se promete un plazo. EN: With the purge off (0) no period is promised. --}}
    {{ __($meses > 0 ? 'emails.privacy_note' : 'emails.privacy_note_no_retention', [
        'brand' => config('appointments.brand.name'),
        'months' => $meses,
    ]) }}
</p>
