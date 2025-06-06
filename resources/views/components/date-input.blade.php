<div class="form-group">
    @if($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif
    <input type="date"
           id="{{ $name }}"
           name="{{ $name }}"
           class="form-control @if($error) is-invalid @endif"
           placeholder="{{ $placeholder }}"
           value="{{ old($name, $value) }}"
           @if($min) min="{{ $min }}" @endif
           onclick="this.showPicker()">

    @if($error)
        <div class="invalid-feedback">
            {{ $error }}
        </div>
    @endif
</div>
