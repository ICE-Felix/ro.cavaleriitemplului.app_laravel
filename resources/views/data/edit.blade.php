@php
    $label = $props['name']['label_singular'] ?? $props['name']['singular'];
@endphp
@section('page_title', "Edit {$label}")
<x-app-layout :breadcrumbs="$breadcrumbs ?? []">
    <form method="POST" action="{{ route($props['name']['plural'] . '.update', [$result['id']]) }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf {{-- CSRF Token field --}}
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
                <h3>Edit {{ucfirst($label)}}</h3>
                <div class="grid  mt-5">
                    <div class="flex flex-col gap-y-5">
                        @foreach($props['schema'] as $key => $field)
                            @if(isset($field['type']) && (!isset($field['readonly']) || $field['readonly'] !== true))
                                @switch($field['type'])
                                    @case('text')
                                        <x-input
                                            name="{{$field['id'] ?? $key}}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{{$result[$field['id'] ?? $key] ?? ''}}"
                                        />
                                        @break
                                    @case('json')
                                        <x-textarea
                                            type="json"
                                            name="{{$field['id'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            rows="{{$field['rows']}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{{json_encode($result[$field['id'] ?? $key])}}"
                                        />
                                        @break
                                    @case('numeric')
                                        <x-numeric-input
                                            name="{{$field['id'] ?? $key}}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            label="{{$field['label'] ?? $key}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{{$result[$field['id'] ?? $key]}}"
                                        />
                                        @break
                                    @case('textarea')
                                        <x-textarea
                                            name="{{$field['id'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            rows="{{$field['rows'] ?? null}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{{$result[$field['id'] ?? $key]}}"
                                        />
                                        @break
                                    @case('select')
                                        @php
                                            $value = '';
                                            $values = [];
                                            foreach ($data[$key] as $elem) {
//                                                @dd($result[$field['id'] ?? $key]);
                                                $values[$elem['value']] = ucfirst($elem['name']);
                                                if(isset($field['cast']) && $field['cast'] === "bool") {
                                                    $value = $result[$field['id'] ?? $key] === true ? "1": "0";
                                                } else {
                                                    $value = $result[$field['id'] ?? $key] ?? '';
                                                }
                                            }
                                            $label = $field['label'] ?? ucfirst($key);
                                        @endphp
                                        <x-select
                                            name="{{$field['key'] ?? $key}}"
                                            label={{$label}}
                                            :options="$values"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            :selected="$value"
                                        />
                                        @break
                                        @case('image')
                                        <x-file-browser
                                            name="{{$field['key'] ?? $key}}"
                                            label="{{$field['label']}}"
                                            :isImage="true"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :success="session($field['key'] ?? $key)"
                                            preview="{{$result[$field['id'] ?? $key] ? env('SUPABASE_URL') . '/storage/v1/object/public/images/' . $result[$field['id'] ?? $key] : null}}"
                                            value="{{$result[$field['id'] ?? $key]}}"
                                        />
                                        @break
                                        @case('date')
                                        <x-date-input
                                                name="{{ $field['id'] ?? $key }}"
                                                label="{{ $field['label'] ?? ucfirst($key) }}"
                                                placeholder="{{ $field['placeholder'] ?? null }}"
                                                :error="$errors->first($field['id'] ?? $key)"
                                                value="{{ $result[$key] ?? null }}"
                                                min="2024-01-01"
                                                max="2024-12-31"
                                        />
                                        @break
                                @endswitch
                            @endif
                        @endforeach
                        <div class="flex flex-wrap gap-2 ltr:ml-auto rtl:mr-auto">
                            <button type="submit" class="btn btn_primary uppercase">Save</button>
                            <a href="{{ route($props['name']['plural'] . '.store') }}"
                               class="btn btn_secondary uppercase">Cancel</a> {{-- Adjust as necessary --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
