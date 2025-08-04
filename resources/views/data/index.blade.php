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
                                                            {{ parseTemplate($field['format'] ?? $key, $elem) }}
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
