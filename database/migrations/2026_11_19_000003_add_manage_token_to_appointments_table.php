<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
|==============================================================================
| add_manage_token_to_appointments_table / Columna manage_token en 'appointments'
|==============================================================================
| ES: Token aleatorio del enlace «gestionar mi cita». Va dentro del enlace
|     firmado y la página y cada acción exigen que coincida con el guardado.
|     Cambia al cancelar o reprogramar la cita, así los enlaces ya enviados
|     dejan de valer (un enlace reenviado o los de la hora vieja no sirven).
|     Las citas que ya existen reciben uno nuevo aquí.
| EN: Random token of the "manage my booking" link. It travels inside the
|     signed link and the page and every action require it to match the
|     stored one. It changes when the booking is cancelled or rescheduled, so
|     links already sent stop working (a forwarded link or the old time's
|     ones are useless). Existing bookings get a new one here.
|==============================================================================
*/
return new class extends Migration
{
    /**
     * ES: Añade manage_token (nullable) y rellena las citas que ya existen.
     * EN: Adds manage_token (nullable) and fills the existing bookings.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('manage_token', 64)->nullable()->after('revision');
        });

        // ES: Uno distinto por cita (sin cargar modelos: la migración no depende
        //     de cómo sea el modelo en el futuro).
        // EN: A different one per booking (no models loaded: the migration
        //     doesn't depend on what the model looks like in the future).
        //     Por lotes (sin cargar todos los ids en memoria) y sin pisar un
        //     token que ya tenga la fila.
        //     In batches (without loading every id into memory) and without
        //     overwriting a token the row already has.
        DB::table('appointments')->whereNull('manage_token')->select('id')
            ->chunkById(500, function ($filas) {
                foreach ($filas as $fila) {
                    DB::table('appointments')->where('id', $fila->id)->whereNull('manage_token')
                        ->update(['manage_token' => Str::random(40)]);
                }
            });
    }

    /**
     * ES: Quita la columna (reversible).
     * EN: Drops the column (reversible).
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('manage_token');
        });
    }
};
