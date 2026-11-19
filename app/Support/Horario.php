<?php

namespace App\Support;

/*
|==============================================================================
| Horario / Schedule helpers
|==============================================================================
| ES: Ayudas para el horario por día de la semana (config schedule.hours):
|       - normalizar(): limpia la config (días ISO 1-7, tramos "HH:MM" válidos
|         con apertura < cierre, ordenados) y descarta lo mal escrito.
|       - desdeTexto(): lee el horario escrito en UNA línea del .env
|         (APPOINTMENTS_HOURS), p. ej. "1-4 09:00-14:00 16:00-19:00; 5 09:00-14:00".
| EN: Helpers for the per-weekday schedule (config schedule.hours):
|       - normalizar(): cleans the config (ISO days 1-7, valid "HH:MM" ranges
|         with open < close, sorted) and drops anything badly written.
|       - desdeTexto(): reads the schedule written on ONE .env line
|         (APPOINTMENTS_HOURS), e.g. "1-4 09:00-14:00 16:00-19:00; 5 09:00-14:00".
|==============================================================================
*/
class Horario
{
    /**
     * ES: Limpia el horario por día: solo días 1-7 con tramos ["HH:MM","HH:MM"]
     *     válidos (apertura < cierre), ordenados por hora. Los días sin ningún
     *     tramo válido desaparecen (= cerrados).
     * EN: Cleans the per-day schedule: only days 1-7 with valid ["HH:MM","HH:MM"]
     *     ranges (open < close), sorted by time. Days with no valid range are
     *     dropped (= closed).
     *
     * @param  mixed  $horas  ES: config cruda · EN: raw config
     * @return array<int, array<int, array{0:string, 1:string}>>
     */
    public static function normalizar(mixed $horas): array
    {
        $limpio = [];

        foreach ((array) $horas as $dia => $tramos) {
            $dia = (int) $dia;
            if ($dia < 1 || $dia > 7) {
                continue;
            }

            $validos = [];
            foreach ((array) $tramos as $tramo) {
                $tramo = array_values((array) $tramo);
                $abre = (string) ($tramo[0] ?? '');
                $cierra = (string) ($tramo[1] ?? '');
                // ES: Formato exacto y apertura antes del cierre. EN: Exact format and open before close.
                if (self::esHora($abre) && self::esHora($cierra) && $abre < $cierra) {
                    $validos[] = [$abre, $cierra];
                }
            }

            if ($validos !== []) {
                // ES: Ordenados por apertura y fusionados si se solapan o se tocan
                //     (09:00-12:00 + 11:00-14:00 = 09:00-14:00).
                // EN: Sorted by opening time and merged if they overlap or touch
                //     (09:00-12:00 + 11:00-14:00 = 09:00-14:00).
                usort($validos, fn (array $a, array $b) => strcmp($a[0], $b[0]));
                $fusion = [];
                foreach ($validos as $tramo) {
                    $ultimo = count($fusion) - 1;
                    if ($ultimo >= 0 && $tramo[0] <= $fusion[$ultimo][1]) {
                        $fusion[$ultimo][1] = max($fusion[$ultimo][1], $tramo[1]);
                    } else {
                        $fusion[] = $tramo;
                    }
                }
                $limpio[$dia] = $fusion;
            }
        }

        ksort($limpio);

        return $limpio;
    }

    /**
     * ES: Convierte el horario escrito en una línea en el array de la config.
     *     Grupos separados por ";": primero el día o rango de días ISO (1 = lun …
     *     7 = dom, "1-4" = lunes a jueves, "1,3" = lunes y miércoles) y después
     *     uno o más tramos "HH:MM-HH:MM". Lo que no se entiende se ignora.
     * EN: Turns the one-line schedule into the config array. Groups split by
     *     ";": first the ISO day or day range (1 = Mon … 7 = Sun, "1-4" =
     *     Monday to Thursday, "1,3" = Monday and Wednesday) and then one or
     *     more "HH:MM-HH:MM" ranges. Anything not understood is ignored.
     *
     * @return array<int, array<int, array{0:string, 1:string}>>
     */
    public static function desdeTexto(?string $texto): array
    {
        $horas = [];

        foreach (explode(';', (string) $texto) as $grupo) {
            $partes = preg_split('/\s+/', trim($grupo), -1, PREG_SPLIT_NO_EMPTY);
            if (count($partes) < 2) {
                continue;
            }

            // ES: Días: "1-4", "1,3" o "5". EN: Days: "1-4", "1,3" or "5".
            $dias = [];
            foreach (explode(',', array_shift($partes)) as $trozo) {
                if (preg_match('/^([1-7])-([1-7])$/', $trozo, $m) && $m[1] <= $m[2]) {
                    $dias = array_merge($dias, range((int) $m[1], (int) $m[2]));
                } elseif (preg_match('/^[1-7]$/', $trozo)) {
                    $dias[] = (int) $trozo;
                }
            }

            // ES: Tramos "HH:MM-HH:MM". EN: "HH:MM-HH:MM" ranges.
            $tramos = [];
            foreach ($partes as $parte) {
                if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $parte, $m)) {
                    $tramos[] = [$m[1], $m[2]];
                }
            }

            foreach ($dias as $dia) {
                $horas[$dia] = array_merge($horas[$dia] ?? [], $tramos);
            }
        }

        return self::normalizar($horas);
    }

    /**
     * ES: ¿Es una hora "HH:MM" real (00:00-23:59, o 24:00 como cierre)?
     * EN: Is it a real "HH:MM" time (00:00-23:59, or 24:00 as a closing time)?
     */
    public static function esHora(string $hora): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) || $hora === '24:00';
    }
}
