@props([
    'name' => 'schedule',
    'label' => 'Business Hours',
    'value' => null,
    'error' => null,
    'required' => false
])

@php
    // Default schedule structure
    $defaultSchedule = [
        'monday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
        'tuesday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
        'wednesday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
        'thursday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
        'friday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
        'saturday' => ['enabled' => false, 'open' => '10:00', 'close' => '16:00'],
        'sunday' => ['enabled' => false, 'open' => '10:00', 'close' => '16:00']
    ];
    
    // Parse existing value
    $schedule = $defaultSchedule;
    if ($value) {
        if (is_string($value)) {
            $parsedValue = json_decode($value, true);
            if ($parsedValue) {
                $schedule = array_merge($schedule, $parsedValue);
            }
        } elseif (is_array($value)) {
            $schedule = array_merge($schedule, $value);
        }
    }
    
    // Days of the week with proper labels
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday', 
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday'
    ];
@endphp

<div class="form-group">
    <label class="form-label">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    
    @if($error)
        <div class="text-red-500 text-sm mt-1">{{ $error }}</div>
    @endif
    
    <div class="schedule-container bg-white border border-gray-300 rounded-lg p-4 mt-2">
        <!-- Hidden input to store the complete schedule as JSON -->
        <input type="hidden" name="{{ $name }}" id="{{ $name }}_data" value="{{ json_encode($schedule) }}">
        
        <!-- Quick Actions -->
        <div class="schedule-quick-actions p-3 flex flex-wrap gap-2">
            <button type="button" class="btn btn_primary px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600" 
                    onclick="setAllDays(true)">
                Enable All Days
            </button>
            <button type="button" class="btn btn_primary px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600" 
                    onclick="setAllDays(false)">
                Disable All Days
            </button>
            <button type="button" class="btn btn_primary px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600" 
                    onclick="setBusinessHours()">
                Set Business Hours (Mon-Fri 9-17)
            </button>
        </div>
        
        <!-- Schedule Grid -->
        <div class="schedule-grid space-y-3 p-3">
            @foreach($days as $dayKey => $dayLabel)
                @php
                    $daySchedule = $schedule[$dayKey] ?? $defaultSchedule[$dayKey];
                @endphp
                
                <div class="schedule-day flex items-center gap-4 rounded-lg {{ $daySchedule['enabled'] ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                    <!-- Day Checkbox -->
                    <div class="day-checkbox flex items-center min-w-[120px]">
                        <input type="checkbox" 
                               id="{{ $name }}_{{ $dayKey }}_enabled"
                               class="day-enabled-checkbox mr-2 w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                               {{ $daySchedule['enabled'] ? 'checked' : '' }}
                               onchange="toggleDay('{{ $dayKey }}')">
                        <label for="{{ $name }}_{{ $dayKey }}_enabled" class="label-day font-medium text-gray-700 cursor-pointer">
                            {{ $dayLabel }}
                        </label>
                    </div>
                    
                    <!-- Time Inputs -->
                    <div class="time-inputs flex items-center gap-2 {{ $daySchedule['enabled'] ? '' : 'opacity-50' }}">
                        <div class="flex items-center gap-1">
                            <label class="text-sm text-gray-600"></label>
                            <input type="time" 
                                   id="{{ $name }}_{{ $dayKey }}_open"
                                   class="time-input border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   value="{{ $daySchedule['open'] }}"
                                   {{ $daySchedule['enabled'] ? '' : 'disabled' }}
                                   onchange="updateSchedule('{{ $dayKey }}', 'open', this.value)">
                        </div>
                        
                        <span class="text-gray-500">-</span>
                        
                        <div class="flex items-center gap-1">
                            <label class="text-sm text-gray-600"></label>
                            <input type="time" 
                                   id="{{ $name }}_{{ $dayKey }}_close"
                                   class="time-input border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   value="{{ $daySchedule['close'] }}"
                                   {{ $daySchedule['enabled'] ? '' : 'disabled' }}
                                   onchange="updateSchedule('{{ $dayKey }}', 'close', this.value)">
                        </div>
                    </div>
                    
                    <!-- Status Indicator -->
                    <div class="status-indicator ml-auto">
                        <span class="status-badge px-2 py-1 text-xs rounded-full {{ $daySchedule['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $daySchedule['enabled'] ? ' ( Open ) ' : ' ( Closed ) ' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Schedule Preview -->
        <div class="schedule-preview mt-4 p-3 bg-gray-50 rounded-lg">
            <h4 class="font-medium text-gray-700 mb-2">Schedule Preview:</h4>
            <div class="preview-text text-sm text-gray-600" id="schedule-preview">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
    // Global schedule data
    let scheduleData = @json($schedule);
    const scheduleName = '{{ $name }}';
    
    // Initialize schedule preview
    document.addEventListener('DOMContentLoaded', function() {
        updateSchedulePreview();
    });
    
    // Toggle day enabled/disabled
    function toggleDay(day) {
        const checkbox = document.getElementById(`${scheduleName}_${day}_enabled`);
        const openInput = document.getElementById(`${scheduleName}_${day}_open`);
        const closeInput = document.getElementById(`${scheduleName}_${day}_close`);
        const dayContainer = checkbox.closest('.schedule-day');
        
        scheduleData[day].enabled = checkbox.checked;
        
        // Enable/disable time inputs
        openInput.disabled = !checkbox.checked;
        closeInput.disabled = !checkbox.checked;
        
        // Update visual styling
        if (checkbox.checked) {
            dayContainer.classList.remove('bg-gray-50');
            dayContainer.classList.add('bg-green-50', 'border-green-200');
            dayContainer.querySelector('.time-inputs').classList.remove('opacity-50');
            dayContainer.querySelector('.status-badge').classList.remove('bg-gray-100', 'text-gray-600');
            dayContainer.querySelector('.status-badge').classList.add('bg-green-100', 'text-green-800');
            dayContainer.querySelector('.status-badge').textContent = '( Open )';
        } else {
            dayContainer.classList.remove('bg-green-50', 'border-green-200');
            dayContainer.classList.add('bg-gray-50');
            dayContainer.querySelector('.time-inputs').classList.add('opacity-50');
            dayContainer.querySelector('.status-badge').classList.remove('bg-green-100', 'text-green-800');
            dayContainer.querySelector('.status-badge').classList.add('bg-gray-100', 'text-gray-600');
            dayContainer.querySelector('.status-badge').textContent = '( Closed )';
        }
        
        updateHiddenInput();
        updateSchedulePreview();
    }
    
    // Update schedule time
    function updateSchedule(day, field, value) {
        scheduleData[day][field] = value;
        updateHiddenInput();
        updateSchedulePreview();
    }
    
    // Update hidden input with JSON data
    function updateHiddenInput() {
        document.getElementById(`${scheduleName}_data`).value = JSON.stringify(scheduleData);
    }
    
    // Set all days enabled/disabled
    function setAllDays(enabled) {
        Object.keys(scheduleData).forEach(day => {
            scheduleData[day].enabled = enabled;
            const checkbox = document.getElementById(`${scheduleName}_${day}_enabled`);
            checkbox.checked = enabled;
            toggleDay(day);
        });
    }
    
    // Set standard business hours
    function setBusinessHours() {
        const businessDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        const weekendDays = ['saturday', 'sunday'];
        
        // Enable business days with 9-17 hours
        businessDays.forEach(day => {
            scheduleData[day].enabled = true;
            scheduleData[day].open = '09:00';
            scheduleData[day].close = '17:00';
            
            document.getElementById(`${scheduleName}_${day}_enabled`).checked = true;
            document.getElementById(`${scheduleName}_${day}_open`).value = '09:00';
            document.getElementById(`${scheduleName}_${day}_close`).value = '17:00';
            toggleDay(day);
        });
        
        // Disable weekends
        weekendDays.forEach(day => {
            scheduleData[day].enabled = false;
            document.getElementById(`${scheduleName}_${day}_enabled`).checked = false;
            toggleDay(day);
        });
    }
    
    // Update schedule preview text
    function updateSchedulePreview() {
        const preview = document.getElementById('schedule-preview');
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayLabels = {
            'monday': 'Monday',
            'tuesday': 'Tuesday',
            'wednesday': 'Wednesday', 
            'thursday': 'Thursday',
            'friday': 'Friday',
            'saturday': 'Saturday',
            'sunday': 'Sunday'
        };
        
        let previewText = '';
        const openDays = days.filter(day => scheduleData[day].enabled);
        
        if (openDays.length === 0) {
            previewText = 'Closed all days';
        } else {
            openDays.forEach(day => {
                const dayData = scheduleData[day];
                previewText += `${dayLabels[day]}: ${dayData.open} - ${dayData.close}<br>`;
            });
        }
        
        preview.innerHTML = previewText;
    }
</script>

<style>
    .schedule-container {
        max-width: 100%;
    }
    
    .schedule-day {
        transition: all 0.3s ease;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }
    
    .time-input {
        width: 120px;
        margin-left: 0.5rem;
    }
    
    .btn-quick-action {
        transition: all 0.2s ease;
    }
    
    .btn-quick-action:hover {
        transform: translateY(-1px);
    }
    
    .status-badge {
        transition: all 0.3s ease;
    }

    .label-day {
        padding-left: 0.5rem;
        width: 90px;
    }

    .form-label {
        font-weight: 700;
    }
    
    @media (max-width: 768px) {
        .schedule-day {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }
        
        .day-checkbox {
            min-width: auto;
        }
        
        .time-inputs {
            width: 100%;
            justify-content: space-between;
        }
        
        .status-indicator {
            margin-left: 0;
            align-self: flex-end;
        }
    }
</style> 