<?php

/*
|==============================================================================
| EMAILS (EN) — Translations for the APPOINTMENT email templates
|==============================================================================
| EN: English strings for the appointment email templates:
|       - appointments/owner     (new request → the OWNER reads it)
|       - appointments/client    (request received → the CLIENT reads it)
|       - appointments/confirmed (appointment confirmed → CLIENT)
|       - appointments/rejected  (appointment declined → CLIENT)
|       - partials/signature     (shared signature, translatable bits only)
| ES: Cadenas en inglés de las plantillas de email de citas (ver detalle en lang/es).
|
| INDEX / ÍNDICE
|   1. SUBJECTS .......... Mailable Envelope subjects / asuntos de los Mailables
|   2. SHARED ........... brand, dates, labels, calendar / marca, fechas, etiquetas
|   3. SIGNATURE ........ partials/signature
|   4. OWNER ............ appointments/owner (default locale)
|   5. CLIENT ........... appointments/client
|   6. CONFIRMED ........ appointments/confirmed
|   7. REJECTED ......... appointments/rejected
|
| NOTE / NOTA:
|   EN: For client emails the final language is the active locale at send time
|       (Mail::...->locale($appointment->locale)). The OWNER email uses the
|       business default locale (config 'appointments.default_locale').
|   ES: Idéntico criterio que en lang/es: cliente = locale activo, dueño = locale
|       por defecto del negocio.
|==============================================================================
*/

return [

    // ── 1. SUBJECTS / ASUNTOS (Mailable Envelope subject) ─────────────────
    // EN: :name/:date/:time are interpolated by the owner Mailable.
    // ES: :name/:date/:time los interpola el Mailable del dueño.
    'subject_appointment_owner'     => 'New appointment request — :name (:date :time)',
    'subject_appointment_received'  => "We've received your appointment request",
    'subject_appointment_confirmed' => 'Your appointment is confirmed',
    'subject_appointment_rejected'  => 'About your appointment',

    // ── 2. SHARED / COMÚN ─────────────────────────────────────────────────
    'brand_tagline'      => 'Book your appointment online',
    'header_confirmed'   => 'Appointment confirmed',
    'header_appointment' => 'About your appointment',

    // EN: Long-date isoFormat pattern (Carbon). EN uses natural English order.
    // ES: Patrón isoFormat de fecha larga (Carbon). EN usa el orden inglés natural.
    'date_format'     => 'dddd D MMMM YYYY',

    // ── Appointment summary labels (reused) / Etiquetas del resumen ───────
    'label_date'      => 'Date',
    'label_time'      => 'Time',
    'label_duration'  => 'Duration',
    'label_modality'  => 'Format',

    // EN: Modality and duration values. ES: Valores de modalidad y duración.
    'modality_presencial' => 'In person',
    'modality_online'     => 'Online (video call)',
    'duration_one_hour'   => '1 hour',
    'duration_minutes'    => ':min min',

    // ── Calendar (shared by client + confirmed) / Calendario ─────────────
    'calendar_title'       => '🗓️ Add it to your calendar',
    'calendar_google'      => 'Google Calendar',
    'calendar_outlook'     => 'Outlook / Apple',

    // ── Contact footer (shared by client) / Pie de contacto ──────────────
    'footer_role'     => 'Online bookings',

    // ── 3. SIGNATURE / FIRMA (partials/signature) ─────────────────────────
    'signature_role'      => 'Booking & appointment management',
    'signature_available' => '● bookings open',

    // ════════════════════════════════════════════════════════════════════
    // 4. OWNER / DUEÑO — appointments/owner (business default locale)
    // EN: New EN keys: the original owner blade was hardcoded in Spanish only,
    //     so these are created here translated.
    // ES: Claves EN nuevas: el blade del dueño original estaba hardcodeado solo
    //     en español, así que se crean aquí traducidas.
    // ════════════════════════════════════════════════════════════════════
    'owner_title_tag'           => 'New appointment request',
    'owner_header'              => 'Appointment system',
    'owner_preheader'           => ':name has requested an appointment for :date at :time.',
    'owner_heading'             => 'New appointment request',
    'owner_intro'               => ':name has requested an appointment from the web form. Here are all the details:',
    'owner_label_client'        => 'Client',
    'owner_label_email'         => 'Email',
    'owner_label_phone'         => 'Phone',
    'owner_label_attendees'     => 'Attendees',
    'owner_label_status'        => 'Status',
    'owner_label_requested_at'  => 'Requested on',
    'owner_requested_at_format' => 'D MMMM YYYY [at] HH:mm',
    'owner_message_title'       => 'What do they want to discuss?',
    'owner_reply_button'        => 'Reply to the client',
    'owner_reminder'            => 'Remember to confirm the appointment by replying to the client.',

    // ════════════════════════════════════════════════════════════════════
    // 5. CLIENT / CLIENTE — appointments/client (request received)
    // ════════════════════════════════════════════════════════════════════
    'client_title_tag'      => "We've received your appointment request",
    'client_preheader'      => "We've received your request for :date at :time. We'll confirm it shortly.",
    'client_heading'        => 'Request received!',
    'client_intro'          => 'Hi :name, thanks for reaching out. I have received your appointment request and will review it personally.',
    'client_ref_label'      => 'Your appointment ref:',
    'client_ref_hint'       => 'Keep it handy: quote it if you need to write to me about this appointment.',
    'client_summary_title'  => 'Your appointment summary',
    'client_whatnow_title'  => 'What happens now?',
    'client_whatnow_text'   => "I'll review your request and confirm the appointment by email as soon as possible. If you need to change anything or cancel, just reply to this email.",
    'client_signoff'        => 'Best regards,',

    // ════════════════════════════════════════════════════════════════════
    // 6. CONFIRMED / CONFIRMADA — appointments/confirmed
    // ════════════════════════════════════════════════════════════════════
    'confirmed_title_tag'   => 'Your appointment is confirmed',
    'confirmed_preheader'   => 'Your appointment on :date at :time is confirmed.',
    'confirmed_heading'     => 'Appointment confirmed! ✅',
    'confirmed_intro'       => 'Hi :name, your appointment is confirmed. Here are the details:',
    'confirmed_ref_label'   => 'Appointment ref:',
    'confirmed_meet_title'  => '📹 Video call link',
    'confirmed_meet_button' => 'Join Google Meet',
    'confirmed_outro'       => 'If you need to change anything or cancel, reply to this email quoting your appointment ref. See you then!',

    // ════════════════════════════════════════════════════════════════════
    // 7. REJECTED / RECHAZADA — appointments/rejected
    // ════════════════════════════════════════════════════════════════════
    'rejected_title_tag'    => 'About your appointment',
    'rejected_preheader'    => 'About your appointment request for :date.',
    'rejected_heading'      => 'About your appointment request',
    'rejected_intro'        => "Hi :name, thanks for your interest. Unfortunately I won't be able to take the appointment on :date at :time",
    'rejected_ref_inline'   => '(ref :reference)',
    'rejected_outro'        => 'If it works for you, we can find another slot: simply book again at :link or reply to this email. Sorry for the inconvenience.',

];
