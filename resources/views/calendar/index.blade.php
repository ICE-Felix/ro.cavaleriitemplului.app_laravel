@php
    $pageTitle = 'Calendar';
@endphp

@section('page_title', $pageTitle)

@section('header')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
@endsection

@section('footer')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const events = @json($events ?? []);

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                timeZone: 'Europe/Bucharest',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                navLinks: true,
                nowIndicator: true,
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                events: events,
                eventClick: function(info) {

                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                eventMouseEnter: function(arg) {
                    arg.el.style.cursor = 'pointer';
                }
            });

            calendar.render();
        });
    </script>
@endsection

<x-app-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="flex flex-col gap-y-5">
        @if($errors->any())
            @foreach($errors->all() as $error)
                <div class="alert alert_danger">
                    <strong class="uppercase"><bdi>Danger!</bdi></strong>
                    {{ $error }}
                    <button class="dismiss la la-times" data-dismiss="alert"></button>
                </div>
            @endforeach
        @endif

        <div class="card p-5">
            <div class="grid sm:grid-cols-2 gap-5 mb-5">
                <h3>{{ $pageTitle }}</h3>
            </div>

            <div id="calendar"></div>
        </div>
    </div>
</x-app-layout>
