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
                                            value="{!! html_entity_decode($result[$field['id'] ?? $key] ?? '') !!}"
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
                                            value="{{$result[$field['id'] ?? $key] ?? 0}}"
                                        />
                                        @break
                                    @case('ad_hoc_builder')
                                        <x-ad-hoc-builder
                                                name="ad_hoc_windows_json"
                                                :label="$field['label'] ?? 'Pick Dates & Hours'"
                                                :value="old('ad_hoc_windows_json', $result['ad_hoc_windows_json'] ?? '[]')"
                                        />
                                        @break

                                    @case('periods_builder')
                                        <x-periods-builder
                                                name="schedules_json"
                                                :label="$field['label'] ?? 'Recurring Rules'"
                                        />
                                        @break
                                    @case('trix')
                                        <x-trix-editor
                                            name="{{$field['key'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            :rows="$field['rows'] ?? 5"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            value="{!! html_entity_decode(old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? '')) !!}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            :required="$field['required'] ?? false"
                                            :enableAI="true"
                                        />
                                        @break
                                    @case('textarea')
                                        <x-textarea
                                            name="{{$field['key'] ?? $key}}"
                                            label="{{$field['label'] ?? $key}}"
                                            :rows="$field['rows'] ?? 5"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            value="{!! html_entity_decode(old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? '')) !!}"
                                            placeholder="{{$field['placeholder'] ?? null}}"
                                            :required="$field['required'] ?? false"
                                        />
                                        @break
                                    @case('tickets')
                                        @php
                                            // Get ticket types data - already formatted by getSourceData()
                                            $ticketTypesData = $data[$key . '_ticket_types'] ?? [];
                                        @endphp

                                        <x-ticket-builder
                                                name="{{ $field['key'] ?? $key }}"
                                                label="{{ $field['label'] ?? ucfirst($key) }}"
                                                :value="old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? $field['value'] ?? '[]')"
                                                :ticketTypes="$ticketTypesData"
                                        />
                                        @break

                                    @case('select')
                                        @php
                                            $value = '';
                                            $values = [];

                                            // Add safety checks for data structure
                                            if(isset($data[$key]) && is_array($data[$key])) {
                                                foreach ($data[$key] as $elem) {
                                                    // Ensure we have the required keys
                                                    if (is_array($elem) && isset($elem['value']) && isset($elem['name'])) {
                                                        $values[$elem['value']] = ucfirst($elem['name']);
                                                    }
                                                }
                                            }

                                            // Set the current value
                                            if(isset($field['cast']) && $field['cast'] === "bool") {
                                                $value = $result[$field['id'] ?? $key] === true ? "1": "0";
                                            } else {
                                                $value = $result[$field['id'] ?? $key] ?? '';
                                            }

                                            $label = $field['label'] ?? ucfirst($key);

                                            // Check for conditional visibility
                                            $isConditional = isset($field['conditional_visibility']);
                                            $initiallyVisible = $field['visible'] ?? true;
                                            if ($isConditional) {
                                                // For edit mode, check if field should be visible based on current values
                                                $dependsOnField = $field['conditional_visibility']['depends_on'] ?? null;
                                                if ($dependsOnField && isset($result[$dependsOnField])) {
                                                    $dependentValue = $result[$dependsOnField];
                                                    $showWhen = $field['conditional_visibility']['show_when'] ?? [];
                                                    $hideWhen = $field['conditional_visibility']['hide_when'] ?? [];

                                                    if (!empty($showWhen)) {
                                                        $initiallyVisible = in_array($dependentValue, $showWhen);
                                                    } elseif (!empty($hideWhen)) {
                                                        $initiallyVisible = !in_array($dependentValue, $hideWhen);
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="form-group {{ $isConditional ? 'conditional-field' : '' }}"
                                             data-field-name="{{ $field['key'] ?? $key }}"
                                             @if($isConditional)
                                             data-depends-on="{{ $field['conditional_visibility']['depends_on'] }}"
                                             data-show-when="{{ json_encode($field['conditional_visibility']['show_when'] ?? []) }}"
                                             data-hide-when="{{ json_encode($field['conditional_visibility']['hide_when'] ?? []) }}"
                                             data-conditional-filtering="{{ json_encode($field['conditional_filtering'] ?? []) }}"
                                             @endif
                                             style="{{ $initiallyVisible ? '' : 'display: none;' }}">
                                            <x-select
                                                name="{{$field['key'] ?? $key}}"
                                                label={{$label}}
                                                :options="$values"
                                                :error="$errors->first($field['id'] ?? $key)"
                                                :selected="$value"
                                            />
                                        </div>
                                        @break
                                        @case('image')
                                            @isset($result[$field['id'] ?? $key])
                                                <x-file-browser
                                                    name="{{$field['key'] ?? $key}}"
                                                    label="{{$field['label']}}"
                                                    :isImage="true"
                                                    :error="$errors->first($field['key'] ?? $key)"
                                                    :success="session($field['key'] ?? $key)"
                                                    preview="{{$result[$field['id'] ?? $key]}}"
                                                    value="{{$result[$field['id'] ?? $key]}}"
                                                />
                                                @else
                                            <x-file-browser
                                                    name="{{$field['key'] ?? $key}}"
                                                    label="{{$field['label']}}"
                                                    :isImage="true"
                                                    :error="$errors->first($field['key'] ?? $key)"
                                                    :success="session($field['key'] ?? $key)"
                                            />
                                            @endisset
                                        @break
                                        @case('file-browser')
                                            @isset($result[$field['id'] ?? $key])
                                                <x-file-browser
                                                    name="{{$field['key'] ?? $key}}"
                                                    label="{{$field['label']}}"
                                                    :isImage="$field['is_image'] ?? false"
                                                    :error="$errors->first($field['key'] ?? $key)"
                                                    :success="session($field['key'] ?? $key)"
                                                    preview="{{$result[$field['id'] ?? $key]}}"
                                                    value="{{$result[$field['id'] ?? $key]}}"
                                                />
                                                @else
                                            <x-file-browser
                                                    name="{{$field['key'] ?? $key}}"
                                                    label="{{$field['label']}}"
                                                    :isImage="$field['is_image'] ?? false"
                                                    :error="$errors->first($field['key'] ?? $key)"
                                                    :success="session($field['key'] ?? $key)"
                                            />
                                            @endisset
                                        @break

                                        @case('location')
                                        @if($props['name']['plural'] === 'events')
                                            <x-location-picker
                                                    name="location"
                                                    label='Location <span style="color: red;">(only if no venue is selected!)</span>'
                                                    :error="$errors->first('location')"
                                                    :success="session('location')"
                                                    :latitude="$result['location_latitude'] ?? 44.4268"
                                                    :longitude="$result['location_longitude'] ?? 26.1025"
                                            />
                                        @else
                                        <x-location-picker
                                            name="location"
                                            label="Location"
                                            :error="$errors->first('location')"
                                            :success="session('location')"
                                            :latitude="$result['location_latitude'] ?? 44.4268"
                                            :longitude="$result['location_longitude'] ?? 26.1025"
                                        />
                                        @endif
                                        @break

                                        @case('date')
                                        <x-date-input
                                                name="{{ $field['id'] ?? $key }}"
                                                label="{{ $field['label'] ?? ucfirst($key) }}"
                                                placeholder="{{ $field['placeholder'] ?? null }}"
                                                :error="$errors->first($field['id'] ?? $key)"
                                                value="{{ $result[$key] ?? null }}"
                                        />
                                        @break
                                        @case('time')
                                        <x-time-input
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $field['label'] ?? ucfirst($key) }}"
                                            placeholder="{{ $field['placeholder'] ?? 'HH:MM' }}"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            value="{{ old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? $field['value'] ?? '') }}"
                                            :required="$field['required'] ?? false"
                                            min="{{ $field['min'] ?? '00:00' }}"
                                            max="{{ $field['max'] ?? '23:59' }}"
                                            step="{{ $field['step'] ?? '60' }}"
                                        />
                                        @break
                                        @case('checkbox')
                                        @php
                                            $name  = $field['key'] ?? $key;
                                            $label = $field['label'] ?? ucfirst($key);
                                            $values = [];

                                              // Check for static options in field configuration
                                               if (isset($field['options'])) {
                                                    foreach ($field['options'] as $option) {
                                                        $values[(string)$option['value']] = ucfirst($option['name']);
                                                    }
                                               }
                                              // Fallback to dynamic data if no static options
                                              elseif (isset($data[$key]) && is_array($data[$key])) {
                                                   foreach ($data[$key] as $elem) {
                                                        if (is_array($elem) && isset($elem['value'], $elem['name'])) {
                                                            $values[(string)$elem['value']] = ucfirst($elem['name']);
                                                        }
                                                   }
                                               }

                                                $selected = old($name, $result[$name] ?? []);
                                               if (is_string($selected)) {
                                                   $decoded = json_decode($selected, true);
                                                   $selected = is_array($decoded) ? $decoded : [];
                                               }
                                               $selected = array_map('strval', (array)$selected);
                                        @endphp
                                        <x-checkbox
                                                name="{{ $name }}"
                                                label="{{ $label }}"
                                                :error="$errors->first($name)"
                                                :options="$values"
                                                :checked="$selected"
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

                                            // Get current selected values
                                            $currentValues = old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? []);
                                            if (!is_array($currentValues)) {
                                                $currentValues = [];
                                            }
                                        @endphp
                                        <x-hierarchical-checkbox
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :options="$values"
                                            :value="$currentValues"
                                            :subcategorySource="$field['subcategory_source'] ?? null"
                                            componentName="edit_{{ $field['key'] ?? $key }}"
                                        />
                                        @break
                                        @case('three_level_hierarchical_checkbox')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $values = [];

                                            // Get top-level categories (level 1)
                                            if(isset($data[$key]) && is_array($data[$key])) {
                                                foreach ($data[$key] as $elem) {
                                                    // Ensure we have the required keys
                                                    if (is_array($elem) && isset($elem['value']) && isset($elem['name'])) {
                                                        $values[] = [
                                                            'id' => $elem['value'],
                                                            'name' => ucfirst($elem['name'])
                                                        ];
                                                    }
                                                }
                                            }

                                            // Get current selected values
                                            $currentValues = old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? []);
                                            if (!is_array($currentValues)) {
                                                $currentValues = [];
                                            }
                                        @endphp
                                        <x-three-level-hierarchical-checkbox
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :data="$values"
                                            :value="$currentValues"
                                            :subcategorySource="$field['subcategory_source'] ?? []"
                                            :filterSource="$field['filter_source'] ?? []"
                                            :required="$field['required'] ?? false"
                                        />
                                        @break
                                        @case('switch')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $currentValue = old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? $field['value'] ?? false);
                                        @endphp
                                        <x-switch
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :value="$currentValue"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :required="$field['required'] ?? false"
                                            :onLabel="$field['on_label'] ?? 'Active'"
                                            :offLabel="$field['off_label'] ?? 'Inactive'"
                                        />
                                        @break
                                        @case('schedule')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $currentValue = old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? $field['value'] ?? null);
                                        @endphp
                                        <x-schedule
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :value="$currentValue"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :required="$field['required'] ?? false"
                                        />
                                        @break
                                        @case('gallery')
                                        @php
                                            $label = $field['label'] ?? ucfirst($key);
                                            $currentValue = old($field['key'] ?? $key, $result[$field['key'] ?? $key] ?? $field['value'] ?? null);
                                        @endphp
                                        <x-gallery
                                            name="{{ $field['key'] ?? $key }}"
                                            label="{{ $label }}"
                                            :value="$currentValue"
                                            :error="$errors->first($field['key'] ?? $key)"
                                            :required="$field['required'] ?? false"
                                            :minImages="$field['min_images'] ?? 1"
                                            :maxImages="$field['max_images'] ?? 6"
                                            :bucket="$field['bucket'] ?? 'venue-galleries'"
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
            document.addEventListener('DOMContentLoaded', function() {
                // Handle conditional field visibility and filtering
                function handleConditionalFields() {
                    const conditionalFields = document.querySelectorAll('.conditional-field');

                    conditionalFields.forEach(field => {
                        const dependsOn = field.dataset.dependsOn;
                        const showWhen = JSON.parse(field.dataset.showWhen || '[]');
                        const hideWhen = JSON.parse(field.dataset.hideWhen || '[]');
                        const conditionalFiltering = JSON.parse(field.dataset.conditionalFiltering || '{}');

                        if (!dependsOn) return;

                        const dependentField = document.querySelector(`[name="${dependsOn}"]`);
                        if (!dependentField) return;

                        // Function to update field visibility and options
                        function updateField() {
                            const currentValue = parseInt(dependentField.value) || dependentField.value;
                            let shouldShow = false;

                            // Check visibility conditions
                            if (showWhen.length > 0) {
                                shouldShow = showWhen.includes(currentValue);
                            } else if (hideWhen.length > 0) {
                                shouldShow = !hideWhen.includes(currentValue);
                            }

                            // Show/hide field
                            field.style.display = shouldShow ? 'block' : 'none';

                            // Update field options based on conditional filtering
                            if (shouldShow && conditionalFiltering.filters && conditionalFiltering.filters[currentValue]) {
                                updateFieldOptions(field, currentValue, conditionalFiltering);
                            }
                        }

                        // Function to update select options via AJAX
                        async function updateFieldOptions(field, levelValue, conditionalFiltering) {
                            const selectElement = field.querySelector('select');
                            if (!selectElement) return;

                            const filters = conditionalFiltering.filters[levelValue];
                            if (!filters || !Array.isArray(filters)) return;

                            try {
                                // Build filter parameters for API call
                                const filterParams = new URLSearchParams();
                                filters.forEach(filter => {
                                    if (Array.isArray(filter) && filter.length === 3) {
                                        const [field, operator, value] = filter;
                                        filterParams.append(field, `${operator}.${value}`);
                                    }
                                });

                                // Make API call to get filtered options
                                const response = await fetch(`/api/subcategories/venue_categories?${filterParams.toString()}`);
                                if (response.ok) {
                                    const options = await response.json();

                                    // Clear existing options except the first (placeholder)
                                    const firstOption = selectElement.querySelector('option:first-child');
                                    const currentValue = selectElement.value; // Preserve current selection
                                    selectElement.innerHTML = '';
                                    if (firstOption) {
                                        selectElement.appendChild(firstOption);
                                    }

                                    // Add new options
                                    options.forEach(option => {
                                        const optionElement = document.createElement('option');
                                        optionElement.value = option.id;
                                        optionElement.textContent = option.name;
                                        if (option.id === currentValue) {
                                            optionElement.selected = true;
                                        }
                                        selectElement.appendChild(optionElement);
                                    });
                                }
                            } catch (error) {
                                console.error('Error updating field options:', error);
                            }
                        }

                        // Listen for changes on the dependent field
                        dependentField.addEventListener('change', updateField);

                        // Initial update
                        updateField();
                    });
                }

                // Initialize conditional fields
                handleConditionalFields();
            });
            </script>
        </div>
    </form>
</x-app-layout>
