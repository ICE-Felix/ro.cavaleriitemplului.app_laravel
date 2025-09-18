<?php
namespace App\Support;

use Carbon\Carbon;

final class DateTime
{
    public static function combine(?string $date, ?string $time, ?string $tz = null): ?Carbon
    {
        if (!$date || !$time) return null;

        $date = trim($date);
        $time = trim($time);
        $tz   = $tz ?? config('app.timezone', 'Europe/Bucharest');

        foreach (['Y-m-d H:i', 'd-m-Y H:i', 'm/d/Y H:i'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, "$date $time", $tz);
                if ($dt !== false) return $dt;
            } catch (\Throwable $e) {}
        }

        try {
            return Carbon::parse("$date $time", $tz);
        } catch (\Throwable $e) {
            return null;
        }
    }
}