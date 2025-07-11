<div>
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" type="hidden" name="{{ $name }}" value="{{$value}}">
    
    <trix-editor 
        class="form-control trix-content {{ $error ? 'is-invalid' : '' }}" 
        input="{{ $name }}" 
        rows="{{ $rows ?? 5 }}" 
        data-trix-allow-files="false">
    </trix-editor>
    
    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">
            {{ $error ?? $success }}
        </small>
    @endif
</div>


