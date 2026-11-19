{{--
  ES: Primera capa de información sobre protección de datos (RGPD art. 13 y
      LOPDGDD art. 11), justo antes del botón de reservar: responsable,
      finalidad, legitimación, destinatarios, derechos y plazo, con enlace a la
      política completa si existe. Datos desde config('appointments.privacy').
  EN: First-layer data-protection notice (GDPR art. 13 and LOPDGDD art. 11),
      right before the booking button: controller, purpose, legal basis,
      recipients, rights and retention, linking to the full policy if any.
      Data from config('appointments.privacy').
--}}
@php
    // ES: Valores de la config. EN: Config values.
    $priv = config('appointments.privacy');
    $meses = (int) ($priv['retention_months'] ?? 12);
    $destinatarios = trim((string) ($priv['recipients'] ?? '')) ?: __('citas.privacy_recipients');
@endphp
<details class="rounded-lg border border-brand/15 bg-base/40 px-4 py-3 text-xs text-muted">
  <summary class="cursor-pointer font-semibold text-ink">{{ __('citas.privacy_title') }}</summary>
  <dl class="mt-2 grid gap-1 sm:grid-cols-[auto,1fr] sm:gap-x-3">
    <dt class="font-semibold">{{ __('citas.privacy_label_controller') }}</dt>
    <dd>{{ $priv['controller'] ?? config('appointments.brand.name') }}</dd>
    <dt class="font-semibold">{{ __('citas.privacy_label_purpose') }}</dt>
    <dd>{{ __('citas.privacy_purpose') }} @if($meses > 0){{ __('citas.privacy_retention', ['months' => $meses]) }}@endif</dd>
    <dt class="font-semibold">{{ __('citas.privacy_label_basis') }}</dt>
    <dd>{{ __('citas.privacy_basis') }}</dd>
    <dt class="font-semibold">{{ __('citas.privacy_label_recipients') }}</dt>
    <dd>{{ $destinatarios }}</dd>
    <dt class="font-semibold">{{ __('citas.privacy_label_rights') }}</dt>
    <dd>
      {{ __('citas.privacy_rights', ['email' => config('appointments.brand.owner_email')]) }}
      @if(! empty($priv['policy_url']))
        {!! __('citas.privacy_more', ['link' => '<a href="'.e($priv['policy_url']).'" class="underline" target="_blank" rel="noopener">'.e(__('citas.privacy_link')).'</a>']) !!}
      @endif
    </dd>
  </dl>
</details>
