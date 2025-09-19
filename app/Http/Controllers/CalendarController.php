<?php

namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use App\Support\DateTime as DT;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function index()
    {
        try {
            $raw = $this->supabase->read_edge('events');

            $tz = config('app.timezone', 'Europe/Bucharest');

            $events = array_map(function ($e) use ($tz) {
                $title = $e['title'] ?? 'Untitled';

                $startIso = $e['start'] ?? null;
                $endIso   = $e['end']   ?? null;

                if (!$startIso) {
                    $sd = $e['start_date'] ?? null;
                    $sh = $e['start_hour'] ?? null;
                    if ($sd && $sh) {
                        $startDT = DT::combine($sd, $sh, $tz);
                        $startIso = $startDT ? $startDT->toIso8601String() : null;
                    }
                }
                if (!$endIso) {
                    $ed = $e['end_date'] ?? null;
                    $eh = $e['end_hour'] ?? null;
                    if ($ed && $eh) {
                        $endDT = DT::combine($ed, $eh, $tz);
                        $endIso = $endDT ? $endDT->toIso8601String() : null;
                    }
                }

                $url = isset($e['id']) ? route('events.edit', [$e['id']]) : null;

                return [
                    'id'    => $e['id'] ?? null,
                    'title' => $title,
                    'start' => $startIso,
                    'end'   => $endIso,
                    'url'   => $url,
                    'backgroundColor' => $e['event_type_color'] ?? null,
                ];
            }, is_array($raw) ? $raw : []);

            $events = array_values(array_filter($events, fn($ev) => !empty($ev['start'])));

            return view('calendar.index', [
                'events' => $events,
                'label'  => 'Calendar',
            ]);

        } catch (\Throwable $e) {
            Log::error('Calendar load failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['general' => 'Failed to load calendar.']);
        }
    }
}
