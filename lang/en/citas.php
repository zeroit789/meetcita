<?php

/*
|==============================================================================
| EN — UI translations for the appointments module / Traducciones EN del módulo
|==============================================================================
| ES: Aquí viven todos los textos visibles del wizard de reserva, el panel, el
|     login y la landing. Redacción neutra (sin marca personal). El espejo en
|     español es lang/es/citas.php — mantén ambos sincronizados.
| EN: Every visible string in the booking wizard, the panel, the login and the
|     landing lives here. Neutral wording (no personal brand). The Spanish
|     mirror is lang/es/citas.php — keep both in sync.
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
    'wrapper_title' => 'Book an appointment',
    'wrapper_intro' => "Book a call or meeting. Pick the day and time that suits you best and we'll confirm by email.",

    // ── 2. WIZARD / WIZARD DE RESERVA ─────────────────────────────────────
    // Step indicator / Indicador de pasos
    'steps_01' => 'Day',
    'steps_02' => 'Time',
    'steps_03' => 'Your details',
    // ES: Nombre de la lista de pasos para lectores de pantalla. EN: Step list name for screen readers.
    'steps_aria' => 'Booking steps',

    // Step 1: pick a day / Paso 1: elegir día
    'step1_title' => 'Pick a day',
    'cal_prev_aria' => 'Previous month',
    'cal_next_aria' => 'Next month',
    'cal_today_suffix' => ' (today)',
    'cal_available' => ', available',
    'cal_selected' => ', selected',
    'cal_unavailable' => ', unavailable',
    'cal_weekdays' => 'M,T,W,T,F,S,S',
    'cal_hours_label' => 'Working hours:',
    // ES: Horario generado desde la config (días y horas). EN: Schedule built from the config.
    'cal_hours_value' => ':days · :hours',
    'cal_days_range' => ':from to :to',
    'cal_days_join' => ', ',
    // ES: Unión de tramos de un día y de grupos de días con horarios distintos.
    // EN: Join between a day's ranges and between day groups with different hours.
    'cal_ranges_join' => ' and ',
    'cal_groups_join' => '; ',

    // Step 2: pick a time / Paso 2: elegir hora
    'step2_title' => 'Pick a length and time',
    'step2_subtitle_pre' => 'Open slots for',
    'step2_duration_label' => 'Meeting length',
    'btn_30min' => '30 min',
    'btn_1hour' => '1 hour',
    'btn_minutes' => ':min min',
    'step2_no_slots' => 'No free times left on this day.',
    'step2_no_slots_hint' => 'Try another day.',
    'step2_back' => '← Change day',

    // Step 3: details form / Paso 3: formulario de datos
    'step3_title' => 'Your details',
    'step3_summary_pre' => 'Appointment for',
    'step3_summary_at' => 'at',
    'honeypot_label' => 'Do not fill in this field',
    'field_modality' => 'Format *',
    'modality_online' => 'Online',
    'modality_online_sub' => 'video call (Meet)',
    'modality_inperson' => 'In person',
    'modality_inperson_sub' => "let's meet in person",
    'field_name' => 'Your name *',
    'ph_name' => 'Your name',
    'field_email' => 'Email *',
    'ph_email' => 'you@email.com',
    'field_phone' => 'Phone',
    'field_phone_optional' => '(optional)',
    'ph_phone' => '+34 ...',
    'field_attendees' => 'Who will attend',
    'ph_attendees' => 'Names of everyone joining the meeting',
    'field_attendee_emails' => "Attendees' emails",
    'field_attendee_emails_optional' => '(optional)',
    'ph_attendee_emails' => 'anna@email.com, john@email.com',
    'help_attendee_emails' => 'They\'ll get the invite with the video call link. Separate them with commas.',
    'field_message' => 'What would you like to discuss?',
    'help_message' => 'Please don\'t include health data or other sensitive data: it isn\'t needed for the booking.',
    'ph_message' => 'Tell us briefly what you want to do or talk about...',
    // ES: Primera capa de protección de datos (bajo el formulario).
    // EN: First-layer data-protection notice (under the form).
    'privacy_title' => 'Basic information on data protection',
    'privacy_label_controller' => 'Controller',
    'privacy_label_purpose' => 'Purpose',
    'privacy_label_basis' => 'Legal basis',
    'privacy_label_recipients' => 'Recipients',
    'privacy_label_rights' => 'Rights',
    'privacy_purpose' => 'Managing your booking and sending you its confirmation and notices.',
    'privacy_basis' => 'Your booking request (pre-contractual steps, GDPR art. 6.1.b).',
    'privacy_recipients' => "No data is shared with third parties. The business's email and hosting providers process it and, if enabled, Google Calendar and Telegram.",
    'privacy_rights' => 'Access, rectification, erasure, objection, restriction and portability, by writing to :email.',
    'privacy_retention' => 'Kept for :months months after the booking and then deleted.',
    'privacy_more' => 'More information in the :link.',
    'privacy_link' => 'privacy policy',
    'step3_back' => '← Change time',
    'btn_book' => 'Book appointment',
    'btn_booking' => 'Booking...',

    // Step 4: confirmation / Paso 4: confirmación
    'step4_title' => 'Appointment requested!',
    'step4_summary_pre' => 'Your appointment for',
    'step4_summary_at' => 'at',
    'step4_summary_post' => 'is registered.',
    'step4_ref_label' => 'Your booking no.',
    'step4_ref_hint' => 'Keep it: quote it if you need to write to us about this appointment.',
    'step4_email_note' => 'We\'ll confirm your appointment by email as soon as possible.',
    'btn_book_another' => 'Book another appointment',

    // Duration labels (used across steps) / Etiquetas de duración (varios pasos)
    'dur_1hour' => '1 hour',
    'dur_30min' => '30 min',
    // ES: Textos de calendario (.ics y Google), en el idioma de la cita.
    // EN: Calendar texts (.ics and Google), in the booking's language.
    'cal_location_online' => 'Online',
    'cal_location_in_person' => 'In person',
    'cal_desc_topic' => 'Topic: :text',
    'cal_desc_video' => 'Video call: :url',
    'cal_desc_reference' => 'Reference: :ref',
    'cal_desc_requested_from' => 'Booking requested from :host',
    'cal_desc_attendees' => 'Attendees: :text',
    'cal_desc_modality' => 'Modality: :text',
    'dur_minutes' => ':min min',
    'dur_hours' => '{1} :h hour|[2,*] :h hours',
    'dur_hours_minutes' => ':h h :min min',
    'dur_min_unit' => 'min',

    // ── 3. CLIENT ERRORS / ERRORES PARA EL CLIENTE ────────────────────────
    'err_too_many_attempts' => 'Too many attempts. Please try again in a little while.',
    'err_email_invalid' => 'The email ":email" is not valid. Check the list (separate them with commas).',
    'err_max_attendees' => 'Maximum :max attendees.',
    'err_email_limit' => 'This email already has :max upcoming bookings. You can book again once they are over or cancelled.',
    'err_captcha' => 'Please confirm you\'re not a robot (tick the box) and send again.',
    // ES: Modo demo. EN: Demo mode.
    'demo_banner' => 'This is a demo: no email is sent and the data is wiped every hour. Please do not enter real data.',
    'demo_panel_hint' => 'Demo: the panel password is ":password".',
    'err_retry_soon' => 'Please try again in a moment.',
    'err_slot_taken' => 'That slot has just been taken, please pick another.',
    'err_slot_taken_step2' => 'That slot has just been taken. Please pick another time.',

    // ── 4. LAYOUT / SELECTOR DE IDIOMA + FOOTER ───────────────────────────
    'lang_aria' => 'Select language',
    // ES: aria-label del botón sol/luna para cambiar de tema.
    // EN: aria-label for the sun/moon theme toggle button.
    'theme_toggle' => 'Toggle theme',
    'footer_website' => 'Website',

    // ── 5. LANDING / PÁGINA DE INICIO ─────────────────────────────────────
    'home_title' => 'Book your appointment',
    'home_lead' => 'Book an appointment in a couple of minutes: pick the day and time that suit you best, online or in person, and we\'ll confirm it by email.',
    'home_cta_book' => 'Book an appointment',
    'home_cta_repo' => 'View the demo source code',
    'home_feat_1' => 'You pick a free day and time',
    'home_feat_2' => 'Online by video call or in person',
    'home_feat_3' => 'Confirmation by email',

    // ── 6. PANEL / TABLA DE CITAS ─────────────────────────────────────────
    'panel_title' => 'Appointments panel',
    'panel_heading' => 'Appointments panel',
    'panel_subtitle' => 'All appointment requests received.',
    'panel_logout' => 'Log out',
    'panel_empty' => 'No appointments registered yet.',
    'panel_list_title' => 'Appointments received',
    // Table columns / Columnas de la tabla
    'col_ref' => 'Booking #',
    'col_date' => 'Date',
    'col_time' => 'Time',
    'col_duration' => 'Length',
    'col_modality' => 'Format',
    'col_name' => 'Name',
    'col_email' => 'Email',
    'col_phone' => 'Phone',
    'col_people' => 'People',
    'col_message' => 'Message',
    'col_status' => 'Status',
    'col_actions' => 'Actions',
    'meet_link' => 'Meet',
    // Statuses / Estados
    'status_pending' => 'Pending',
    'status_confirmed' => 'Confirmed',
    'status_cancelled' => 'Cancelled',
    // Actions / Acciones
    'action_confirm' => 'Confirm',
    'action_cancel' => 'Cancel',
    // ES: Cancelación desde el panel (motivo opcional) y errores de estado.
    // EN: Cancelling from the panel (optional reason) and status errors.
    'cancel_reason_label' => 'Reason for the client (optional)',
    'cancel_reason_placeholder' => 'It will be emailed exactly as you write it.',
    'cancel_submit' => 'Cancel and notify',
    'cancel_back' => 'Back',
    'panel_err_only_pending' => 'Only pending appointments can be confirmed.',
    'panel_err_already_cancelled' => 'That appointment was already cancelled.',

    // ── 7. BLOCKED DAYS / DÍAS BLOQUEADOS ─────────────────────────────────
    'blocked_title' => 'Off days',
    'blocked_intro' => 'Mark your holidays or days you will not be operating here. Those days will appear disabled in the booking calendar.',
    'blocked_field_date' => 'Date *',
    'blocked_field_reason' => 'Reason',
    'blocked_field_reason_optional' => '(optional)',
    'blocked_ph_reason' => 'Holiday, day off...',
    'blocked_add' => 'Block day',
    'blocked_empty' => 'No blocked days. You\'re available every working day.',
    'blocked_remove' => 'Remove',
    'blocked_confirm_remove' => 'Remove the block on this day? It will be bookable again.',
    // Blocked-day validation messages / Mensajes de validación de día bloqueado
    'blocked_err_required' => 'Pick a date.',
    'blocked_err_invalid' => 'Invalid date.',
    'blocked_err_past' => "There's no point blocking a day that already passed.",
    'blocked_err_duplicate' => 'That day is already blocked.',
    'blocked_err_reason_long' => 'The reason is too long.',
    'blocked_err_has_appointments' => 'That day has booked appointments; handle them before blocking it.',

    // ── 8. LOGIN / LOGIN DEL PANEL ────────────────────────────────────────
    'login_title' => 'Panel access',
    'login_password' => 'Password',
    'login_submit' => 'Sign in',
    'login_back' => '← Back to the site',

    // ── 9. MANAGE / MANAGE MY BOOKING (signed link in the email) ──────────
    'manage_title' => 'Manage my booking',
    'manage_intro' => 'Here you can see your booking, move it to another day or time, or cancel it.',
    'manage_summary_aria' => 'Your booking details',
    'manage_label_ref' => 'Booking no.',
    'manage_label_date' => 'Day',
    'manage_label_time' => 'Time',
    'manage_label_duration' => 'Duration',
    'manage_label_modality' => 'Type',
    'manage_label_status' => 'Status',
    'manage_deadline' => 'You can change or cancel it here until :date at :time.',
    'manage_too_late' => 'You can no longer change or cancel the booking here: there are less than :hours hours left or it has already passed.',
    'manage_contact' => 'If you need to, get in touch with us:',
    'manage_cancelled_state' => 'This booking is cancelled.',
    'manage_book_new' => 'Book a new appointment',
    'manage_btn_reschedule' => 'Change day or time',
    'manage_btn_cancel' => 'Cancel the booking',
    'manage_back' => 'Back',
    'manage_reschedule_title' => 'Choose the new day and time',
    'manage_reschedule_note' => 'Once changed, the booking is pending again until we confirm it.',
    'manage_days_label' => 'Day',
    'manage_times_label' => 'Time (:duration)',
    'manage_no_slots' => 'No free times left that day. Try another one.',
    'manage_new_summary' => 'New booking: :date at :time',
    'manage_reschedule_confirm' => 'Confirm the change',
    'manage_cancel_title' => 'Are you sure you want to cancel the booking?',
    'manage_cancel_text' => 'The slot will be free for someone else. If you want to come later on, you will need to book again.',
    'manage_cancel_confirm' => 'Yes, cancel the booking',
    'manage_cancelled_done' => 'Your booking is cancelled. We have let the business know.',
    'manage_rescheduled_done' => 'We have changed your booking. It is pending confirmation: you will get an email once we confirm it.',
    'manage_same_slot' => 'That is the same date and time you already had: nothing to change.',
    'manage_err_taken' => 'That time has just been taken. Please choose another.',
    'manage_err_retry' => 'Another booking for that day is in progress right now. Please try again in a few seconds.',
    'manage_err_invalid' => 'The change could not be made. Please reload the page and try again.',
    'manage_err_too_many' => 'You have made too many changes in a row. Please try again later.',
    'manage_duration_unavailable' => 'This booking has a duration that is no longer offered online; to change it, please get in touch with us.',

    // ── 10. PANEL: VIEWS, MANUAL ENTRY AND WHATSAPP ───────────────────────
    'panel_err_changed' => 'The client has just changed this booking. Check the new day and time and confirm again.',
    'panel_view_aria' => 'Which bookings to show',
    'panel_view_today' => 'Today',
    'panel_view_upcoming' => 'Upcoming',
    'panel_view_all' => 'All',
    'panel_today_heading' => 'Today, :date',
    'panel_empty_today' => 'No bookings today.',
    'panel_empty_upcoming' => 'No upcoming bookings.',
    'action_whatsapp' => 'WhatsApp',
    'whatsapp_aria' => 'Message :name on WhatsApp (opens in a new tab)',
    'whatsapp_text' => 'Hi :name, I am writing about your booking :reference on :date at :time.',
    'new_open' => 'New booking',
    'new_close' => 'Close',
    'new_title' => 'New booking by phone',
    'new_intro' => 'For clients who call you: the booking is confirmed when saved. If you add their email, they get the confirmation; without an email nothing is sent.',
    'new_field_name' => 'Name *',
    'new_field_email' => 'Email (optional)',
    'new_field_phone' => 'Phone (optional)',
    'new_field_date' => 'Day *',
    'new_field_time' => 'Time *',
    'new_field_duration' => 'Duration',
    'new_field_modality' => 'Type',
    'new_field_message' => 'Note (optional)',
    'new_field_locale' => 'Email language',
    'new_choose_day' => 'Choose a day',
    'new_choose_time' => 'Choose a time',
    'new_no_slots' => 'No free times left that day for that duration.',
    'new_submit' => 'Save booking',
    'new_saved' => 'Booking :reference saved and confirmed.',
    'new_err_name' => 'Enter the name (2 to 80 characters).',
    'new_err_email' => 'That email is not valid.',
    'new_err_phone' => 'The phone number is too long.',
    'new_err_day' => 'Choose a day from the list.',
    'new_err_time' => 'Choose a time from the list.',
    'new_err_invalid' => 'Invalid value.',
    'new_err_message' => 'The note is too long (1000 characters at most).',
    'new_err_slot' => 'That time is no longer free. Please choose another.',
    'new_err_retry' => 'Another booking for that day is in progress right now. Please try again in a few seconds.',

];
