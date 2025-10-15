<div data-field="{{ $name }}">
    <label class="label block mb-2" for="{{ $name }}">{{ $label ?? ucfirst($name) }}</label>
    <div class="custom-select">
        <select id="{{ $name }}" name="{{ $name }}" class="form-control {{ $error ? 'is-invalid' : '' }}">
            <option value="" selected>Select</option>
            @foreach($options as $value => $text)
                <option value="{{ $value }}" @if(old($name, $selected) == $value || old($name, $selected) == $text) selected @endif>
                    {{ ucfirst($text) }}
                </option>
            @endforeach
        </select>
        <div class="custom-select-icon la la-caret-down"></div>
    </div>
    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div>