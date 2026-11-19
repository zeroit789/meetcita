{{--
  ES: Bloque «¿No puedes venir?» con el enlace firmado a gestionar la cita
      (cancelar o cambiar día y hora). Solo si el Mailable pasa $gestionarUrl
      (null con la autogestión desactivada). Estilos en línea (apto para correo).
  EN: "Can't make it?" block with the signed link to manage the booking
      (cancel or change day and time). Only if the Mailable passes
      $gestionarUrl (null with self-service disabled). Inline styles (email-safe).
--}}
@if(! empty($gestionarUrl))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
        <tr>
            <td style="padding:18px 20px; background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; font-family:Arial,Helvetica,sans-serif;">
                <p style="margin:0 0 6px 0; color:#1f2937; font-size:15px; font-weight:bold;">{{ __('emails.manage_title') }}</p>
                <p style="margin:0 0 12px 0; color:#4b5563; font-size:14px; line-height:1.6;">
                    @php($horasPlazo = \App\Models\Appointment::horasPlazoAutogestion())
                    {{ $horasPlazo > 0 ? __('emails.manage_text', ['hours' => $horasPlazo]) : __('emails.manage_text_no_limit') }}
                </p>
                <a href="{{ $gestionarUrl }}" style="display:inline-block; background-color:#1f2937; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none; padding:10px 16px; border-radius:8px;">{{ __('emails.manage_button') }}</a>
            </td>
        </tr>
    </table>
@endif
