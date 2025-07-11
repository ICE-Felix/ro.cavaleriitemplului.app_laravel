@props(['name', 'label' => null, 'error' => null, 'success' => null, 'checked' => false, 'disabled' => false, 'options' => [], 'default' => false, 'text' => null])

<div>
    <label class="label block mb-2" for="{{ $name }}">{{ $label ?? ucfirst($name) }}</label>
    @if(empty($options))
        <!-- Single checkbox -->
        @php
            $isChecked = $checked !== false ? $checked : $default;
        @endphp
        <div class="flex items-center">
            <input type="checkbox" 
                   id="{{ $name }}" 
                   name="{{ $name }}" 
                   value="1"
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded {{ $error ? 'border-red-500' : '' }}"
                   {{ $isChecked ? 'checked' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   {{ $attributes }}>
            <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                {{ $text ?? ($isChecked ? 'Yes' : 'No') }}
            </span>
        </div>
    @else
        <!-- Multiple checkboxes -->
        @php
            $selectedValues = $checked !== false ? $checked : $default;
            if (!is_array($selectedValues)) {
                $selectedValues = $selectedValues ? [$selectedValues] : [];
            }
        @endphp
        <div class="space-y-2">
            @foreach($options as $value => $text)
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="{{ $name }}_{{ $value }}" 
                           name="{{ $name }}[]" 
                           value="{{ $value }}"
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded {{ $error ? 'border-red-500' : '' }}"
                           {{ in_array($value, $selectedValues) || $selectedValues == $value ? 'checked' : '' }}
                           {{ $disabled ? 'disabled' : '' }}
                           {{ $attributes }}>
                    <label for="{{ $name }}_{{ $value }}" class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $text }}
                    </label>
                </div>
            @endforeach
        </div>
    @endif
    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div> 