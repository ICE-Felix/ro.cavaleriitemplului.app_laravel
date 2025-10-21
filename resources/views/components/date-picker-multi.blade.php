@props([
    'name' => 'blackout_dates',
    'label' => 'Blackout Dates',
    'value' => '[]'
])

<div x-data="blackoutDatePicker()" x-init="init()" x-cloak>
    <label class="label block mb-2">{{ $label }}</label>

    <div class="flex gap-2 mb-3">
        <input type="date"
               class="form-control flex-1"
               x-model="newDate"
               @keyup.enter="addDate()">
        <button type="button"
                class="btn btn_primary"
                @click.prevent="addDate()">
            <i class="la la-plus"></i> Add
        </button>
    </div>

    <div class="space-y-2">
        <template x-for="(date, idx) in dates" :key="idx">
            <div class="flex items-center justify-between p-2 border rounded">
                <span x-text="formatDate(date)" class="text-sm"></span>
                <button type="button"
                        class="btn btn_danger btn_sm"
                        @click.prevent="removeDate(idx)">
                    <i class="la la-trash"></i>
                </button>
            </div>
        </template>

        <div x-show="dates.length === 0" class="text-sm text-gray-500 italic">
            No blackout dates set
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" id="{{ $name }}" x-model="hiddenValue" value="{{ $value }}">
</div>

@push('scripts')
    <script>
        function blackoutDatePicker() {
            return {
                dates: [],
                newDate: '',
                hiddenValue: '[]',
                _initialized: false,

                init() {
                    try {
                        const hidden = document.getElementById('{{ $name }}');
                        if (hidden && hidden.value) {
                            const data = JSON.parse(hidden.value);
                            if (Array.isArray(data)) {
                                this.dates = data.filter(d => d);
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing blackout dates:', e);
                    }

                    this._initialized = true;
                    this.syncPayload();
                },

                addDate() {
                    if (this.newDate && !this.dates.includes(this.newDate)) {
                        this.dates.push(this.newDate);
                        this.dates.sort();
                        this.newDate = '';
                        this.syncPayload();
                    }
                },

                removeDate(idx) {
                    this.dates.splice(idx, 1);
                    this.syncPayload();
                },

                formatDate(isoDate) {
                    const [y, m, d] = isoDate.split('-').map(Number);
                    const date = new Date(y, m - 1, d);
                    return date.toLocaleDateString('en-US', {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                syncPayload() {
                    if (!this._initialized) return;
                    this.hiddenValue = JSON.stringify(this.dates);
                }
            }
        }
    </script>
@endpush