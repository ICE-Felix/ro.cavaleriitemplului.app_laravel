@props([
    'name' => 'attribute_ids',
    'label' => 'Attributes',
    'data' => [],
    'value' => [],
    'error' => null,
    'required' => false
])

@php
    // Normalize value to array of strings
    $selectedValues = is_string($value) ? json_decode($value, true) ?: [] : (array)$value;
    $selectedValues = array_map('strval', $selectedValues);

    // Group data by type
    $groupedData = [];
    foreach ($data as $item) {
        if (!is_array($item)) continue;

        // Extract type - could be in 'type' field or parse from 'name'
        $type = null;
        $displayValue = null;
        $id = (string)($item['id'] ?? $item['value'] ?? '');

        if (isset($item['type'])) {
            // Direct type field from DB
            $type = $item['type'];
            $displayValue = $item['display_value'] ?? $item['value'] ?? $item['name'] ?? 'Unknown';
        } elseif (isset($item['name']) && strpos($item['name'], ' - ') !== false) {
            // Parse from "type - value" format
            [$type, $displayValue] = explode(' - ', $item['name'], 2);
        } else {
            $type = 'Other';
            $displayValue = $item['name'] ?? $item['value'] ?? 'Unknown';
        }

        $type = ucfirst(trim($type));

        if (!isset($groupedData[$type])) {
            $groupedData[$type] = [];
        }

        $groupedData[$type][] = [
            'id' => $id,
            'name' => trim($displayValue),
        ];
    }

    // Sort types alphabetically
    ksort($groupedData);
@endphp

<div class="space-y-3">
    <div class="flex items-center justify-between mb-2">
        <label class="label block font-medium text-gray-800">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>

        @if(count($selectedValues) > 0)
            <span class="text-xs text-gray-600">
                <strong>{{ count($selectedValues) }}</strong> selected
            </span>
        @endif
    </div>

    @if($error)
        <div class="text-red-500 text-sm mb-2">{{ $error }}</div>
    @endif

    @if(empty($groupedData))
        <p class="text-gray-500 text-sm">No attributes available</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 border border-gray-200 rounded-lg p-4 bg-white max-h-96 overflow-y-auto">
            @foreach($groupedData as $type => $items)
                <div class="space-y-2">
                    <h4 class="font-semibold text-gray-700 text-sm uppercase tracking-wide border-b border-gray-300 pb-2 sticky top-0 bg-white">
                        {{ $type }}
                        <span class="text-xs font-normal text-gray-500">({{ count($items) }})</span>
                    </h4>

                    <div class="space-y-2 pl-2">
                        @foreach($items as $item)
                            <div class="flex items-start">
                                <input
                                        type="checkbox"
                                        id="{{ $name }}_{{ $item['id'] }}"
                                        name="{{ $name }}[]"
                                        value="{{ $item['id'] }}"
                                        class="h-4 w-4 mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                                        {{ in_array($item['id'], $selectedValues) ? 'checked' : '' }}
                                >
                                <label
                                        for="{{ $name }}_{{ $item['id'] }}"
                                        class="ml-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900 select-none leading-tight"
                                >
                                    {{ $item['name'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>