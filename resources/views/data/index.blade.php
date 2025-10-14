@php
    use App\Services\Supabase\SupabaseService;
    $label = $props['name']['label_singular'] ?? $props['name']['singular'];
@endphp
@section('page_title', "Listing {$label}")

@section('header')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css" />
    <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
@endsection

@section('footer')
    <script>
        $(document).ready(function () {
            var table = $('.table_striped').DataTable({
            });

            // Apply column search
            $('.table_striped_filters input').on('keyup change', function () {
                var colIndex = $(this).parent().index();
                console.log(colIndex);
                table.column(colIndex).search(this.value).draw();
            });
        });
    </script>
@endsection

<x-app-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="flex flex-col gap-y-5">
        <!-- Check for a session success message -->
        @if(session('success'))
            <div class="alert alert_success">
                <strong class="uppercase">
                    <bdi>Success!</bdi>
                </strong>
                {{ session('success') }}
                <button class="dismiss la la-times" data-dismiss="alert"></button>
            </div>
        @endif

        <!-- Check for errors -->
        @if($errors->any())
            @foreach($errors->all() as $error)
                <div class="alert alert_danger">
                    <strong class="uppercase">
                        <bdi>Danger!</bdi>
                    </strong>
                    {{$error}}
                    <button class="dismiss la la-times" data-dismiss="alert"></button>
                </div>
            @endforeach
        @endif
        <div class="card p-5">
            <div class="grid sm:grid-cols-2 gap-5">
                <h3>{{ucfirst($label)}}</h3>
                @if(SupabaseService::user_have_permission($props['name']['plural'], 'i'))
                    <div class="flex flex-wrap gap-2 ltr:ml-auto rtl:mr-auto">
                        <a href="{{ route($props['name']['plural'] . '.create') }}"
                           class="btn btn_primary uppercase">New {{$label}}
                        </a>
                    </div>
                @endif
            </div>
            <div class="grid  mt-5">
                <div class="flex flex-col gap-y-5">
                    @if(!empty($data))
                        <h3>Filters</h3>
                        <table class="table_striped_filters">
                            <tr>
                                @foreach($props['schema'] as $key => $field)
                                    @if(!isset($field['visible']) || $field['visible'] !== false)
                                        @if($key === 'shop') @continue @endif
                                        <td>
                                            <label class="label block mb-2" for="{{$key}}">{{$field['label'] ?? ucfirst($key)}}</label>
                                            <input type="text" class="form-control" placeholder="{{$field['label'] ?? ucfirst($key)}}" name="{{$key}}">
                                        </td>
                                    @endif
                                @endforeach

                                @if(SupabaseService::user_have_permission($props['name']['plural'], ['u', 'd']))
                                    <td></td> <!-- Empty cell for the Actions column -->
                                @endif
                            </tr>
                        </table>

                        <table class="table table_striped w-full mt-3">
                            <thead>
                            <tr>
                                @foreach($props['schema'] as $key => $field)
                                    @if(!isset($field['visible']) || $field['visible'] !== false)
                                        @if($key === 'shop') @continue @endif
                                        <th class="">{{$field['label'] ?? ucfirst($key)}}</th>
                                    @endif
                                @endforeach

                                @if(SupabaseService::user_have_permission($props['name']['plural'], ['u', 'd']))
                                    <th class="ltr:text-left rtl:text-right uppercase">Actions</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $elem)
                                <tr>
                                    @foreach($props['schema'] as $key => $field)
                                        @if(!isset($field['visible']) || $field['visible'] !== false)
                                            @if($key === 'shop') @continue @endif
                                            @isset($field['type'])
                                                <td>
                                                    @switch($field['type'] )
                                                        @case('button')
                                                            @if(isset($elem[$key]))
                                                                <a href="{{$elem[$key]}}" target="_blank">
                                                                    <button class="btn btn_primary uppercase">Contract</button>
                                                                </a>
                                                            @else
                                                                Not available
                                                            @endif
                                                            @break
                                                        @case('image')
                                                            @isset($elem[$key])
                                                                <img style="height: 50px"
                                                                     src="{{$elem[$key]}}"
                                                                     alt="{{$elem[$key] ?? ''}}">
                                                            @else
                                                                <span>N/A</span>
                                                            @endisset
                                                            @break
                                                        @case('file-browser')
                                                            @isset($elem[$key])
                                                                @if($elem[$key])
                                                                    <a href="{{$elem[$key]}}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-download"></i> Download
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">No file</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">No file</span>
                                                            @endisset
                                                            @break
                                                        @case('boolean')
                                                            {{$elem[$key] ? "TRUE" : "FALSE"}}
                                                            @break
                                                        @case('option')
                                                            {{ucfirst($elem[$key])}}
                                                            @break
                                                        @case('select')
                                                            @isset($field["cast"])
                                                                @switch($field["cast"])
                                                                    @case('bool')
                                                                        {{isset($elem[$key]) ? ($elem[$key] ? "True": "False"): "False"}}
                                                                        @break
                                                                    @default
                                                                        {{ucfirst($elem[$key] ?? '')}}
                                                                        @break
                                                                @endswitch
                                                            @else
                                                                @if(isset($props['schema'][$key]['data']['type']) && $props['schema'][$key]['data']['type'] === 'static')
                                                                    @php
                                                                        $displayName = '';
                                                                        if(isset($elem[$key]) && isset($props['schema'][$key]['data']['options'])) {
                                                                            foreach($props['schema'][$key]['data']['options'] as $option) {
                                                                                if($option['value'] == $elem[$key]) {
                                                                                    $displayName = $option['name'];
                                                                                    break;
                                                                                }
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    {{ $displayName ?: ($elem[$key] ?? '') }}
                                                                @elseif(isset($props['schema'][$key]['data']['type']) && $props['schema'][$key]['data']['type'] === 'class')
                                                                    {{ucfirst($elem[$key] ?? '')}}
                                                                @else
                                                                    @if(isset($elem[$key]))
                                                                        @if(isset($props['schema'][$key]['data']['source'][$elem[$key]]['color']))
                                                                            <div class="badge {{$props['schema'][$key]['data']['source'][$elem[$key]]['color']}} uppercase">{{$props['schema'][$key]['data']['source'][$elem[$key]]['name'] ?? null}}</div>
                                                                        @else
                                                                            {{$props['schema'][$key]['data']['source'][$elem[$key]]['name'] ?? null}}
                                                                        @endif
                                                                    @endif
                                                                @endif

                                                            @endisset
                                                            @break
                                                        @case('email')
                                                            {{$elem[$key] ?? ''}}
                                                            @break
                                                        @case('date')
                                                            @php
                                                                $raw = data_get($elem, $field['source'] ?? $key);
                                                            @endphp

                                                            @if($raw)
                                                                @if(preg_match('/^\d{4}-\d{2}-\d{2}T/', $raw))
                                                                    {{ Carbon\Carbon::parse($raw)->timezone('Europe/Bucharest')->format('d-m-Y') }}
                                                                @else
                                                                    {{ Carbon\Carbon::createFromFormat('Y-m-d', $raw)->format('d-m-Y') }}
                                                                @endif
                                                            @endif
                                                            @break
                                                        @case('time')
                                                            @php
                                                                $timeValue = $elem[$key] ?? '';
                                                                if ($timeValue && $timeValue !== '00:00:00') {
                                                                    $formattedTime = date('H:i', strtotime($timeValue));
                                                                } else {
                                                                    $formattedTime = '--:--';
                                                                }
                                                            @endphp
                                                            {{ $formattedTime }}
                                                            @break
                                                        @case('gallery')
                                                            @php
                                                                $galleryValue = $elem[$key] ?? null;
                                                                $galleryData = null;

                                                                if (is_string($galleryValue)) {
                                                                    $decoded = json_decode($galleryValue, true);
                                                                    $galleryData = is_array($decoded) ? $decoded : null;
                                                                } elseif (is_array($galleryValue)) {
                                                                    $galleryData = $galleryValue;
                                                                }

                                                                $extractSrc = function ($item) {
                                                                    if (is_array($item)) {
                                                                        if (!empty($item['url']))  return (string)$item['url'];
                                                                        if (!empty($item['src']))  return (string)$item['src'];
                                                                        if (!empty($item['path'])) return (string)$item['path'];
                                                                    }
                                                                    if (is_string($item)) {
                                                                        $s = trim($item);
                                                                        if ($s === '') return null;
                                                                        if (str_starts_with($s, 'data:image/')) return $s;
                                                                        if (str_starts_with($s, 'http://') || str_starts_with($s, 'https://')) return $s;
                                                                        return $s;
                                                                    }
                                                                    return null;
                                                                };

                                                                $firstImage = null;
                                                                $imageCount = 0;

                                                                if (is_array($galleryData) && count($galleryData) > 0) {
                                                                    $imageCount = count($galleryData);
                                                                    $firstImage = $extractSrc($galleryData[0]);
                                                                    if (!$firstImage) {
                                                                        foreach ($galleryData as $it) {
                                                                            $firstImage = $extractSrc($it);
                                                                            if ($firstImage) break;
                                                                        }
                                                                    }
                                                                }
                                                            @endphp

                                                            @if($imageCount > 0)
                                                                @if($firstImage)
                                                                    <div class="flex items-center gap-2">
                                                                        <img src="{{ e($firstImage) }}"
                                                                             alt="Gallery preview"
                                                                             style="height: 40px; width: 40px; object-fit: cover; border-radius: 4px;">
                                                                        <span class="text-sm text-gray-600">
                                                                            {{ $imageCount }} image{{ $imageCount > 1 ? 's' : '' }}
                                                                        </span>
                                                                    </div>
                                                                @else
                                                                    <span class="text-gray-600">
                                                                        {{ $imageCount }} image{{ $imageCount > 1 ? 's' : '' }}
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span class="text-gray-500">No images</span>
                                                            @endif
                                                            @break
                                                        @case('ad_hoc_builder')
                                                            @php
                                                                $adHocValue = $elem[$key] ?? '';
                                                                $adHocData = null;

                                                                if (is_string($adHocValue)) {
                                                                    $adHocData = json_decode($adHocValue, true);
                                                                } elseif (is_array($adHocValue)) {
                                                                    $adHocData = $adHocValue;
                                                                }

                                                                if ($adHocData && is_array($adHocData) && count($adHocData) > 0) {
                                                                    $dateCount = count($adHocData);
                                                                    $dates = array_map(fn($item) => $item['date'] ?? '', $adHocData);
                                                                    $dates = array_filter($dates);

                                                                    if (count($dates) > 0) {
                                                                        $preview = count($dates) <= 3
                                                                            ? implode(', ', $dates)
                                                                            : implode(', ', array_slice($dates, 0, 2)) . ', +' . (count($dates) - 2) . ' more';

                                                                        echo '<div class="text-sm">';
                                                                        echo '<strong>' . $dateCount . ' date' . ($dateCount > 1 ? 's' : '') . ':</strong><br>';
                                                                        echo '<span class="text-gray-600">' . $preview . '</span>';
                                                                        echo '</div>';
                                                                    } else {
                                                                        echo '<span class="text-gray-500">No dates</span>';
                                                                    }
                                                                } else {
                                                                    echo '<span class="text-gray-500">No dates selected</span>';
                                                                }
                                                            @endphp
                                                            @break
                                                        @case('periods_builder')
                                                            @php
                                                                $periodsValue = $elem[$key] ?? '';
                                                                $periodsData = null;

                                                                if (is_string($periodsValue)) {
                                                                    $periodsData = json_decode($periodsValue, true);
                                                                } elseif (is_array($periodsValue)) {
                                                                    $periodsData = $periodsValue;
                                                                }

                                                                if ($periodsData && is_array($periodsData)) {
                                                                    $frequency = $periodsData['frequency'] ?? 'daily';
                                                                    $startsOn = $periodsData['starts_on'] ?? null;
                                                                    $endsOn = $periodsData['ends_on'] ?? null;
                                                                    $windows = $periodsData['windows'] ?? [];
                                                                    $weeklyDays = $periodsData['weekly_days'] ?? [];
                                                                    $monthlyDays = $periodsData['monthly_days'] ?? [];

                                                                    $displayParts = [];

                                                                    // Frequency
                                                                    $displayParts[] = '<strong>' . ucfirst($frequency) . '</strong>';

                                                                    // Date range
                                                                    if ($startsOn && $endsOn) {
                                                                        $displayParts[] = Carbon\Carbon::parse($startsOn)->format('M d') . ' - ' . Carbon\Carbon::parse($endsOn)->format('M d, Y');
                                                                    }

                                                                    // Weekly days
                                                                    if ($frequency === 'weekly' && !empty($weeklyDays)) {
                                                                        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                                                        $selectedDays = array_map(fn($d) => $dayNames[$d] ?? $d, $weeklyDays);
                                                                        $displayParts[] = implode(', ', $selectedDays);
                                                                    }

                                                                    // Monthly days
                                                                    if ($frequency === 'monthly' && !empty($monthlyDays)) {
                                                                        $displayParts[] = 'Days: ' . implode(', ', array_map(fn($d) => $d + 1, $monthlyDays));
                                                                    }

                                                                    // Windows
                                                                    if (!empty($windows)) {
                                                                        $windowCount = count($windows);
                                                                        $displayParts[] = $windowCount . ' time window' . ($windowCount > 1 ? 's' : '');
                                                                    }

                                                                    echo '<div class="text-sm">' . implode('<br>', $displayParts) . '</div>';
                                                                } else {
                                                                    echo '<span class="text-gray-500">No schedule set</span>';
                                                                }
                                                            @endphp
                                                            @break
                                                        @case('tickets')
                                                            @php
                                                                $ticketsValue = $elem[$key] ?? '';
                                                                $ticketsData = null;

                                                                if (is_string($ticketsValue)) {
                                                                    $ticketsData = json_decode($ticketsValue, true);
                                                                } elseif (is_array($ticketsValue)) {
                                                                    $ticketsData = $ticketsValue;
                                                                }

                                                                if ($ticketsData && is_array($ticketsData) && count($ticketsData) > 0) {
                                                                    $ticketCount = count($ticketsData);
                                                                    $ticketTypes = array_map(fn($t) => $t['type'] ?? $t['name'] ?? 'Ticket', $ticketsData);

                                                                    $preview = count($ticketTypes) <= 3
                                                                        ? implode(', ', $ticketTypes)
                                                                        : implode(', ', array_slice($ticketTypes, 0, 2)) . ', +' . (count($ticketTypes) - 2) . ' more';

                                                                    echo '<div class="text-sm">';
                                                                    echo '<strong>' . $ticketCount . ' ticket type' . ($ticketCount > 1 ? 's' : '') . '</strong><br>';
                                                                    echo '<span class="text-gray-600">' . $preview . '</span>';
                                                                    echo '</div>';
                                                                } else {
                                                                    echo '<span class="text-gray-500">No tickets</span>';
                                                                }
                                                            @endphp
                                                            @break
                                                        @case('info_fields')
                                                            @php
                                                                $infoValue = $elem[$key] ?? '';
                                                                $infoData = null;

                                                                if (is_string($infoValue)) {
                                                                    $infoData = json_decode($infoValue, true);
                                                                } elseif (is_array($infoValue)) {
                                                                    $infoData = $infoValue;
                                                                }

                                                                $displayText = '';
                                                                if ($infoData && is_array($infoData)) {
                                                                    $titles = array_map(fn($item) => $item['title'] ?? '', $infoData);
                                                                    $displayText = implode(', ', array_filter($titles));
                                                                }

                                                                if (empty($displayText)) {
                                                                    $displayText = '<span class="text-gray-500">No fields</span>';
                                                                }
                                                            @endphp
                                                            {!! $displayText !!}
                                                            @break
                                                        @case('trix')
                                                            {!! \Illuminate\Support\Str::limit(strip_tags(html_entity_decode($elem[$key] ?? '')), 100) !!}
                                                            @break
                                                        @case('switch')
                                                            @php
                                                                $value = $elem[$key] ?? '';
                                                                if (is_array($value)) {
                                                                    $displayValues = [];
                                                                    foreach ($value as $val) {
                                                                        $displayValues[] = $val ? ($field['on_label'] ?? 'On') : ($field['off_label'] ?? 'Off');
                                                                    }
                                                                    $displayText = implode(', ', $displayValues);
                                                                } else {
                                                                    $displayText = $value ? ($field['on_label'] ?? 'On') : ($field['off_label'] ?? 'Off');
                                                                }
                                                            @endphp
                                                            {{ $displayText }}
                                                            @break
                                                        @case('schedule')
                                                            @php
                                                                $scheduleValue = $elem[$key] ?? '';
                                                                $scheduleData = null;

                                                                // Handle both string and array inputs
                                                                if (is_string($scheduleValue)) {
                                                                    $scheduleData = json_decode($scheduleValue, true);
                                                                } elseif (is_array($scheduleValue)) {
                                                                    $scheduleData = $scheduleValue;
                                                                }

                                                                $displayText = '';

                                                                if ($scheduleData) {
                                                                    $dayLabels = [
                                                                        'monday' => 'Mon',
                                                                        'tuesday' => 'Tue',
                                                                        'wednesday' => 'Wed',
                                                                        'thursday' => 'Thu',
                                                                        'friday' => 'Fri',
                                                                        'saturday' => 'Sat',
                                                                        'sunday' => 'Sun'
                                                                    ];

                                                                    $openDays = [];
                                                                    foreach ($dayLabels as $dayKey => $dayLabel) {
                                                                        if (isset($scheduleData[$dayKey]) && $scheduleData[$dayKey]['enabled']) {
                                                                            $dayData = $scheduleData[$dayKey];
                                                                            $openTime = date('g:i A', strtotime($dayData['open']));
                                                                            $closeTime = date('g:i A', strtotime($dayData['close']));
                                                                            $openDays[] = "{$dayLabel}: {$openTime}-{$closeTime}";
                                                                        }
                                                                    }

                                                                    if (empty($openDays)) {
                                                                        $displayText = '<span class="text-red-600">Closed all days</span>';
                                                                    } else {
                                                                        $displayText = '<div class="text-sm">' . implode('<br>', $openDays) . '</div>';
                                                                    }
                                                                } else {
                                                                    $displayText = '<span class="text-gray-500">No schedule set</span>';
                                                                }
                                                            @endphp
                                                            {!! $displayText !!}
                                                            @break
                                                        @default
                                                            @php
                                                                $value = $elem[$key] ?? '';
                                                                if (is_array($value)) {
                                                                    $value = implode(', ', $value);
                                                                }
                                                            @endphp
                                                            {!! html_entity_decode($value) !!}
                                                            @break
                                                    @endswitch
                                                </td>
                                            @else
                                                <td>{{$elem[$key] ?? ''}}</td>
                                            @endisset
                                        @endif
                                    @endforeach
                                    @php
                                        $canEdit = SupabaseService::user_have_permission($props['name']['plural'], 'u');
                                        $canDelete = SupabaseService::user_have_permission($props['name']['plural'], 'd');
                                    @endphp

                                    @if($canEdit || $canDelete)
                                        <td>
                                            @if($canEdit && isset($elem['id']))
                                                <a href="{{ route($props['name']['plural'] . '.edit', [ $elem['id']]) }}"
                                                   class="btn btn_secondary uppercase">Edit</a>
                                            @endif
                                            @if($canDelete && isset($elem['id']))
                                                <form
                                                        action="{{ route($props['name']['plural'] . '.destroy', [$elem['id']]) }}"
                                                        method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn_danger uppercase"
                                                            onclick="confirmDelete(event)">Delete
                                                    </button>
                                                </form>
                                            @endif
                                            @if(!isset($elem['id']))
                                                <span class="text-gray-500">No ID available</span>
                                            @endif
                                        </td>
                                    @endif

                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>There is no data here...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>