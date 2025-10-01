<div id="periods_builder"
     x-data="periodsBuilder()"
     x-init="init()"
     x-cloak
     class="card p-4 rounded-xl shadow-sm border border-gray-200">

    <h3 class="text-lg font-semibold mb-3">{{ $label ?? 'Recurring Rules' }}</h3>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1">
            <div class="space-y-3">
                <div>
                    <label class="label block mb-2">Frequency</label>
                    <select class="form-control" x-model="freq" @change="syncFromFreq()">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <div>
                    <div class="text-sm text-gray-600">Fence:
                        <span x-text="starts_on||'—'"></span> → <span x-text="ends_on||'—'"></span>
                    </div>
                </div>

                <template x-if="freq==='weekly'">
                    <div>
                        <label class="label block mb-2">Weekdays</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(n,idx) in weekdayNames" :key="'wk'+idx">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" class="form-checkbox" :value="idx" @change="toggleWeekly(idx)" :checked="weekly_days.has(idx)">
                                    <span x-text="n"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="freq==='monthly'">
                    <div>
                        <label class="label block mb-2">Days of month</label>
                        <div class="grid grid-cols-8 gap-2">
                            <template x-for="d in 31" :key="'md'+d">
                                <label class="inline-flex items-center gap-1 text-sm">
                                    <input type="checkbox" class="form-checkbox" :value="d" @change="toggleMonthly(d)" :checked="monthly_days.has(d)">
                                    <span x-text="d"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <label class="label">Time Windows</label>
                <button type="button" class="btn btn_secondary btn_sm" @click="addWindow()">Add Window</button>
            </div>

            <div class="space-y-2">
                <template x-for="(w,i) in windows" :key="w.uid">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="label block mb-2">Start</label>
                            <input type="time" class="form-control" x-model="w.start_time" @change="syncPayload()">
                        </div>
                        <div>
                            <label class="label block mb-2">End</label>
                            <input type="time" class="form-control" x-model="w.end_time" @change="syncPayload()">
                            <small class="text-gray-500">If end &lt; start ⇒ overnight</small>
                        </div>
                        <div class="flex gap-2 md:justify-end">
                            <button type="button" class="btn btn_secondary btn_sm" @click="dupWindow(i)">Duplicate</button>
                            <button type="button" class="btn btn_danger btn_sm" @click="delWindow(i)">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <input type="hidden" name="schedules_json" id="schedules_json" :value="serialize()">
</div>

@push('styles')
    <style>
        [x-cloak]{display:none!important}
        .btn_sm{padding:.35rem .6rem;font-size:.825rem}

        /* ========= Scoped fallback layout for periods-builder ========= */
        #periods_builder .grid { display: grid; gap: .5rem; }
        /* left side form stack */
        #periods_builder .grid-cols-1 { grid-template-columns: 1fr; }
        #periods_builder .lg\:grid-cols-3 { grid-template-columns: 1fr; }
        @media (min-width: 1024px){
            #periods_builder .lg\:grid-cols-3 { grid-template-columns: 1fr 2fr; }
        }

        /* Time windows row (3 columns: start | end | actions) */
        #periods_builder .md\:grid-cols-3 { display:grid; grid-template-columns: 1fr 1fr auto; gap:.75rem; }

        /* Monthly day grid (8 columns) */
        #periods_builder .grid-cols-8 { grid-template-columns: repeat(8, minmax(0,1fr)); }

        /* Utility fallbacks used inside the component */
        #periods_builder .inline-flex { display:inline-flex; align-items:center; gap:.25rem; }
        #periods_builder .items-center { align-items:center; }
        #periods_builder .gap-1 { gap:.25rem; }
        #periods_builder .gap-2 { gap:.5rem; }
        #periods_builder .text-sm { font-size:.875rem; }
        #periods_builder .label { display:block; margin-bottom:.25rem; font-weight:600; }
        #periods_builder .form-control { width:100%; }

        /* ========= Kill theme-injected ornaments on labels/checkboxes ========= */
        #periods_builder label::before,
        #periods_builder label::after { content:none !important; }
        #periods_builder .form-checkbox {
            appearance:auto; -webkit-appearance:auto; -moz-appearance:auto;
            width:1rem; height:1rem; margin:0;
            position:static;
        }
        #periods_builder .form-checkbox::before,
        #periods_builder .form-checkbox::after { content:none !important; }
    </style>
@endpush

@push('scripts')
    <script>
        function periodsBuilder () {
            return {
                // fence
                starts_on: null,
                ends_on:   null,

                // model
                freq: 'daily',                              // daily | weekly | monthly
                weekdayNames: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
                weekly_days:  new Set(),                    // 0=Mon..6=Sun
                monthly_days: new Set(),                    // 1..31
                windows: [],

                init() {
                    // read fence from the date fields on the page
                    const sd = document.getElementById('start_date');
                    const ed = document.getElementById('end_date');
                    const readFence = () => {
                        this.starts_on = sd?.value || null;
                        this.ends_on   = ed?.value || null;
                    };
                    sd?.addEventListener('change', readFence);
                    ed?.addEventListener('change', readFence);
                    readFence();

                    // pick freq from <select id="schedule_type"> if present
                    const st = document.getElementById('schedule_type');
                    if (st && st.value) this.freq = st.value;
                    st && st.addEventListener('change', () => {
                        this.freq = st.value;
                        this.syncFromFreq();
                    });

                    // defaults
                    this.windows = [ this.newWindow() ];
                    this.syncPayload();
                },

                newWindow() {
                    return { uid: crypto.randomUUID(), start_time: '', end_time: '' };
                },

                addWindow() {
                    this.windows.push(this.newWindow());
                    this.syncPayload();
                },

                delWindow(i) {
                    this.windows.splice(i, 1);
                    if (this.windows.length === 0) this.windows = [ this.newWindow() ];
                    this.syncPayload();
                }, // <-- IMPORTANT COMMA HERE

                dupWindow(i) {
                    const c = JSON.parse(JSON.stringify(this.windows[i]));
                    c.uid = crypto.randomUUID();
                    this.windows.splice(i + 1, 0, c);
                    this.syncPayload();
                },

                toggleWeekly(idx) {
                    this.weekly_days.has(idx) ? this.weekly_days.delete(idx) : this.weekly_days.add(idx);
                    this.syncPayload();
                },

                toggleMonthly(d) {
                    this.monthly_days.has(d) ? this.monthly_days.delete(d) : this.monthly_days.add(d);
                    this.syncPayload();
                },

                syncFromFreq() {
                    // clear day selections when frequency changes
                    this.weekly_days.clear();
                    this.monthly_days.clear();
                    this.syncPayload();
                },

                serialize() {
                    const payload = {
                        frequency: this.freq,
                        starts_on: this.starts_on,
                        ends_on:   this.ends_on,
                        windows:   this.windows.map(w => ({ start_time: w.start_time, end_time: w.end_time }))
                    };
                    if (this.freq === 'weekly')  payload.weekly_days  = Array.from(this.weekly_days).sort((a,b)=>a-b);
                    if (this.freq === 'monthly') payload.monthly_days = Array.from(this.monthly_days).sort((a,b)=>a-b);
                    return JSON.stringify(payload);
                },

                syncPayload() {
                    const el = document.getElementById('schedules_json');
                    if (el) el.value = this.serialize();
                }
            }
        }
    </script>
@endpush

