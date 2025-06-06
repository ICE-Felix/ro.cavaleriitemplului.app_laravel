<div>
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" class="form-control {{ $error ? 'is-invalid' : '' }}"
              rows="{{ $rows ?? 5 }}">{{ old($name, $value) }}</textarea>
    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div>

