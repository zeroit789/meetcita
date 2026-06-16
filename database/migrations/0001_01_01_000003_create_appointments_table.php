<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|==============================================================================
| create_appointments_table / Tabla 'appointments' (citas)
|==============================================================================
| EN: Bookings requested by clients. This is the CONSOLIDATED final schema:
|     the original project grew it across 9 evolutionary migrations; here it is
|     a single clean table with all final columns. The anti-double-booking
|     guarantee is a PARTIAL unique index on (date, time) WHERE status !=
|     'cancelada', applied per database driver below (see PARTIAL INDEX note).
| ES: Citas que solicitan los clientes. Este es el esquema final CONSOLIDADO:
|     en el proyecto original creció en 9 migraciones evolutivas; aquí es una
|     única tabla limpia con todas las columnas finales. La garantía
|     anti-doble-reserva es un índice único PARCIAL sobre (date, time) WHERE
|     status != 'cancelada', aplicado por driver de base de datos más abajo
|     (ver nota del ÍNDICE PARCIAL).
|==============================================================================
*/
return new class extends Migration
{
    /**
     * EN: Create the bookings table with the final schema.
     * ES: Crea la tabla de citas con el esquema final.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // EN: Public booking code ("APT-K7P3Q"). Nullable + unique: the model
            //     generates it on create; unique enforces no collisions.
            // ES: Código público de la cita ("APT-K7P3Q"). Nullable + unique: el
            //     modelo lo genera al crear; unique garantiza que no se repita.
            $table->string('reference', 16)->nullable()->unique();

            // EN: Client contact data. ES: Datos de contacto del cliente.
            $table->string('name');                  // EN: client name (required) · ES: nombre (obligatorio)
            $table->string('email');                 // EN: client email (required) · ES: email (obligatorio)
            $table->string('phone')->nullable();     // EN: phone (optional) · ES: teléfono (opcional)

            // EN: Date and time of the booking. ES: Fecha y hora de la cita.
            $table->date('date');                    // EN: booking day (YYYY-MM-DD) · ES: día de la cita
            $table->string('time', 5);               // EN: slot "HH:MM" · ES: hora del slot "HH:MM"

            // EN: Duration in minutes (30 = half hour, 60 = one hour). Default 30.
            // ES: Duración en minutos (30 = media hora, 60 = una hora). Por defecto 30.
            $table->unsignedSmallInteger('duration')->default(30);

            // EN: Modality: 'online' (Meet videocall) or 'presencial' (in-person).
            // ES: Modalidad: 'online' (videollamada Meet) o 'presencial'.
            $table->string('modality', 10)->default('online');

            // EN: Client language (es|en) for the booking emails. Default from intent.
            // ES: Idioma del cliente (es|en) para los emails de la cita.
            $table->string('locale', 5)->default('es');

            // EN: Google Calendar references (filled when the event is created).
            // ES: Referencias de Google Calendar (se rellenan al crear el evento).
            $table->string('google_event_id')->nullable();  // EN: event id · ES: id del evento
            $table->string('google_meet_url')->nullable();   // EN: Meet link (online only) · ES: enlace Meet (solo online)

            // EN: Attendees. 'attendees' = names; 'attendee_emails' = extra emails
            //     to invite (comma-separated). Both optional.
            // ES: Asistentes. 'attendees' = nombres; 'attendee_emails' = correos
            //     extra a invitar (separados por comas). Ambos opcionales.
            $table->string('attendees')->nullable();
            $table->text('attendee_emails')->nullable();

            // EN: 'message' = the real meeting topic (optional). 'reason' = kept
            //     NOT NULL for compatibility (filled with the same content as message).
            // ES: 'message' = el asunto real de la reunión (opcional). 'reason' =
            //     se conserva NOT NULL por compatibilidad (= message al crear).
            $table->text('message')->nullable();
            $table->text('reason');

            // EN: Status: pendiente / confirmada / cancelada. Default 'pendiente'.
            // ES: Estado: pendiente / confirmada / cancelada. Por defecto 'pendiente'.
            $table->enum('status', ['pendiente', 'confirmada', 'cancelada'])
                  ->default('pendiente');

            $table->timestamps();
        });

        // ──────────────────────────────────────────────────────────────────────
        // PARTIAL INDEX / ÍNDICE PARCIAL — anti-double-booking at DB level
        // ──────────────────────────────────────────────────────────────────────
        // EN: DB-level anti-double-booking guarantee. We want unicity on
        //     (date, time) ONLY for bookings that are NOT cancelled, so a cancelled
        //     booking frees the slot and the same (date, time) can be re-booked.
        //
        //     PORTABILITY DECISION (multi-driver): partial indexes (with WHERE)
        //     are supported by SQLite and PostgreSQL but NOT by MySQL/MariaDB.
        //       - sqlite / pgsql: real PARTIAL UNIQUE index → strong DB guarantee.
        //       - mysql:          no partial index → we create a NON-unique
        //                         composite index on (date, time, status). It does
        //                         NOT enforce uniqueness; the app already prevents
        //                         double-booking with a pessimistic lock
        //                         (lockForUpdate) inside a transaction in the
        //                         Livewire component, and this index just speeds up
        //                         the availability lookups.
        //
        // ES: Garantía anti-doble-reserva a nivel de BD. Queremos unicidad sobre
        //     (date, time) SOLO para citas que NO estén canceladas, así una cita
        //     cancelada libera el hueco y se puede volver a reservar ese mismo
        //     (date, time).
        //
        //     DECISIÓN DE PORTABILIDAD (multi-driver): los índices parciales (con
        //     WHERE) los soportan SQLite y PostgreSQL pero NO MySQL/MariaDB.
        //       - sqlite / pgsql: índice ÚNICO PARCIAL real → garantía fuerte en BD.
        //       - mysql:          sin índice parcial → creamos un índice compuesto
        //                         NO único sobre (date, time, status). NO impone
        //                         unicidad; la app ya evita la doble reserva con un
        //                         bloqueo pesimista (lockForUpdate) dentro de una
        //                         transacción en el componente Livewire, y este
        //                         índice solo acelera las consultas de disponibilidad.
        // ──────────────────────────────────────────────────────────────────────
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // EN: SQLite partial unique index (string literal with double quotes).
            // ES: Índice único parcial de SQLite (literal con comillas dobles).
            DB::statement('CREATE UNIQUE INDEX appointments_active_slot_unique ON appointments (date, time) WHERE status != \'cancelada\'');
        } elseif ($driver === 'pgsql') {
            // EN: PostgreSQL partial unique index. ES: Índice único parcial de PostgreSQL.
            DB::statement('CREATE UNIQUE INDEX appointments_active_slot_unique ON appointments (date, time) WHERE status <> \'cancelada\'');
        } else {
            // EN: MySQL/MariaDB and others: non-unique composite index (no partial
            //     index support). App-level lock guards against double-booking.
            // ES: MySQL/MariaDB y otros: índice compuesto NO único (sin soporte de
            //     índice parcial). El bloqueo a nivel de app evita la doble reserva.
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['date', 'time', 'status'], 'appointments_active_slot_idx');
            });
        }
    }

    /**
     * EN: Drop the bookings table (and its index implicitly).
     * ES: Elimina la tabla de citas (y su índice de forma implícita).
     */
    public function down(): void
    {
        // EN: Drop the partial index first for sqlite/pgsql (IF EXISTS so rollback
        //     never fails). For mysql the index drops with the table.
        // ES: Soltamos primero el índice parcial en sqlite/pgsql (IF EXISTS para
        //     que el rollback nunca falle). En mysql el índice cae con la tabla.
        $driver = DB::getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX IF EXISTS appointments_active_slot_unique');
        }

        Schema::dropIfExists('appointments');
    }
};
