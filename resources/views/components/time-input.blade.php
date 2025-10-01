@props([
    'name',
    'label' => null,
    'value' => '',
    'placeholder' => 'HH:MM',
    'required' => false,
    'error' => null,
    'success' => null,
    'min' => '00:00',
    'max' => '23:59',
    'step' => '60', // 60 seconds = 1 minute intervals
    'disabled' => false,
    'readonly' => false
])
<div class="form-group mb-3" data-field="{{ $name }}">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <input type="time" id="{{ $name }}" name="{{ $name }}"
           class="form-control @error($name) is-invalid @enderror @if($success) is-valid @endif"
           value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}"
           min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
           @if($required) required @endif @if($disabled) disabled @endif @if($readonly) readonly @endif />
    @if($error)<div class="invalid-feedback">{{ $error }}</div>@endif
    @if($success)<div class="valid-feedback">{{ $success }}</div>@endif
    <div class="form-text text-muted"><small>Format: HH:MM (24-hour format)</small></div>
</div>