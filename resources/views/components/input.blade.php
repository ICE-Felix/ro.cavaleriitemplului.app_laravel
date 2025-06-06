<div>
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" class="form-control {{ $error ? 'is-invalid' : '' }}" placeholder="{{ $placeholder }}" value="{{ old($name, $value) }}">
        @if($error || $success)
            <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
        @endif
</div>
