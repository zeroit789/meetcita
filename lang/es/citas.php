<?php

/*
|==============================================================================
| ES — UI translations for the appointments module / Traducciones ES del módulo
|==============================================================================
| ES: Aquí viven todos los textos visibles del wizard de reserva, el panel, el
|     login y la landing. Redacción neutra (sin marca personal). El espejo en
|     inglés es lang/en/citas.php — mantén ambos sincronizados.
| EN: Every visible string in the booking wizard, the panel, the login and the
|     landing lives here. Neutral wording (no personal brand). The English
|     mirror is lang/en/citas.php — keep both in sync.
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
    'wrapper_back_home' => '← Volver al inicio',
    'wrapper_title' => 'Agenda una cita',
    'wrapper_intro' => 'Reserva una llamada o reunión. Elige el día y la hora que mejor te venga; te confirmaremos por email.',

    // ── 2. WIZARD / WIZARD DE RESERVA ─────────────────────────────────────
    // Step indicator / Indicador de pasos
    'steps_01' => 'Día',
    'steps_02' => 'Hora',
    'steps_03' => 'Tus datos',
    // ES: Nombre de la lista de pasos para lectores de pantalla. EN: Step list name for screen readers.
    'steps_aria' => 'Pasos de la reserva',

    // Step 1: pick a day / Paso 1: elegir día
    'step1_title' => 'Elige un día',
    'cal_prev_aria' => 'Mes anterior',
    'cal_next_aria' => 'Mes siguiente',
    'cal_today_suffix' => ' (hoy)',
    'cal_available' => ', disponible',
    'cal_selected' => ', seleccionado',
    'cal_unavailable' => ', no disponible',
    'cal_weekdays' => 'L,M,X,J,V,S,D',
    'cal_hours_label' => 'Horario:',
    // ES: Horario generado desde la config (días y horas). EN: Schedule built from the config.
    'cal_hours_value' => ':days · :hours',
    'cal_days_range' => ':from a :to',
    'cal_days_join' => ', ',
    // ES: Unión de tramos de un día y de grupos de días con horarios distintos.
    // EN: Join between a day's ranges and between day groups with different hours.
    'cal_ranges_join' => ' y ',
    'cal_groups_join' => '; ',

    // Step 2: pick a time / Paso 2: elegir hora
    'step2_title' => 'Elige duración y hora',
    'step2_subtitle_pre' => 'Huecos libres para el',
    'step2_duration_label' => 'Duración de la reunión',
    'btn_30min' => '30 min',
    'btn_1hour' => '1 hora',
    // ES: etiqueta genérica para duraciones no estándar (:min minutos).
    // EN: generic label for non-standard durations (:min minutes).
    'btn_minutes' => ':min min',
    'step2_no_slots' => 'No quedan horas libres este día.',
    'step2_no_slots_hint' => 'Prueba con otro día.',
    'step2_back' => '← Cambiar día',

    // Step 3: details form / Paso 3: formulario de datos
    'step3_title' => 'Tus datos',
    'step3_summary_pre' => 'Cita para el',
    'step3_summary_at' => 'a las',
    'honeypot_label' => 'No rellenar este campo',
    'field_modality' => 'Modalidad *',
    'modality_online' => 'Online',
    'modality_online_sub' => 'videollamada (Meet)',
    'modality_inperson' => 'Presencial',
    'modality_inperson_sub' => 'nos vemos en persona',
    'field_name' => 'Nombre *',
    'ph_name' => 'Tu nombre',
    'field_email' => 'Email *',
    'ph_email' => 'tu@email.com',
    'field_phone' => 'Teléfono',
    'field_phone_optional' => '(opcional)',
    'ph_phone' => '+34 ...',
    'field_attendees' => 'Personas que asistirán',
    'ph_attendees' => 'Nombres de quienes asistirán a la reunión',
    'field_attendee_emails' => 'Correos de los asistentes',
    'field_attendee_emails_optional' => '(opcional)',
    'ph_attendee_emails' => 'ana@email.com, juan@email.com',
    'help_attendee_emails' => 'Les llegará la invitación con el enlace de la videollamada. Sepáralos por comas.',
    'field_message' => '¿Qué te gustaría tratar en la cita?',
    'help_message' => 'No incluyas datos de salud ni otros datos sensibles: no hacen falta para la cita.',
    'ph_message' => 'Cuéntanos brevemente qué quieres hacer o tratar...',
    // ES: Primera capa de protección de datos (bajo el formulario).
    // EN: First-layer data-protection notice (under the form).
    'privacy_title' => 'Información básica sobre protección de datos',
    'privacy_label_controller' => 'Responsable',
    'privacy_label_purpose' => 'Finalidad',
    'privacy_label_basis' => 'Legitimación',
    'privacy_label_recipients' => 'Destinatarios',
    'privacy_label_rights' => 'Derechos',
    'privacy_purpose' => 'Gestionar tu cita y enviarte la confirmación y los avisos sobre ella.',
    'privacy_basis' => 'Tu solicitud de cita (medidas precontractuales, art. 6.1.b RGPD).',
    'privacy_recipients' => 'No se ceden datos a terceros. Los tratan los proveedores de correo y alojamiento del negocio y, si están activados, Google Calendar y Telegram.',
    'privacy_rights' => 'Acceso, rectificación, supresión, oposición, limitación y portabilidad, escribiendo a :email.',
    'privacy_retention' => 'Se conservan :months meses tras la cita y después se borran.',
    'privacy_more' => 'Más información en la :link.',
    'privacy_link' => 'política de privacidad',
    'step3_back' => '← Cambiar hora',
    'btn_book' => 'Reservar cita',
    'btn_booking' => 'Reservando...',

    // Step 4: confirmation / Paso 4: confirmación
    'step4_title' => '¡Cita solicitada!',
    'step4_summary_pre' => 'Tu cita para el',
    'step4_summary_at' => 'a las',
    'step4_summary_post' => 'está registrada.',
    'step4_ref_label' => 'Tu nº de cita',
    'step4_ref_hint' => 'Guárdalo: indícalo si necesitas escribirnos sobre esta cita.',
    'step4_email_note' => 'Te confirmaremos la cita por email lo antes posible.',
    'btn_book_another' => 'Pedir otra cita',

    // Duration labels (used across steps) / Etiquetas de duración (varios pasos)
    'dur_1hour' => '1 hora',
    'dur_30min' => '30 min',
    // ES: Textos de calendario (.ics y Google), en el idioma de la cita.
    // EN: Calendar texts (.ics and Google), in the booking's language.
    'cal_location_online' => 'Online',
    'cal_location_in_person' => 'Presencial',
    'cal_desc_topic' => 'Asunto: :text',
    'cal_desc_video' => 'Videollamada: :url',
    'cal_desc_reference' => 'Referencia: :ref',
    'cal_desc_requested_from' => 'Cita solicitada desde :host',
    'cal_desc_attendees' => 'Asistentes: :text',
    'cal_desc_modality' => 'Modalidad: :text',
    'dur_minutes' => ':min min',
    'dur_hours' => '{1} :h hora|[2,*] :h horas',
    'dur_hours_minutes' => ':h h :min min',
    'dur_min_unit' => 'min', // ES: unidad de minutos en el panel / EN: minutes unit in the panel

    // ── 3. CLIENT ERRORS / ERRORES PARA EL CLIENTE ────────────────────────
    'err_too_many_attempts' => 'Demasiados intentos. Inténtalo de nuevo dentro de un rato.',
    'err_email_invalid' => 'El correo ":email" no es válido. Revisa la lista (sepáralos por comas).',
    'err_max_attendees' => 'Máximo :max asistentes.',
    'err_email_limit' => 'Este correo ya tiene :max citas pendientes. Cuando pasen o se cancelen podrás pedir otra.',
    'err_captcha' => 'Comprueba que no eres un robot (marca la casilla) y vuelve a enviar.',
    // ES: Modo demo. EN: Demo mode.
    'demo_banner' => 'Esto es una demo: no se envía ningún correo y los datos se borran cada hora. No escribas datos reales.',
    'demo_panel_hint' => 'Demo: la contraseña del panel es «:password».',
    'err_retry_soon' => 'Inténtalo de nuevo en un momento.',
    'err_slot_taken' => 'Ese hueco acaba de ocuparse, elige otro.',
    'err_slot_taken_step2' => 'Ese hueco se acaba de ocupar. Elige otra hora, por favor.',

    // ── 4. LAYOUT / SELECTOR DE IDIOMA + FOOTER ───────────────────────────
    'lang_aria' => 'Seleccionar idioma',
    // ES: aria-label del botón sol/luna para cambiar de tema.
    // EN: aria-label for the sun/moon theme toggle button.
    'theme_toggle' => 'Cambiar tema',
    'footer_website' => 'Web',

    // ── 5. LANDING / PÁGINA DE INICIO ─────────────────────────────────────
    'home_title' => 'Pide tu cita',
    'home_lead' => 'Reserva una cita en un par de minutos: elige el día y la hora que mejor te vengan, online o en persona, y te la confirmamos por email.',
    'home_cta_book' => 'Reservar una cita',
    'home_cta_repo' => 'Ver el código de la demo',
    'home_feat_1' => 'Eliges día y hora libres',
    'home_feat_2' => 'Online por videollamada o en persona',
    'home_feat_3' => 'Confirmación por email',

    // ── 6. PANEL / TABLA DE CITAS ─────────────────────────────────────────
    'panel_title' => 'Panel de citas',
    'panel_heading' => 'Panel de citas',
    'panel_subtitle' => 'Todas las solicitudes de cita recibidas.',
    'panel_logout' => 'Cerrar sesión',
    'panel_empty' => 'Todavía no hay citas registradas.',
    'panel_list_title' => 'Citas recibidas',
    // Table columns / Columnas de la tabla
    'col_ref' => 'Nº Cita',
    'col_date' => 'Fecha',
    'col_time' => 'Hora',
    'col_duration' => 'Duración',
    'col_modality' => 'Modalidad',
    'col_name' => 'Nombre',
    'col_email' => 'Email',
    'col_phone' => 'Teléfono',
    'col_people' => 'Personas',
    'col_message' => 'Mensaje',
    'col_status' => 'Estado',
    'col_actions' => 'Acciones',
    'meet_link' => 'Meet',
    // Statuses / Estados
    'status_pending' => 'Pendiente',
    'status_confirmed' => 'Confirmada',
    'status_cancelled' => 'Cancelada',
    // Actions / Acciones
    'action_confirm' => 'Confirmar',
    'action_cancel' => 'Cancelar',
    // ES: Cancelación desde el panel (motivo opcional) y errores de estado.
    // EN: Cancelling from the panel (optional reason) and status errors.
    'cancel_reason_label' => 'Motivo para el cliente (opcional)',
    'cancel_reason_placeholder' => 'Se le enviará por email tal cual lo escribas.',
    'cancel_submit' => 'Cancelar cita y avisar',
    'cancel_back' => 'Volver',
    'panel_err_only_pending' => 'Solo se pueden confirmar las citas pendientes.',
    'panel_err_already_cancelled' => 'Esa cita ya estaba cancelada.',

    // ── 7. BLOCKED DAYS / DÍAS BLOQUEADOS ─────────────────────────────────
    'blocked_title' => 'Días no disponibles',
    'blocked_intro' => 'Marca aquí tus vacaciones o días en los que no estarás operativo. Esos días aparecerán deshabilitados en el calendario de reservas.',
    'blocked_field_date' => 'Fecha *',
    'blocked_field_reason' => 'Motivo',
    'blocked_field_reason_optional' => '(opcional)',
    'blocked_ph_reason' => 'Vacaciones, festivo...',
    'blocked_add' => 'Bloquear día',
    'blocked_empty' => 'No hay días bloqueados. Estás disponible todos los días laborables.',
    'blocked_remove' => 'Quitar',
    'blocked_confirm_remove' => '¿Quitar el bloqueo de este día? Volverá a estar disponible para reservas.',
    // Blocked-day validation messages / Mensajes de validación de día bloqueado
    'blocked_err_required' => 'Elige una fecha.',
    'blocked_err_invalid' => 'Fecha no válida.',
    'blocked_err_past' => 'No tiene sentido bloquear un día que ya pasó.',
    'blocked_err_duplicate' => 'Ese día ya está bloqueado.',
    'blocked_err_reason_long' => 'El motivo es demasiado largo.',
    'blocked_err_has_appointments' => 'Ese día tiene citas reservadas; gestiónalas antes de bloquearlo.',

    // ── 8. LOGIN / LOGIN DEL PANEL ────────────────────────────────────────
    'login_title' => 'Acceso al panel',
    'login_password' => 'Contraseña',
    'login_submit' => 'Entrar',
    'login_back' => '← Volver al sitio',

];
