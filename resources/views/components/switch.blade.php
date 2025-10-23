@props([
    'name' => '',
    'label' => '',
    'value' => false,
    'checked' => false,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'onLabel' => 'Active',
    'offLabel' => 'Inactive'
])

@php
    // Determine if switch should be checked
    $isChecked = $checked || $value === true || $value === 'true' || $value === '1' || $value === 1;
@endphp

<div class="form-group">
    <label class="form-label ">
        {{ $label }} 
        @if($required)<span class="text-red-500 ">*</span>@endif
    </label>
    
    <div class="switch-container">
        <!-- Hidden input for form submission -->
        <input type="hidden" name="{{ $name }}" value="0">
        
        <!-- Switch toggle -->
        <label class="switch-wrapper {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
            <input 
                type="checkbox" 
                name="{{ $name }}" 
                value="1"
                class="switch-input sr-only"
                {{ $isChecked ? 'checked' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $required ? 'required' : '' }}
            >
            <div class="switch-track {{ $isChecked ? 'switch-track-on' : 'switch-track-off' }}">
                <div class="switch-thumb {{ $isChecked ? 'switch-thumb-on' : 'switch-thumb-off' }}"></div>
            </div>
            <span class="switch-label {{ $isChecked ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                {{ $isChecked ? $onLabel : $offLabel }}
            </span>
        </label>
    </div>
    
    @if($error)
        <div class="error-message text-red-500 text-sm mt-1">{{ $error }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const switchInputs = document.querySelectorAll('.switch-input');
    
    switchInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const track = this.parentElement.querySelector('.switch-track');
            const thumb = this.parentElement.querySelector('.switch-thumb');
            const label = this.parentElement.querySelector('.switch-label');
            
            if (this.checked) {
                // Switch ON state
                track.classList.remove('switch-track-off');
                track.classList.add('switch-track-on');
                thumb.classList.remove('switch-thumb-off');
                thumb.classList.add('switch-thumb-on');
                label.classList.remove('text-gray-500');
                label.classList.add('text-green-600', 'font-medium');
                label.textContent = '{{ $onLabel }}';
            } else {
                // Switch OFF state
                track.classList.remove('switch-track-on');
                track.classList.add('switch-track-off');
                thumb.classList.remove('switch-thumb-on');
                thumb.classList.add('switch-thumb-off');
                label.classList.remove('text-green-600', 'font-medium');
                label.classList.add('text-gray-500');
                label.textContent = '{{ $offLabel }}';
            }
        });
    });
});
</script>

<style>
.switch-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.switch-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.switch-track {
    position: relative;
    width: 3rem;
    height: 1.5rem;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.switch-track-off {
    background-color: #e5e7eb;
    border-color: #d1d5db;
}

.switch-track-on {
    background-color: #10b981;
    border-color: #059669;
}

.switch-thumb {
    position: absolute;
    top: 0.125rem;
    width: 1rem;
    height: 1rem;
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.switch-thumb-off {
    left: 0.125rem;
}

.switch-thumb-on {
    left: 1.625rem;
}

.switch-label {
    font-size: 0.875rem;
    transition: all 0.3s ease;
    min-width: 4rem;
    font-weight: bold;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    color: #374151;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.error-message {
    font-size: 0.875rem;
    color: #ef4444;
    margin-top: 0.25rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Hover effects */
.switch-wrapper:hover:not(.opacity-50) .switch-track {
    transform: scale(1.05);
}

.switch-wrapper:hover:not(.opacity-50) .switch-thumb {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Focus styles for accessibility */
.switch-input:focus + .switch-track {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style> 