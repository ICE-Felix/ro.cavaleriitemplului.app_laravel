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

    /**
     * Split an ISO/timestamptz into local date (Y-m-d) and time (H:i).
     */
    public static function splitIso(?string $iso, ?string $tz = null): array
    {
        if (!$iso || !is_string($iso)) {
            return ['date' => null, 'time' => null];
        }
        try {
            // ISO already contains an offset; we just display in app TZ.
            $tz = $tz ?? config('app.timezone', 'Europe/Bucharest');
            $dt = Carbon::parse($iso)->setTimezone($tz);

            return [
                'date' => $dt->format('Y-m-d'),
                'time' => $dt->format('H:i'),
            ];
        } catch (\Throwable $e) {
            return ['date' => null, 'time' => null];
        }
    }

    /**
     * For one row, add start_date/start_hour and end_date/end_hour
     * from its ISO fields. Works for events (start/end) and
     * venue_products (start_date/end_date).
     *
     * You can override input keys via:
     *   $props['datetime']['input']['start'|'end']
     * Otherwise we auto-detect.
     */
    public static function normalizeTemporalRow(array $row, array $props, ?string $tz = null): array
    {
        $tz     = $tz ?? config('app.timezone', 'Europe/Bucharest');
        $plural = strtolower($props['name']['plural'] ?? '');

        $dtCfg   = $props['datetime'] ?? [];

        // Which keys contain the ISO timestamps?
        // Allow override via props['datetime']['input']; otherwise detect.
        $inStart = $dtCfg['input']['start']
            ?? ($dtCfg['output']['start'] ?? (str_contains($plural, 'event') ? 'start' : 'start_date'));
        $inEnd   = $dtCfg['input']['end']
            ?? ($dtCfg['output']['end']   ?? (str_contains($plural, 'event') ? 'end'   : 'end_date'));

        // Start
        if (!empty($row[$inStart]) && is_string($row[$inStart])) {
            $s = self::splitIso($row[$inStart], $tz);
            if ($s['date'] !== null) $row['start_date'] = $s['date']; // overwrite to date-only for listing/forms
            if ($s['time'] !== null) $row['start_hour'] = $s['time']; // <-- THIS is what your Blade needs
        }

        // End
        if (!empty($row[$inEnd]) && is_string($row[$inEnd])) {
            $e = self::splitIso($row[$inEnd], $tz);
            if ($e['date'] !== null) $row['end_date'] = $e['date'];
            if ($e['time'] !== null) $row['end_hour'] = $e['time'];
        }

        return $row;
    }

    /**
     * Normalize a list of rows.
     */
    public static function normalizeTemporalCollection(array $rows, array $props, ?string $tz = null): array
    {
        foreach ($rows as $i => $row) {
            if (is_array($row)) {
                $rows[$i] = self::normalizeTemporalRow($row, $props, $tz);
            }
        }
        return $rows;
    }
}
