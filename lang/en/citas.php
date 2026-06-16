<?php

/*
|==============================================================================
| EN — UI translations for the appointments module / Traducciones EN del módulo
|==============================================================================
| EN: Every visible string in the booking wizard, the panel, the login and the
|     landing lives here. Neutral wording (no personal brand). The Spanish
|     mirror is lang/es/citas.php — keep both in sync.
| ES: Aquí viven todos los textos visibles del wizard de reserva, el panel, el
|     login y la landing. Redacción neutra (sin marca personal). El espejo en
|     español es lang/es/citas.php — mantén ambos sincronizados.
|
| INDEX / ÍNDICE
|   1. PAGE WRAPPER ........ /citas header / cabecera de /citas
|   2. WIZARD .............. 4-step booking flow / flujo de reserva 4 pasos
|   3. CLIENT ERRORS ....... validation messages / mensajes de validación
|   4. LAYOUT .............. lang toggle + footer / selector idioma + footer
|   5. LANDING ............. welcome page / página de inicio
|   6. PANEL ............... appointments table / tabla de citas
|   7. BLOCKED DAYS ........ off-days manager / gestor de días bloqueados
|   8. LOGIN ............... panel login / login del panel
|==============================================================================
*/

return [

    // ── 1. PAGE WRAPPER / CABECERA DE /citas ──────────────────────────────
    'wrapper_back_home' => '← Back to home',
    'wrapper_title'     => 'Book an appointment',
    'wrapper_intro'     => "Book a call or meeting. Pick the day and time that suits you best and we'll confirm by email.",

    // ── 2. WIZARD / WIZARD DE RESERVA ─────────────────────────────────────
    // Step indicator / Indicador de pasos
    'steps_01' => '01 day',
    'steps_02' => '02 time',
    'steps_03' => '03 details',

    // Step 1: pick a day / Paso 1: elegir día
    'step1_cmd'        => '$ ./availability.sh --calendar',
    'step1_title'      => 'Pick a day',
    'cal_prev_aria'    => 'Previous month',
    'cal_next_aria'    => 'Next month',
    'cal_today_suffix' => ' (today)',
    'cal_available'    => ', available',
    'cal_selected'     => ', selected',
    'cal_unavailable'  => ', unavailable',
    'cal_weekdays'     => 'M,T,W,T,F,S,S',
    'cal_hours_label'  => 'Working hours:',
    'cal_hours_value'  => 'Monday to Friday · 9:30am–6:00pm',
    'cal_hours_note'   => '(no lunch break)',

    // Step 2: pick a time / Paso 2: elegir hora
    'step2_cmd'            => '$ ./availability.sh --slots',
    'step2_title'          => 'Pick a length and time',
    'step2_subtitle_pre'   => 'Open slots for',
    'step2_duration_label' => '// meeting length',
    'btn_30min'            => '30 min',
    'btn_1hour'            => '1 hour',
    'btn_minutes'          => ':min min',
    'step2_no_slots'       => '// no slots left on this day',
    'step2_no_slots_hint'  => 'Try another day.',
    'step2_back'           => '← change day',

    // Step 3: details form / Paso 3: formulario de datos
    'step3_cmd'                      => '$ ./book.sh --details',
    'step3_title'                    => 'Your details',
    'step3_summary_pre'             => 'Appointment for',
    'step3_summary_at'              => 'at',
    'honeypot_label'               => 'Do not fill in this field',
    'field_modality'                => 'Format *',
    'modality_online'               => 'Online',
    'modality_online_sub'           => 'video call (Meet)',
    'modality_inperson'             => 'In person',
    'modality_inperson_sub'         => "let's meet in person",
    'field_name'                    => 'Your name *',
    'ph_name'                       => 'Your name',
    'field_email'                   => 'Email *',
    'ph_email'                      => 'you@email.com',
    'field_phone'                   => 'Phone',
    'field_phone_optional'          => '(optional)',
    'ph_phone'                      => '+34 ...',
    'field_attendees'               => 'Who will attend *',
    'ph_attendees'                  => 'Names of everyone joining the meeting',
    'field_attendee_emails'         => "Attendees' emails",
    'field_attendee_emails_optional' => '(optional)',
    'ph_attendee_emails'            => 'anna@email.com, john@email.com',
    'help_attendee_emails'          => "// they'll get the invite with the video call link. Separate them with commas.",
    'field_message'                 => 'What would you like to discuss? *',
    'ph_message'                    => 'Tell us briefly what you want to do or talk about...',
    'step3_back'                    => '← change time',
    'btn_book'                      => 'Book appointment',
    'btn_booking'                   => 'Booking...',

    // Step 4: confirmation / Paso 4: confirmación
    'step4_cmd'          => '$ appointment --status requested',
    'step4_title'        => 'Appointment requested!',
    'step4_summary_pre'  => 'Your appointment for',
    'step4_summary_at'   => 'at',
    'step4_summary_post' => 'is registered.',
    'step4_ref_label'    => 'Your booking no.',
    'step4_ref_hint'     => '// keep it: quote it if you need to write to us about this appointment',
    'step4_email_note'   => "// we'll confirm by email as soon as possible",
    'btn_book_another'   => 'Book another appointment',

    // Duration labels (used across steps) / Etiquetas de duración (varios pasos)
    'dur_1hour'    => '1 hour',
    'dur_30min'    => '30 min',
    'dur_min_unit' => 'min',

    // ── 3. CLIENT ERRORS / ERRORES PARA EL CLIENTE ────────────────────────
    'err_too_many_attempts' => 'Too many attempts. Please try again in a little while.',
    'err_email_invalid'     => 'The email ":email" is not valid. Check the list (separate them with commas).',
    'err_max_attendees'     => 'Maximum :max attendees.',
    'err_retry_soon'        => 'Please try again in a moment.',
    'err_slot_taken'        => 'That slot has just been taken, please pick another.',
    'err_slot_taken_step2'  => 'That slot has just been taken. Please pick another time.',

    // ── 4. LAYOUT / SELECTOR DE IDIOMA + FOOTER ───────────────────────────
    'lang_aria'      => 'Select language',
    // EN: aria-label for the sun/moon theme toggle button.
    // ES: aria-label del botón sol/luna para cambiar de tema.
    'theme_toggle'   => 'Toggle theme',
    'footer_website' => 'Website',
    'footer_repo'    => 'Source',

    // ── 5. LANDING / PÁGINA DE INICIO ─────────────────────────────────────
    'home_title'    => 'Appointment booking system',
    'home_lead'     => 'A clean, open-source demo of a booking system: calendar, time selection, online or in-person format and a management panel. Try it by booking a sample appointment.',
    'home_cta_book' => 'Book an appointment',
    'home_cta_repo' => 'View the source',
    'home_feat_1'   => 'reactive calendar, no reloads',
    'home_feat_2'   => 'online (Meet) or in person',
    'home_feat_3'   => 'bilingual management panel',

    // ── 6. PANEL / TABLA DE CITAS ─────────────────────────────────────────
    'panel_title'    => 'Appointments panel',
    'panel_heading'  => 'Appointments panel',
    'panel_subtitle' => 'All requests received from /citas.',
    'panel_logout'   => 'log out',
    'panel_empty'    => 'no appointments registered yet',
    // Table columns / Columnas de la tabla
    'col_ref'      => 'Booking #',
    'col_date'     => 'Date',
    'col_time'     => 'Time',
    'col_duration' => 'Length',
    'col_modality' => 'Format',
    'col_name'     => 'Name',
    'col_email'    => 'Email',
    'col_phone'    => 'Phone',
    'col_people'   => 'People',
    'col_message'  => 'Message',
    'col_status'   => 'Status',
    'col_actions'  => 'Actions',
    'meet_link'    => 'Meet',
    // Statuses / Estados
    'status_pending'   => 'pending',
    'status_confirmed' => 'confirmed',
    'status_cancelled' => 'cancelled',
    // Actions / Acciones
    'action_confirm' => 'confirm',
    'action_cancel'  => 'cancel',

    // ── 7. BLOCKED DAYS / DÍAS BLOQUEADOS ─────────────────────────────────
    'blocked_title'                 => 'Off days',
    'blocked_intro'                 => 'Mark your holidays or days you will not be operating here. Those days will appear disabled in the booking calendar.',
    'blocked_field_date'            => 'Date *',
    'blocked_field_reason'          => 'Reason',
    'blocked_field_reason_optional' => '(optional)',
    'blocked_ph_reason'             => 'Holiday, day off...',
    'blocked_add'                   => 'Block day',
    'blocked_empty'                 => "no blocked days. You're available every working day.",
    'blocked_remove'                => 'remove',
    'blocked_confirm_remove'        => 'Remove the block on this day? It will be bookable again.',
    // Blocked-day validation messages / Mensajes de validación de día bloqueado
    'blocked_err_required'          => 'Pick a date.',
    'blocked_err_invalid'           => 'Invalid date.',
    'blocked_err_past'              => "There's no point blocking a day that already passed.",
    'blocked_err_duplicate'         => 'That day is already blocked.',
    'blocked_err_reason_long'       => 'The reason is too long.',
    'blocked_err_has_appointments'  => 'That day has booked appointments; handle them before blocking it.',

    // ── 8. LOGIN / LOGIN DEL PANEL ────────────────────────────────────────
    'login_title'    => 'Panel access',
    'login_password' => 'Password',
    'login_submit'   => 'Sign in',
    'login_back'     => '← back to the site',

];
