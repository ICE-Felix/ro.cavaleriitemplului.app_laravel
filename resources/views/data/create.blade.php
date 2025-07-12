@php
    $label = $props['name']['label_singular'] ?? $props['name']['singular'];
@endphp
@section('page_title', "Create {$label}")
<x-app-layout :breadcrumbs="$breadcrumbs ?? []">
    <form method="POST" action="{{ route($props['name']['plural'] . '.store') }}" enctype="multipart/form-data">
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
                <h3>New {{ucfirst($label)}}</h3>
                <div class="grid  mt-5">
                    <div class="flex flex-col gap-y-5">
                        @foreach($props['schema'] as $key => $field)
                            @if(isset($field['type']) && (!isset($field['readonly']) || $field['readonly'] !== true))
                                @switch($field['type'])
                                    @case('text')
                                        <x-input
                                            name="{{$field['id'] ?? $key}}"
                                            label="{{$field['label' ?? ucfirst($key)]}}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{!! html_entity_decode($field['value'] ?? '') !!}"
                                        />
                                        @break
                                    @case('numeric')
                                        <x-numeric-input
                                            name="{{$field['id'] ?? $key}}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            label="{{$field['label'] ?? $key}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{{$field['value'] ?? null ?? 0}}"
                                        />
                                        @break
                                    @case('trix')
                                        <x-trix-editor
                                            name="{{$field['id'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            rows="{{$field['rows'] ?? null}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{!! html_entity_decode($field['value'] ?? '') !!}"
                                        />
                                        @break
                                    @case('textarea')
                                        <x-textarea
                                            name="{{$field['id'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            rows="{{$field['rows'] ?? null}}"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            value="{!! html_entity_decode($field['value'] ?? '') !!}"
                                        />
                                        @break
                                        @case('json')
                                        <x-textarea
                                                type="json"
                                                name="{{$field['id'] ?? $key}}"
                                                label="{{$field['label'] ?? $key}}"
                                                rows="{{$field['rows']}}"
                                                :error="$errors->first($field['id'] ?? $key)"
                                        />
                                        @break
                                        @case('select')
                                        @php
                                            $values = [];
                                            foreach ($data[$key] as $elem) {
                                                $values[$elem['value']] = ucfirst($elem['name']);
                                            }
                                            $label = $field['label'] ?? ucfirst($key);
                                        @endphp
                                        <x-select
                                            name="{{$field['key'] ?? $key}}"
                                            label={{$label}}
                                            :options="$values"
                                            :error="$errors->first($field['id'] ?? $key)"
                                            selected="{{$field['value'] ?? null}}"
                                        />
                                        @break
                                        @case('image')
                                        <x-file-browser
                                            name="{{$field['key'] ?? $key}}"
                                            label="{{$field['label']}}"
                                            :isImage="true"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :success="session($field['key'] ?? $key)"
                                        />
                                        @break
                                        @case('location')
                                        <x-location-picker
                                            name="location"
                                            label="Location"
                                            :error="$errors->first('location')"
                                            :success="session('location')"
                                            :latitude="old('location_latitude', 44.4268)"
                                            :longitude="old('location_longitude', 26.1025)"
                                        />
                                        @break
                                        @case('date')
                                        <x-date-input
                                                name="{{ $field['id'] ?? $key }}"
                                                label="{{ $field['label'] ?? ucfirst($key) }}"
                                                placeholder="{{ $field['placeholder'] ?? null }}"
                                                :error="$errors->first($field['id'] ?? $key)"
                                                value="{{ $field['value'] ?? null }}"
                                                min="2024-01-01"
                                                max="2024-12-31"
                                        />
                                        @break
                                        @case('checkbox')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $values = [];
                                            
                                            // Check for static options in field configuration
                                            if(isset($field['options'])) {
                                                foreach ($field['options'] as $option) {
                                                    $values[$option['value']] = ucfirst($option['name']);
                                                }
                                            }
                                            // Fallback to dynamic data if no static options
                                            elseif(isset($data[$key])) {
                                                foreach ($data[$key] as $elem) {
                                                    $values[$elem['value']] = ucfirst($elem['name']);
                                                }
                                            }
                                        @endphp
                                        <x-checkbox
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :checked="old($field['key'] ?? $key, $field['value'] ?? false)"
                                            :default="$field['value'] ?? false"
                                            :options="$values"
                                            :text="$field['text'] ?? null"
                                        />
                                        @break
                                        @case('hierarchical_checkbox')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $values = [];
                                            
                                            // Get top-level categories
                                            if(isset($data[$key])) {
                                                foreach ($data[$key] as $elem) {
                                                    $values[] = [
                                                        'value' => $elem['value'],
                                                        'name' => ucfirst($elem['name'])
                                                    ];
                                                }
                                            }
                                        @endphp
                                        <x-hierarchical-checkbox
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :options="$values"
                                            :value="old($field['key'] ?? $key, $field['value'] ?? [])"
                                            :subcategorySource="$field['subcategory_source'] ?? null"
                                            componentName="create_{{ $field['key'] ?? $key }}"
                                        />
                                        @break
                                        @case('switch')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                        @endphp
                                        <x-switch
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :value="old($field['key'] ?? $key, $field['value'] ?? false)"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :required="$field['required'] ?? false"
                                            :onLabel="$field['on_label'] ?? 'Active'"
                                            :offLabel="$field['off_label'] ?? 'Inactive'"
                                        />
                                        @break
                                        @case('schedule')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                        @endphp
                                        <x-schedule
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :value="old($field['key'] ?? $key, $field['value'] ?? null)"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :required="$field['required'] ?? false"
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
            <script>

            </script>
        </div>
    </form>
</x-app-layout>
