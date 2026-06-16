<?php

/*
|==============================================================================
| ES — UI translations for the appointments module / Traducciones ES del módulo
|==============================================================================
| EN: Every visible string in the booking wizard, the panel, the login and the
|     landing lives here. Neutral wording (no personal brand). The English
|     mirror is lang/en/citas.php — keep both in sync.
| ES: Aquí viven todos los textos visibles del wizard de reserva, el panel, el
|     login y la landing. Redacción neutra (sin marca personal). El espejo en
|     inglés es lang/en/citas.php — mantén ambos sincronizados.
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
    'wrapper_title'     => 'Agenda una cita',
    'wrapper_intro'     => 'Reserva una llamada o reunión. Elige el día y la hora que mejor te venga; te confirmaremos por email.',

    // ── 2. WIZARD / WIZARD DE RESERVA ─────────────────────────────────────
    // Step indicator / Indicador de pasos
    'steps_01' => '01 día',
    'steps_02' => '02 hora',
    'steps_03' => '03 datos',

    // Step 1: pick a day / Paso 1: elegir día
    'step1_cmd'        => '$ ./availability.sh --calendar',
    'step1_title'      => 'Elige un día',
    'cal_prev_aria'    => 'Mes anterior',
    'cal_next_aria'    => 'Mes siguiente',
    'cal_today_suffix' => ' (hoy)',
    'cal_available'    => ', disponible',
    'cal_selected'     => ', seleccionado',
    'cal_unavailable'  => ', no disponible',
    'cal_weekdays'     => 'L,M,X,J,V,S,D',
    'cal_hours_label'  => 'Horario:',
    'cal_hours_value'  => 'Lunes a viernes · 9:30–18:00',
    'cal_hours_note'   => '(horario continuo)',

    // Step 2: pick a time / Paso 2: elegir hora
    'step2_cmd'            => '$ ./availability.sh --slots',
    'step2_title'          => 'Elige duración y hora',
    'step2_subtitle_pre'   => 'Huecos libres para el',
    'step2_duration_label' => '// duración de la reunión',
    'btn_30min'            => '30 min',
    'btn_1hour'            => '1 hora',
    // EN: generic label for non-standard durations (:min minutes).
    // ES: etiqueta genérica para duraciones no estándar (:min minutos).
    'btn_minutes'          => ':min min',
    'step2_no_slots'       => '// no quedan huecos libres este día',
    'step2_no_slots_hint'  => 'Prueba con otro día.',
    'step2_back'           => '← cambiar día',

    // Step 3: details form / Paso 3: formulario de datos
    'step3_cmd'                      => '$ ./book.sh --details',
    'step3_title'                    => 'Tus datos',
    'step3_summary_pre'             => 'Cita para el',
    'step3_summary_at'              => 'a las',
    'honeypot_label'               => 'No rellenar este campo',
    'field_modality'                => 'Modalidad *',
    'modality_online'               => 'Online',
    'modality_online_sub'           => 'videollamada (Meet)',
    'modality_inperson'             => 'Presencial',
    'modality_inperson_sub'         => 'nos vemos en persona',
    'field_name'                    => 'Nombre *',
    'ph_name'                       => 'Tu nombre',
    'field_email'                   => 'Email *',
    'ph_email'                      => 'tu@email.com',
    'field_phone'                   => 'Teléfono',
    'field_phone_optional'          => '(opcional)',
    'ph_phone'                      => '+34 ...',
    'field_attendees'               => 'Personas que asistirán *',
    'ph_attendees'                  => 'Nombres de quienes asistirán a la reunión',
    'field_attendee_emails'         => 'Correos de los asistentes',
    'field_attendee_emails_optional' => '(opcional)',
    'ph_attendee_emails'            => 'ana@email.com, juan@email.com',
    'help_attendee_emails'          => '// les llegará la invitación con el enlace de la videollamada. Sepáralos por comas.',
    'field_message'                 => '¿Qué tienes pensado tratar en la reunión? *',
    'ph_message'                    => 'Cuéntanos brevemente qué quieres hacer o tratar...',
    'step3_back'                    => '← cambiar hora',
    'btn_book'                      => 'Reservar cita',
    'btn_booking'                   => 'Reservando...',

    // Step 4: confirmation / Paso 4: confirmación
    'step4_cmd'          => '$ appointment --status requested',
    'step4_title'        => '¡Cita solicitada!',
    'step4_summary_pre'  => 'Tu cita para el',
    'step4_summary_at'   => 'a las',
    'step4_summary_post' => 'está registrada.',
    'step4_ref_label'    => 'Tu nº de cita',
    'step4_ref_hint'     => '// guárdalo: identifícate con él si necesitas escribirnos sobre esta cita',
    'step4_email_note'   => '// te confirmaremos por email lo antes posible',
    'btn_book_another'   => 'Pedir otra cita',

    // Duration labels (used across steps) / Etiquetas de duración (varios pasos)
    'dur_1hour'    => '1 hora',
    'dur_30min'    => '30 min',
    'dur_min_unit' => 'min', // EN: minutes unit in the panel / ES: unidad de minutos en el panel

    // ── 3. CLIENT ERRORS / ERRORES PARA EL CLIENTE ────────────────────────
    'err_too_many_attempts' => 'Demasiados intentos. Inténtalo de nuevo dentro de un rato.',
    'err_email_invalid'     => 'El correo ":email" no es válido. Revisa la lista (sepáralos por comas).',
    'err_max_attendees'     => 'Máximo :max asistentes.',
    'err_retry_soon'        => 'Inténtalo de nuevo en un momento.',
    'err_slot_taken'        => 'Ese hueco acaba de ocuparse, elige otro.',
    'err_slot_taken_step2'  => 'Ese hueco se acaba de ocupar. Elige otra hora, por favor.',

    // ── 4. LAYOUT / SELECTOR DE IDIOMA + FOOTER ───────────────────────────
    'lang_aria'      => 'Seleccionar idioma',
    // EN: aria-label for the sun/moon theme toggle button.
    // ES: aria-label del botón sol/luna para cambiar de tema.
    'theme_toggle'   => 'Cambiar tema',
    'footer_website' => 'Web',
    'footer_repo'    => 'Código',

    // ── 5. LANDING / PÁGINA DE INICIO ─────────────────────────────────────
    'home_title'    => 'Sistema de reservas de citas',
    'home_lead'     => 'Una demo limpia y open-source de un sistema de reservas: calendario, selección de hora, modalidad online o presencial y panel de gestión. Pruébalo reservando una cita de ejemplo.',
    'home_cta_book' => 'Reservar una cita',
    'home_cta_repo' => 'Ver el código',
    'home_feat_1'   => 'calendario reactivo sin recargar',
    'home_feat_2'   => 'online (Meet) o presencial',
    'home_feat_3'   => 'panel de gestión bilingüe',

    // ── 6. PANEL / TABLA DE CITAS ─────────────────────────────────────────
    'panel_title'    => 'Panel de citas',
    'panel_heading'  => 'Panel de citas',
    'panel_subtitle' => 'Todas las solicitudes recibidas desde /citas.',
    'panel_logout'   => 'cerrar sesión',
    'panel_empty'    => 'todavía no hay citas registradas',
    // Table columns / Columnas de la tabla
    'col_ref'      => 'Nº Cita',
    'col_date'     => 'Fecha',
    'col_time'     => 'Hora',
    'col_duration' => 'Duración',
    'col_modality' => 'Modalidad',
    'col_name'     => 'Nombre',
    'col_email'    => 'Email',
    'col_phone'    => 'Teléfono',
    'col_people'   => 'Personas',
    'col_message'  => 'Mensaje',
    'col_status'   => 'Estado',
    'col_actions'  => 'Acciones',
    'meet_link'    => 'Meet',
    // Statuses / Estados
    'status_pending'   => 'pendiente',
    'status_confirmed' => 'confirmada',
    'status_cancelled' => 'cancelada',
    // Actions / Acciones
    'action_confirm' => 'confirmar',
    'action_cancel'  => 'cancelar',

    // ── 7. BLOCKED DAYS / DÍAS BLOQUEADOS ─────────────────────────────────
    'blocked_title'                 => 'Días no disponibles',
    'blocked_intro'                 => 'Marca aquí tus vacaciones o días en los que no estarás operativo. Esos días aparecerán deshabilitados en el calendario de reservas.',
    'blocked_field_date'            => 'Fecha *',
    'blocked_field_reason'          => 'Motivo',
    'blocked_field_reason_optional' => '(opcional)',
    'blocked_ph_reason'             => 'Vacaciones, festivo...',
    'blocked_add'                   => 'Bloquear día',
    'blocked_empty'                 => 'no hay días bloqueados. Estás disponible todos los días laborables.',
    'blocked_remove'                => 'quitar',
    'blocked_confirm_remove'        => '¿Quitar el bloqueo de este día? Volverá a estar disponible para reservas.',
    // Blocked-day validation messages / Mensajes de validación de día bloqueado
    'blocked_err_required'          => 'Elige una fecha.',
    'blocked_err_invalid'           => 'Fecha no válida.',
    'blocked_err_past'              => 'No tiene sentido bloquear un día que ya pasó.',
    'blocked_err_duplicate'         => 'Ese día ya está bloqueado.',
    'blocked_err_reason_long'       => 'El motivo es demasiado largo.',
    'blocked_err_has_appointments'  => 'Ese día tiene citas reservadas; gestiónalas antes de bloquearlo.',

    // ── 8. LOGIN / LOGIN DEL PANEL ────────────────────────────────────────
    'login_title'    => 'Acceso al panel',
    'login_password' => 'Contraseña',
    'login_submit'   => 'Entrar',
    'login_back'     => '← volver al sitio',

];
