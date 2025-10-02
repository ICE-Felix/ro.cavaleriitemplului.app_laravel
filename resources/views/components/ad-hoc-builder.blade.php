<div id="ad_hoc_builder"
     x-data="adHocCalendarBuilder()"
     x-init="init()"
     x-cloak
     class="card p-4 rounded-xl shadow-sm border border-gray-200">

    <h3 class="text-lg font-semibold mb-3">{{ $label ?? 'Pick Dates & Hours' }}</h3>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Calendar + helpers -->
        <div class="lg:col-span-1">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm text-gray-600">
                    Fence:
                    <span x-text="min || '—'"></span>
                    →
                    <span x-text="max || '—'"></span>
                </div>
                <div class="text-sm">
                    <button type="button" class="btn btn_secondary btn_sm"
                            @click.prevent="prevMonth()" :disabled="!canPrev()">&laquo;</button>
                    <button type="button" class="btn btn_secondary btn_sm"
                            @click.prevent="nextMonth()" :disabled="!canNext()">&raquo;</button>
                </div>
            </div>

            <!-- One single month -->
            <div class="cal-month mb-3 border rounded" x-show="current">
                <div class="px-3 py-2 font-semibold border-b" x-text="current?.label || ''"></div>

                <div class="cal-grid p-2">
                    <div class="cal-head">Mon</div><div class="cal-head">Tue</div><div class="cal-head">Wed</div>
                    <div class="cal-head">Thu</div><div class="cal-head">Fri</div><div class="cal-head">Sat</div><div class="cal-head">Sun</div>

                    <!-- single flat grid render -->
                    <template x-for="cell in (current?.grid || [])" :key="cell.key">
                        <div
                                class="cal-cell"
                                :class="{
                                'cal-pad': cell.type === 'pad',
                                'cal-disabled': cell.type === 'day' && !cell.inFence,
                                'cal-selected': cell.type === 'day' && isSelected(cell.ymd)
                            }"
                                @click.prevent="cell.type === 'day' && cell.inFence ? toggleDay(cell) : null"
                        >
                            <span x-text="cell.type === 'day' ? cell.day : ''"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick selects -->
            <div class="mt-3 space-y-2">
                <div class="text-sm text-gray-600">Quick select within fence</div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn_secondary btn_sm" @click.prevent="selectAll()">All days</button>
                    <button type="button" class="btn btn_secondary btn_sm" @click.prevent="selectWeekdays()">Weekdays</button>
                    <button type="button" class="btn btn_secondary btn_sm" @click.prevent="selectWeekends()">Weekends</button>
                </div>

                <button type="button" class="btn btn_danger btn_sm" @click.prevent="clearAll()">Clear</button>
            </div>

            <div class="mt-4">
                <label class="label block mb-2">Apply common hours (optional)</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="time" class="form-control" x-model="common.start">
                    <input type="time" class="form-control" x-model="common.end">
                </div>
                <button type="button" class="btn btn_primary btn_sm mt-2" @click.prevent="applyCommon()">
                    Apply to all selected days
                </button>
            </div>
        </div>

        <!-- Per-date windows -->
        <div class="lg:col-span-2">
            <template x-if="((orderedDates || []).length === 0)">
                <p class="text-gray-500">No dates selected yet.</p>
            </template>

            <div class="space-y-3 max-h-[28rem] overflow-auto pr-1">
                <template x-for="d in (orderedDates || [])" :key="'row-'+d">
                    <div class="border rounded p-3">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">
                                <span x-text="fmtLong(d)"></span>
                                <span class="text-gray-500 ml-2">( <span x-text="d"></span> )</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn_secondary btn_sm" @click.prevent="duplicateFirst(d)">Copy first window</button>
                                <button type="button" class="btn btn_danger btn_sm"    @click.prevent="removeDate(d)">Remove date</button>
                                <button type="button" class="btn btn_secondary btn_sm" @click.prevent="addWindow(d)">Add Window</button>
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="label mb-2">Windows</label>

                            <div class="space-y-2">
                                <template x-for="(w, i) in (dateWindows[d] || [])" :key="w.uid">
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
                                            <button type="button" class="btn btn_secondary btn_sm" @click.prevent="dupWindow(d,i)">Duplicate</button>
                                            <button type="button" class="btn btn_danger btn_sm"    @click.prevent="delWindow(d,i)">Remove</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Hidden payload -->
    <input type="hidden" name="{{ $name ?? 'ad_hoc_windows_json' }}"
           id="ad_hoc_windows_json" value="{{ $value ?? '[]' }}">
</div>

@push('styles')
    <style>
        [x-cloak]{display:none!important}
        .btn_sm{padding:.35rem .6rem;font-size:.825rem}
        .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.25rem}
        .cal-head{font-size:.8rem;color:#6b7280;text-align:center;margin-bottom:.25rem}
        .cal-cell{height:2.2rem;display:flex;align-items:center;justify-content:center;border-radius:.5rem;cursor:pointer;border:1px solid #e5e7eb;transition:all 0.2s ease}
        .cal-cell:hover:not(.cal-pad):not(.cal-disabled){background:#f3f4f6;border-color:#0ea5e9}
        .cal-pad{background:#fafafa;border-style:dashed;cursor:default}
        #ad_hoc_builder .cal-cell.cal-selected{background:#0ea5e9 !important;color:#fff !important;border-color:#0ea5e9 !important;font-weight:600}
        .cal-disabled{opacity:.4;pointer-events:none}
    </style>
@endpush

@push('scripts')
    <script>
        function adHocCalendarBuilder(){
            return {
                // fence & view
                min: null,
                max: null,
                cursor: null,
                current: null,
                listenersAttached: false,
                _initialized: false,

                // data - MUST initialize as empty objects, not undefined
                selected: {},
                dateWindows: {},
                common: { start: '', end: '' },

                get orderedDates(){
                    try {
                        // Extra defensive - handle edge cases
                        if (!this.selected) {
                            this.selected = {};
                            return [];
                        }
                        if (typeof this.selected !== 'object') {
                            this.selected = {};
                            return [];
                        }
                        return Object.keys(this.selected).filter(k => this.selected[k] === true).sort();
                    } catch(e) {
                        console.error('orderedDates error:', e);
                        this.selected = {};
                        return [];
                    }
                },

                isSelected(ymd){
                    return !!(this.selected && this.selected[ymd]);
                },

                // ===== init =====
                init(){
                    const sd = document.getElementById('start_date');
                    const ed = document.getElementById('end_date');

                    const readFence = () => {
                        const newMin = this.normalizeInputDate(sd?.value || null);
                        const newMax = this.normalizeInputDate(ed?.value || null);

                        // Only rebuild if fence actually changed
                        if (newMin === this.min && newMax === this.max) return;

                        this.min = newMin;
                        this.max = newMax;

                        if (this.min && this.max && this.min > this.max){
                            const t = this.min; this.min = this.max; this.max = t;
                        }

                        if (!this.cursor) this.cursor = this.min || this.today();
                        this.buildMonth();
                    };

                    if (!this.listenersAttached){
                        sd?.addEventListener('change', readFence);
                        ed?.addEventListener('change', readFence);
                        this.listenersAttached = true;
                    }
                    readFence();

                    // restore from hidden payload (if any)
                    try{
                        const hidden = document.getElementById('ad_hoc_windows_json');
                        if (hidden && hidden.value){
                            const arr = JSON.parse(hidden.value);
                            const sel={}, win={};
                            (arr||[]).forEach(row=>{
                                if(!row || !row.date) return;
                                sel[row.date] = true;
                                win[row.date] = (row.windows||[]).map(w=>({
                                    uid: crypto.randomUUID(),
                                    start_time: this.normalizeTime(w?.start_time || ''),
                                    end_time:   this.normalizeTime(w?.end_time   || '')
                                }));
                            });
                            this.selected = sel;
                            this.dateWindows = win;
                        }
                    }catch(_){}

                    this._initialized = true;
                    this.syncPayload();
                },

                // ===== date helpers =====
                today(){
                    const d=new Date();
                    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                },
                normalizeInputDate(v){
                    if(!v) return null;
                    v = String(v).trim();
                    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;              // YYYY-MM-DD
                    if (/^\d{2}\/\d{2}\/\d{4}$/.test(v)){                     // MM/DD/YYYY or DD/MM/YYYY
                        let [a,b,c] = v.split('/').map(Number); let m=a,d=b;
                        if (b>12 && a<=12){ d=b; m=a; } else if (a>12 && b<=12){ d=a; m=b; }
                        return `${c}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                    }
                    if (/^\d{2}-\d{2}-\d{4}$/.test(v)){                       // DD-MM-YYYY or MM-DD-YYYY
                        let [a,b,c] = v.split('-').map(Number); let d=a,m=b;
                        if (b>12 && a<=12){ d=b; m=a; }
                        return `${c}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                    }
                    const dt = new Date(v);
                    return isNaN(dt) ? null :
                        `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
                },
                monthStart(ymd){
                    if(!ymd) return new Date();
                    const [y,m] = ymd.split('-').map(Number);
                    return new Date(y, m-1, 1);
                },
                inFence(ymd){
                    if (this.min && ymd < this.min) return false;
                    if (this.max && ymd > this.max) return false;
                    return true;
                },

                // ===== month build (single) =====
                buildMonth(){
                    const first = this.monthStart(this.cursor || this.min || this.today());
                    const y = first.getFullYear(), m = first.getMonth();

                    // Monday-first offset: Sun=0..Sat=6 → Mon=0..Sun=6
                    const dowSun0 = new Date(y, m, 1).getDay();
                    const offset  = (dowSun0 + 6) % 7;

                    const lastDay = new Date(y, m+1, 0).getDate();

                    // month days
                    const days = [];
                    for (let d=1; d<=lastDay; d++){
                        const ymd = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                        days.push({ type:'day', ymd, day:d, inFence:this.inFence(ymd) });
                    }

                    const used    = offset + lastDay;
                    const postPad = (7 - (used % 7)) % 7;

                    // build flat grid: pads + days + pads
                    const grid = [];
                    for (let i=0; i<offset; i++) grid.push({ type:'pad', key:`pad-start-${y}-${m+1}-${i}` });
                    for (const d of days) grid.push({ ...d, key:`day-${d.ymd}` });
                    for (let i=0; i<postPad; i++) grid.push({ type:'pad', key:`pad-end-${y}-${m+1}-${i}` });

                    this.current = {
                        key:   `${y}-${String(m+1).padStart(2,'0')}`,
                        label: first.toLocaleString(undefined,{month:'long',year:'numeric'}),
                        year:  y,
                        month: m,
                        offset,
                        days,     // kept in case you need it elsewhere
                        postPad,
                        grid      // <- single source of truth for rendering
                    };
                },

                // month nav limited to fence months
                minMonthStart(){ return this.min ? this.monthStart(this.min) : null; },
                maxMonthStart(){ return this.max ? this.monthStart(this.max) : null; },
                canPrev(){
                    if(!this.current) return false;
                    const prev = new Date(this.current.year, this.current.month-1, 1);
                    const minS = this.minMonthStart();
                    return !minS || prev >= minS;
                },
                canNext(){
                    if(!this.current) return false;
                    const next = new Date(this.current.year, this.current.month+1, 1);
                    const maxS = this.maxMonthStart();
                    return !maxS || next <= maxS;
                },
                prevMonth(){
                    const prev = new Date(this.current.year, this.current.month-1, 1);
                    this.cursor = `${prev.getFullYear()}-${String(prev.getMonth()+1).padStart(2,'0')}-01`;
                    this.buildMonth();
                },
                nextMonth(){
                    const next = new Date(this.current.year, this.current.month+1, 1);
                    this.cursor = `${next.getFullYear()}-${String(next.getMonth()+1).padStart(2,'0')}-01`;
                    this.buildMonth();
                },


                ensureDate(ymd){
                    if (!Array.isArray(this.dateWindows[ymd])) {
                        this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                    }
                },
                select(ymd){
                    if (!this.selected[ymd]) {
                        this.selected[ymd] = true;
                        this.ensureDate(ymd);
                    }
                },
                deselect(ymd){
                    if (this.selected && this.selected[ymd]) {
                        delete this.selected[ymd];
                        if (this.dateWindows && this.dateWindows[ymd]) {
                            delete this.dateWindows[ymd];
                        }
                    }
                },

                removeDate(ymd){
                    if (!this.selected) this.selected = {};
                    if (!this.dateWindows) this.dateWindows = {};

                    const newSelected = this.safeAssign(this.selected);
                    const newWindows = this.safeAssign(this.dateWindows);
                    delete newSelected[ymd];
                    delete newWindows[ymd];
                    this.selected = newSelected;
                    this.dateWindows = newWindows;
                    this.syncPayload();
                },
                safeAssign(target) {
                    if (!target || typeof target !== 'object') {
                        return {};
                    }
                    return Object.assign({}, target);
                },
                toggleDay(cell){
                    if (!cell || !cell.inFence || !this._initialized) return;
                    const ymd = cell.ymd;

                    if (this.isSelected(ymd)) {

                        this.$nextTick(() => {
                            // DEFENSIVE: ensure objects exist before assigning
                            if (!this.selected) this.selected = {};
                            if (!this.dateWindows) this.dateWindows = {};

                            delete this.selected[ymd];
                            delete this.dateWindows[ymd];
                            this.selected = this.safeAssign(this.selected);
                            this.dateWindows = this.safeAssign(this.dateWindows);
                            this.syncPayload();
                        });
                    } else {

                        this.$nextTick(() => {
                            if (!this.selected) this.selected = {};
                            if (!this.dateWindows) this.dateWindows = {};

                            this.selected[ymd] = true;
                            this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                            this.selected = this.safeAssign(this.selected);
                            this.dateWindows = this.safeAssign(this.dateWindows);
                            this.syncPayload();
                        });
                    }
                },

                // quick selects
                rangeDays(){
                    // If both ends exist, iterate inclusive range
                    if (this.min && this.max){
                        const [y1,m1,d1] = this.min.split('-').map(Number);
                        const [y2,m2,d2] = this.max.split('-').map(Number);
                        const from = new Date(y1,m1-1,d1);
                        const to   = new Date(y2,m2-1,d2);
                        const out  = [];
                        for (let t=new Date(from); t<=to; t.setDate(t.getDate()+1)){
                            out.push(`${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`);
                        }
                        return out;
                    }
                    // If only min set, allow that single day; if none, empty.
                    if (this.min && !this.max) return [this.min];
                    return [];
                },
                dow0Mon(ymd){
                    const [y,m,d] = ymd.split('-').map(Number);
                    const g = new Date(y,m-1,d);
                    return (g.getDay()+6)%7; // Mon=0..Sun=6
                },
                selectAll(){
                    if (!this.selected) this.selected = {};
                    if (!this.dateWindows) this.dateWindows = {};

                    for (const ymd of this.rangeDays()){
                        this.selected[ymd] = true;
                        if (!Array.isArray(this.dateWindows[ymd])) {
                            this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                        }
                    }
                    this.selected = this.safeAssign(this.selected);
                    this.dateWindows = this.safeAssign(this.dateWindows);
                    this.syncPayload();
                },

                selectWeekdays(){
                    if (!this.selected) this.selected = {};
                    if (!this.dateWindows) this.dateWindows = {};

                    for (const ymd of this.rangeDays()){
                        if (this.dow0Mon(ymd) < 5){
                            this.selected[ymd] = true;
                            if (!Array.isArray(this.dateWindows[ymd])) {
                                this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                            }
                        }
                    }
                    this.selected = this.safeAssign(this.selected);
                    this.dateWindows = this.safeAssign(this.dateWindows);
                    this.syncPayload();
                },

                selectWeekends(){
                    if (!this.selected) this.selected = {};
                    if (!this.dateWindows) this.dateWindows = {};

                    for (const ymd of this.rangeDays()){
                        if (this.dow0Mon(ymd) >= 5){
                            this.selected[ymd] = true;
                            if (!Array.isArray(this.dateWindows[ymd])) {
                                this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                            }
                        }
                    }
                    this.selected = this.safeAssign(this.selected);
                    this.dateWindows = this.safeAssign(this.dateWindows);
                    this.syncPayload();
                },
                clearAll(){
                    this.selected = {};
                    this.dateWindows = {};
                    this.syncPayload();
                },

                addWindow(ymd){
                    if (!Array.isArray(this.dateWindows[ymd])) {
                        this.dateWindows[ymd] = [];
                    }
                    this.dateWindows[ymd].push({ uid: crypto.randomUUID(), start_time:'', end_time:'' });
                    this.dateWindows = Object.assign({}, this.dateWindows);
                    this.syncPayload();
                },

                delWindow(ymd,i){
                    if (!Array.isArray(this.dateWindows[ymd])) return;

                    this.dateWindows[ymd].splice(i, 1);
                    if (this.dateWindows[ymd].length === 0) {
                        this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                    }
                    this.dateWindows = Object.assign({}, this.dateWindows);
                    this.syncPayload();
                },

                dupWindow(ymd,i){
                    if (!Array.isArray(this.dateWindows[ymd]) || !this.dateWindows[ymd][i]) return;

                    const clone = { ...this.dateWindows[ymd][i], uid: crypto.randomUUID() };
                    this.dateWindows[ymd].splice(i+1, 0, clone);
                    this.dateWindows = Object.assign({}, this.dateWindows);
                    this.syncPayload();
                },

                duplicateFirst(ymd){
                    if (!Array.isArray(this.dateWindows[ymd]) || !this.dateWindows[ymd][0]) return;

                    const f = this.dateWindows[ymd][0];
                    this.dateWindows[ymd].push({
                        uid: crypto.randomUUID(),
                        start_time: f.start_time,
                        end_time: f.end_time
                    });
                    this.dateWindows = Object.assign({}, this.dateWindows);
                    this.syncPayload();
                },

                // apply common hours to first window of each selected date
                applyCommon(){
                    if(!this.common.start || !this.common.end){
                        alert('Pick both start and end first.');
                        return;
                    }
                    const start = this.normalizeTime(this.common.start);
                    const end   = this.normalizeTime(this.common.end);
                    for(const ymd of this.orderedDates){
                        if(!Array.isArray(this.dateWindows[ymd]) || this.dateWindows[ymd].length === 0){
                            this.dateWindows[ymd] = [{ uid: crypto.randomUUID(), start_time:'', end_time:'' }];
                        }
                        this.dateWindows[ymd][0].start_time = start;
                        this.dateWindows[ymd][0].end_time = end;
                    }
                    this.syncPayload();
                },

                // formatting / payload
                fmtLong(ymd){
                    const [y,m,d] = ymd.split('-').map(Number);
                    const dt = new Date(y,m-1,d);
                    return dt.toLocaleDateString(undefined,{ weekday:'long', year:'numeric', month:'long', day:'numeric' });
                },
                normalizeTime(v){
                    if(!v) return '';
                    const s = String(v).trim();
                    if (/^\d{2}:\d{2}$/.test(s)) return s;
                    const m = s.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/);
                    if (m){
                        let hh = parseInt(m[1],10), mm = m[2], ap = m[3].toUpperCase();
                        if (ap==='AM'){ if (hh===12) hh=0; } else { if (hh!==12) hh+=12; }
                        return String(hh).padStart(2,'0') + ':' + mm;
                    }
                    const m2 = s.match(/^(\d{1,2})(?:\s*([AaPp][Mm]))?$/);
                    if (m2){
                        let hh = parseInt(m2[1],10); let ap = (m2[2]||'').toUpperCase();
                        if (ap==='AM'){ if (hh===12) hh=0; } else if (ap==='PM'){ if (hh!==12) hh+=12; }
                        return String(hh).padStart(2,'0') + ':00';
                    }
                    return s;
                },
                serialize(){
                    if (!this.selected || !this.dateWindows) {
                        return '[]';
                    }
                    const dates = Object.keys(this.selected).filter(k => this.selected[k]).sort();
                    return JSON.stringify(
                        dates.map(ymd => ({
                            date: ymd,
                            windows: (this.dateWindows[ymd] || []).map(w => ({
                                start_time: this.normalizeTime(w?.start_time || ''),
                                end_time:   this.normalizeTime(w?.end_time   || '')
                            }))
                        }))
                    );
                },
                syncPayload(){
                    if (!this._initialized) return;
                    try {
                        const el = document.getElementById('ad_hoc_windows_json');
                        if (el) el.value = this.serialize();
                    } catch(e) {
                        console.error('syncPayload error:', e);
                    }
                }
            }
        }
    </script>
@endpush