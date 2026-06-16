<?php

/*
|==============================================================================
| EMAILS (ES) — Traducciones de las plantillas de email de CITAS
|==============================================================================
| EN: Spanish strings for the appointment email templates:
|       - appointments/owner     (new request → the OWNER reads it)
|       - appointments/client    (request received → the CLIENT reads it)
|       - appointments/confirmed (appointment confirmed → CLIENT)
|       - appointments/rejected  (appointment declined → CLIENT)
|       - partials/signature     (shared signature, translatable bits only)
| ES: Cadenas en español de las plantillas de email de citas:
|       - appointments/owner     (nueva solicitud → la lee el DUEÑO)
|       - appointments/client    (solicitud recibida → la lee el CLIENTE)
|       - appointments/confirmed (cita confirmada → CLIENTE)
|       - appointments/rejected  (cita rechazada → CLIENTE)
|       - partials/signature     (firma común, solo lo traducible)
|
| INDEX / ÍNDICE
|   1. SUBJECTS .......... Mailable Envelope subjects / asuntos de los Mailables
|   2. SHARED ........... brand, dates, labels, calendar / marca, fechas, etiquetas
|   3. SIGNATURE ........ partials/signature
|   4. OWNER ............ appointments/owner (locale por defecto)
|   5. CLIENT ........... appointments/client
|   6. CONFIRMED ........ appointments/confirmed
|   7. REJECTED ......... appointments/rejected
|
| NOTE / NOTA:
|   EN: For client emails the final language is the active locale at send time
|       (Mail::...->locale($appointment->locale)). The OWNER email uses the
|       business default locale (config 'appointments.default_locale').
|   ES: En los emails al cliente el idioma final es el locale activo al enviar
|       (Mail::...->locale($appointment->locale)). El email al DUEÑO usa el idioma
|       por defecto del negocio (config 'appointments.default_locale').
|==============================================================================
*/

return [

    // ── 1. SUBJECTS / ASUNTOS (Envelope subject de los Mailables) ─────────
    // EN: :name/:date/:time are interpolated by the owner Mailable.
    // ES: :name/:date/:time los interpola el Mailable del dueño.
    'subject_appointment_owner'     => 'Nueva solicitud de cita — :name (:date :time)',
    'subject_appointment_received'  => 'Hemos recibido tu solicitud de cita',
    'subject_appointment_confirmed' => 'Tu cita está confirmada',
    'subject_appointment_rejected'  => 'Sobre tu cita',

    // ── 2. SHARED / COMÚN ─────────────────────────────────────────────────
    // EN: Brand tagline + section headers shared across client templates.
    // ES: Eslogan de marca + cabeceras de sección comunes a las plantillas de cliente.
    'brand_tagline'      => 'Reserva tu cita online',
    'header_confirmed'   => 'Cita confirmada',
    'header_appointment' => 'Sobre tu cita',

    // EN: Long-date isoFormat pattern (Carbon). ES inserts "de" between parts.
    // ES: Patrón isoFormat de fecha larga (Carbon). ES lleva "de" entre las partes.
    'date_format'     => 'dddd D [de] MMMM [de] YYYY',

    // ── Etiquetas del resumen de la cita (reutilizadas) ──────────────────
    'label_date'      => 'Fecha',
    'label_time'      => 'Hora',
    'label_duration'  => 'Duración',
    'label_modality'  => 'Modalidad',

    // EN: Modality and duration values. ES: Valores de modalidad y duración.
    'modality_presencial' => 'Presencial',
    'modality_online'     => 'Online (videollamada)',
    'duration_one_hour'   => '1 hora',
    'duration_minutes'    => ':min min',

    // ── Calendario (común a client + confirmed) ──────────────────────────
    'calendar_title'       => '🗓️ Añádela a tu calendario',
    'calendar_google'      => 'Google Calendar',
    'calendar_outlook'     => 'Outlook / Apple',

    // ── Pie de contacto (común a client) ─────────────────────────────────
    'footer_role'     => 'Reservas online',

    // ── 3. SIGNATURE / FIRMA (partials/signature) ─────────────────────────
    'signature_role'      => 'Reserva y gestión de citas',
    'signature_available' => '● reservas abiertas',

    // ════════════════════════════════════════════════════════════════════
    // 4. OWNER / DUEÑO — appointments/owner (idioma por defecto del negocio)
    // EN: These keys were HARDCODED in the original owner blade. Now decoupled.
    // ES: Estas claves estaban HARDCODEADAS en el blade del dueño original.
    //     Ahora desacopladas.
    // ════════════════════════════════════════════════════════════════════
    'owner_title_tag'           => 'Nueva solicitud de cita',
    'owner_header'              => 'Sistema de citas',
    'owner_preheader'           => ':name ha solicitado una cita para el :date a las :time.',
    'owner_heading'             => 'Nueva solicitud de cita',
    'owner_intro'               => ':name ha solicitado una cita desde el formulario web. Aquí tienes todos los datos:',
    'owner_label_client'        => 'Cliente',
    'owner_label_email'         => 'Email',
    'owner_label_phone'         => 'Teléfono',
    'owner_label_attendees'     => 'Asistentes',
    'owner_label_status'        => 'Estado',
    'owner_label_requested_at'  => 'Solicitada el',
    'owner_requested_at_format' => 'D [de] MMMM [de] YYYY [a las] HH:mm',
    'owner_message_title'       => '¿Qué quiere tratar?',
    'owner_reply_button'        => 'Responder al cliente',
    'owner_reminder'            => 'Recuerda confirmar la cita respondiendo al cliente.',

    // ════════════════════════════════════════════════════════════════════
    // 5. CLIENT / CLIENTE — appointments/client (solicitud recibida)
    // ════════════════════════════════════════════════════════════════════
    'client_title_tag'      => 'Hemos recibido tu solicitud de cita',
    'client_preheader'      => 'Hemos recibido tu solicitud para el :date a las :time. Te confirmaremos en breve.',
    'client_heading'        => '¡Solicitud recibida!',
    'client_intro'          => 'Hola :name, gracias por contactar. He recibido tu solicitud de cita y la revisaré personalmente.',
    'client_ref_label'      => 'Tu nº de cita:',
    'client_ref_hint'       => 'Guárdalo: identifícate con él si necesitas escribirme sobre esta cita.',
    'client_summary_title'  => 'Resumen de tu cita',
    'client_whatnow_title'  => '¿Qué pasa ahora?',
    'client_whatnow_text'   => 'Revisaré tu solicitud y te confirmaré la cita por email lo antes posible. Si necesitas cambiar algo o cancelar, solo tienes que responder a este correo.',
    'client_signoff'        => 'Un saludo,',

    // ════════════════════════════════════════════════════════════════════
    // 6. CONFIRMED / CONFIRMADA — appointments/confirmed
    // ════════════════════════════════════════════════════════════════════
    'confirmed_title_tag'   => 'Tu cita está confirmada',
    'confirmed_preheader'   => 'Tu cita del :date a las :time está confirmada.',
    'confirmed_heading'     => '¡Cita confirmada! ✅',
    'confirmed_intro'       => 'Hola :name, te confirmo nuestra cita. Aquí tienes los detalles:',
    'confirmed_ref_label'   => 'Nº de cita:',
    'confirmed_meet_title'  => '📹 Enlace de la videollamada',
    'confirmed_meet_button' => 'Unirme a Google Meet',
    'confirmed_outro'       => 'Si necesitas cambiar algo o cancelar, responde a este correo indicando tu nº de cita. ¡Nos vemos!',

    // ════════════════════════════════════════════════════════════════════
    // 7. REJECTED / RECHAZADA — appointments/rejected
    // ════════════════════════════════════════════════════════════════════
    'rejected_title_tag'    => 'Sobre tu cita',
    'rejected_preheader'    => 'Sobre tu solicitud de cita del :date.',
    'rejected_heading'      => 'Sobre tu solicitud de cita',
    // EN: The reason and reference are built in the template with Blade variables.
    // ES: El motivo y la referencia se montan en la plantilla con variables Blade.
    'rejected_intro'        => 'Hola :name, gracias por tu interés. Lamentablemente no voy a poder atender la cita del :date a las :time',
    'rejected_ref_inline'   => '(referencia :reference)',
    'rejected_outro'        => 'Si te viene bien, podemos buscar otro hueco: solo tienes que reservar de nuevo en :link o responder a este correo. Disculpa las molestias.',

];
