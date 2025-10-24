@props([
    'name' => 'ad_hoc_dates',
    'label' => 'Availability Calendar',
    'value' => '[]'
])

<div id="venue_product_calendar"
     x-data="venueProductCalendar()"
     x-init="init()"
     x-cloak
     class="card p-4 rounded-xl shadow-sm border border-gray-200">

    <h3 class="text-lg font-semibold mb-3">{{ $label }}</h3>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
        <p class="text-sm text-blue-800">
            <strong>Instructions:</strong> Select dates when this product is available for booking.
            For each date, you can specify multiple time windows. Each time window has to be taken into consideration when adding the available units.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Calendar -->
        <div class="lg:col-span-1">
            <!-- Month navigation -->
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold" x-text="currentMonthLabel"></h4>
                <div class="flex gap-2">
                    <button type="button" class="btn btn_secondary btn_sm" @click.prevent="prevMonth()">&laquo;</button>
                    <button type="button" class="btn btn_secondary btn_sm" @click.prevent="nextMonth()">&raquo;</button>
                </div>
            </div>

            <!-- Calendar grid -->
            <div class="cal-month border rounded mb-3">
                <div class="cal-grid p-2">
                    <div class="cal-head">Mon</div>
                    <div class="cal-head">Tue</div>
                    <div class="cal-head">Wed</div>
                    <div class="cal-head">Thu</div>
                    <div class="cal-head">Fri</div>
                    <div class="cal-head">Sat</div>
                    <div class="cal-head">Sun</div>

                    <template x-for="cell in calendarGrid" :key="cell.key">
                        <div
                                class="cal-cell"
                                :class="{
                                'cal-pad': cell.type === 'pad',
                                'cal-selected': cell.type === 'day' && isSelected(cell.date),
                                'cal-past': cell.type === 'day' && isPast(cell.date)
                            }"
                                @click.prevent="cell.type === 'day' && !isPast(cell.date) ? toggleDate(cell.date) : null"
                        >
                            <span x-text="cell.type === 'day' ? cell.day : ''"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="space-y-2">
                <div class="flex gap-2">
                    <button type="button" class="btn btn_secondary btn_sm flex-1" @click.prevent="selectWeekdays()">
                        Weekdays
                    </button>
                    <button type="button" class="btn btn_secondary btn_sm flex-1" @click.prevent="selectWeekends()">
                        Weekends
                    </button>
                </div>
                <button type="button" class="btn btn_danger btn_sm w-full" @click.prevent="clearAll()">
                    Clear All
                </button>
            </div>

            <!-- Common hours -->
            <div class="mt-4 border-t pt-4">
                <label class="label block mb-2">Apply Common Hours</label>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <input type="time" class="form-control" x-model="commonStart" placeholder="Start">
                    <input type="time" class="form-control" x-model="commonEnd" placeholder="End">
                </div>
                <button type="button" class="btn btn_primary btn_sm w-full" @click.prevent="applyCommonHours()">
                    Apply to Selected
                </button>
            </div>
        </div>

        <!-- Selected dates list -->
        <div class="lg:col-span-2">
            <h4 class="font-semibold mb-3">
                Selected Dates (<span x-text="Object.keys(selectedDates).length"></span>)
            </h4>

            <div x-show="Object.keys(selectedDates).length === 0" class="text-center py-8 text-gray-500">
                <i class="la la-calendar text-4xl mb-2"></i>
                <p>No dates selected. Click on calendar dates to add availability.</p>
            </div>

            <div class="space-y-3 max-h-[32rem] overflow-auto pr-2">
                <template x-for="date in sortedDates" :key="date">
                    <div class="border rounded p-3 bg-white">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="font-semibold" x-text="formatDate(date)"></div>
                                <div class="text-sm text-gray-500" x-text="date"></div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn_secondary btn_sm" @click.prevent="addWindow(date)">
                                    <i class="la la-plus"></i> Add Window
                                </button>
                                <button type="button" class="btn btn_danger btn_sm" @click.prevent="removeDate(date)">
                                    <i class="la la-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(window, idx) in selectedDates[date]" :key="window.uid">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                                    <div>
                                        <label class="label block mb-1">Start Time</label>
                                        <input type="time" class="form-control"
                                               x-model="window.start_time"
                                               @change="syncPayload()">
                                    </div>
                                    <div>
                                        <label class="label block mb-1">End Time</label>
                                        <input type="time" class="form-control"
                                               x-model="window.end_time"
                                               @change="syncPayload()">
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" class="btn btn_secondary btn_sm"
                                                @click.prevent="duplicateWindow(date, idx)">
                                            <i class="la la-copy"></i>
                                        </button>
                                        <button type="button" class="btn btn_danger btn_sm"
                                                @click.prevent="removeWindow(date, idx)">
                                            <i class="la la-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Hidden input -->
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" x-model="hiddenValue" value="{{ $value }}">
</div>

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .btn_sm { padding: .35rem .6rem; font-size: .825rem; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: .25rem; }
        .cal-head { font-size: .8rem; color: #6b7280; text-align: center; margin-bottom: .25rem; }
        .cal-cell {
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: .375rem;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        .cal-cell:hover:not(.cal-pad):not(.cal-past) {
            background: #f3f4f6;
            border-color: #0ea5e9;
        }
        .cal-pad {
            background: #fafafa;
            border-style: dashed;
            cursor: default;
        }
        .cal-selected {
            background: #0ea5e9 !important;
            color: white !important;
            border-color: #0ea5e9 !important;
            font-weight: 600;
        }
        .cal-past {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f9fafb;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function venueProductCalendar() {
            return {
                currentYear: new Date().getFullYear(),
                currentMonth: new Date().getMonth(),
                selectedDates: {},
                commonStart: '',
                commonEnd: '',
                hiddenValue: '[]',
                _initialized: false,

                get currentMonthLabel() {
                    const date = new Date(this.currentYear, this.currentMonth, 1);
                    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                },

                get calendarGrid() {
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

                    // Monday-first: Sunday=0 -> Monday=0
                    const firstDayOfWeek = (firstDay.getDay() + 6) % 7;

                    const grid = [];

                    // Padding cells
                    for (let i = 0; i < firstDayOfWeek; i++) {
                        grid.push({ type: 'pad', key: `pad-start-${i}` });
                    }

                    // Day cells
                    for (let day = 1; day <= lastDay; day++) {
                        const date = this.formatDateISO(new Date(this.currentYear, this.currentMonth, day));
                        grid.push({
                            type: 'day',
                            day,
                            date,
                            key: `day-${date}`
                        });
                    }

                    // End padding
                    const remaining = (7 - (grid.length % 7)) % 7;
                    for (let i = 0; i < remaining; i++) {
                        grid.push({ type: 'pad', key: `pad-end-${i}` });
                    }

                    return grid;
                },

                get sortedDates() {
                    return Object.keys(this.selectedDates).sort();
                },

                init() {
                    // Restore from hidden input
                    try {
                        const hidden = document.getElementById('{{ $name }}');
                        if (hidden && hidden.value) {
                            const data = JSON.parse(hidden.value);
                            if (Array.isArray(data)) {
                                data.forEach(item => {
                                    if (item.date && Array.isArray(item.windows)) {
                                        this.selectedDates[item.date] = item.windows.map(w => ({
                                            uid: crypto.randomUUID(),
                                            start_time: w.start_time || '',
                                            end_time: w.end_time || ''
                                        }));
                                    }
                                });
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing calendar data:', e);
                    }

                    this._initialized = true;
                    this.syncPayload();
                },

                formatDateISO(date) {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                },

                formatDate(isoDate) {
                    const [y, m, d] = isoDate.split('-').map(Number);
                    const date = new Date(y, m - 1, d);
                    return date.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                isPast(date) {
                    const today = this.formatDateISO(new Date());
                    return date < today;
                },

                isSelected(date) {
                    return !!this.selectedDates[date];
                },

                toggleDate(date) {
                    if (this.selectedDates[date]) {
                        delete this.selectedDates[date];
                    } else {
                        this.selectedDates[date] = [{
                            uid: crypto.randomUUID(),
                            start_time: '',
                            end_time: ''
                        }];
                    }
                    this.selectedDates = { ...this.selectedDates };
                    this.syncPayload();
                },

                removeDate(date) {
                    if (confirm('Remove this date?')) {
                        delete this.selectedDates[date];
                        this.selectedDates = { ...this.selectedDates };
                        this.syncPayload();
                    }
                },

                addWindow(date) {
                    if (!this.selectedDates[date]) {
                        this.selectedDates[date] = [];
                    }
                    this.selectedDates[date].push({
                        uid: crypto.randomUUID(),
                        start_time: '',
                        end_time: ''
                    });
                    this.selectedDates = { ...this.selectedDates };
                    this.syncPayload();
                },

                removeWindow(date, idx) {
                    if (this.selectedDates[date]) {
                        this.selectedDates[date].splice(idx, 1);
                        if (this.selectedDates[date].length === 0) {
                            this.selectedDates[date] = [{
                                uid: crypto.randomUUID(),
                                start_time: '',
                                end_time: ''
                            }];
                        }
                        this.selectedDates = { ...this.selectedDates };
                        this.syncPayload();
                    }
                },

                duplicateWindow(date, idx) {
                    if (this.selectedDates[date] && this.selectedDates[date][idx]) {
                        const original = this.selectedDates[date][idx];
                        this.selectedDates[date].push({
                            uid: crypto.randomUUID(),
                            start_time: original.start_time,
                            end_time: original.end_time
                        });
                        this.selectedDates = { ...this.selectedDates };
                        this.syncPayload();
                    }
                },

                applyCommonHours() {
                    if (!this.commonStart || !this.commonEnd) {
                        alert('Please set both start and end times.');
                        return;
                    }

                    Object.keys(this.selectedDates).forEach(date => {
                        if (this.selectedDates[date] && this.selectedDates[date][0]) {
                            this.selectedDates[date][0].start_time = this.commonStart;
                            this.selectedDates[date][0].end_time = this.commonEnd;
                        }
                    });

                    this.selectedDates = { ...this.selectedDates };
                    this.syncPayload();
                },

                selectWeekdays() {
                    const year = this.currentYear;
                    const month = this.currentMonth;
                    const lastDay = new Date(year, month + 1, 0).getDate();

                    for (let day = 1; day <= lastDay; day++) {
                        const date = new Date(year, month, day);
                        const dayOfWeek = date.getDay();

                        // Monday-Friday (1-5)
                        if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                            const isoDate = this.formatDateISO(date);
                            if (!this.isPast(isoDate) && !this.selectedDates[isoDate]) {
                                this.selectedDates[isoDate] = [{
                                    uid: crypto.randomUUID(),
                                    start_time: '',
                                    end_time: ''
                                }];
                            }
                        }
                    }

                    this.selectedDates = { ...this.selectedDates };
                    this.syncPayload();
                },

                selectWeekends() {
                    const year = this.currentYear;
                    const month = this.currentMonth;
                    const lastDay = new Date(year, month + 1, 0).getDate();

                    for (let day = 1; day <= lastDay; day++) {
                        const date = new Date(year, month, day);
                        const dayOfWeek = date.getDay();

                        // Saturday-Sunday (6, 0)
                        if (dayOfWeek === 0 || dayOfWeek === 6) {
                            const isoDate = this.formatDateISO(date);
                            if (!this.isPast(isoDate) && !this.selectedDates[isoDate]) {
                                this.selectedDates[isoDate] = [{
                                    uid: crypto.randomUUID(),
                                    start_time: '',
                                    end_time: ''
                                }];
                            }
                        }
                    }

                    this.selectedDates = { ...this.selectedDates };
                    this.syncPayload();
                },

                clearAll() {
                    if (confirm('Clear all selected dates?')) {
                        this.selectedDates = {};
                        this.syncPayload();
                    }
                },

                prevMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                },

                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                },

                syncPayload() {
                    if (!this._initialized) return;

                    try {
                        const payload = Object.keys(this.selectedDates)
                            .sort()
                            .map(date => ({
                                date,
                                windows: this.selectedDates[date].map(w => ({
                                    start_time: w.start_time || '',
                                    end_time: w.end_time || ''
                                }))
                            }));

                        this.hiddenValue = JSON.stringify(payload);
                    } catch (e) {
                        console.error('Error syncing payload:', e);
                    }
                }
            }
        }
    </script>
@endpush