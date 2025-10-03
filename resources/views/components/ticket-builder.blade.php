<div id="ticket_builder"
     x-data="ticketBuilder()"
     x-init="init()"
     x-cloak
     class="card p-4 rounded-xl shadow-sm border border-gray-200">

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">{{ $label ?? 'Ticket Types' }}</h3>
        <button type="button"
                class="btn btn_primary btn_sm"
                @click.prevent="addTicket()">
            Add Ticket
        </button>
    </div>

    <template x-if="tickets.length === 0">
        <p class="text-gray-500">No tickets added yet. Click "Add Ticket" to create one.</p>
    </template>

    <div class="space-y-4">
        <template x-for="(ticket, index) in tickets" :key="ticket.uid">
            <div class="border rounded p-4 bg-gray-50">
                <!-- Row 1: Name, Scope, Price -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="label block mb-2">Name</label>
                        <input type="text"
                               class="form-control"
                               placeholder="e.g., Adult General Admission"
                               x-model="ticket.name"
                               @input="syncPayload()">
                    </div>

                    <div>
                        <label class="label block mb-2">Scope</label>
                        <select class="form-control"
                                x-model="ticket.scope"
                                @change="syncPayload()">
                            <option value="">Select scope</option>
                            <option value="1-day-pass">1-Day Pass</option>
                            <option value="weekly-pass">Weekly Pass</option>
                            <option value="monthly-pass">Monthly Pass</option>
                            <option value="season-pass">Season Pass</option>
                        </select>
                    </div>

                    <div>
                        <label class="label block mb-2">Price</label>
                        <input type="number"
                               class="form-control"
                               placeholder="0.00"
                               step="0.01"
                               min="0"
                               x-model="ticket.price"
                               @input="syncPayload()">
                    </div>
                </div>

                <!-- Row 2: Age Category, Max Per Order, Is Active -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="label block mb-2">Age Category</label>
                        <input type="text"
                               class="form-control"
                               placeholder="e.g., Adult, Child, Senior"
                               x-model="ticket.age_category"
                               @input="syncPayload()">
                    </div>

                    <div>
                        <label class="label block mb-2">Max Per Order</label>
                        <input type="number"
                               class="form-control"
                               placeholder="e.g., 10"
                               min="1"
                               x-model="ticket.max_per_order"
                               @input="syncPayload()">
                    </div>

                    <div>
                        <label class="label block mb-2">Is Active</label>
                        <div class="flex items-center gap-3 mt-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox"
                                       class="form-checkbox"
                                       x-model="ticket.is_active"
                                       @change="syncPayload()">
                                <span class="ml-2" x-text="ticket.is_active ? 'Active' : 'Inactive'"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Frequency -->
                <div class="mb-4">
                    <label class="label block mb-2">Frequency</label>
                    <select class="form-control"
                            x-model="ticket.frequency"
                            @change="onFrequencyChange(ticket); syncPayload()">
                        <option value="">Select frequency</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <!-- Conditional: Weekly Days -->
                <div x-show="ticket.frequency === 'weekly'" class="mb-4">
                    <label class="label block mb-2">Weekly Days</label>
                    <div class="flex flex-wrap gap-3">
                        <template x-for="(day, dayIndex) in weekDays" :key="dayIndex">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox"
                                       class="form-checkbox"
                                       :checked="ticket.weekly_days.includes(dayIndex)"
                                       @change="toggleWeekDay(ticket, dayIndex)">
                                <span class="ml-2" x-text="day"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Conditional: Monthly Days -->
                <div x-show="ticket.frequency === 'monthly'" class="mb-4">
                    <label class="label block mb-2">Monthly Days</label>
                    <div class="flex flex-wrap gap-3">
                        <template x-for="day in 31" :key="day">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox"
                                       class="form-checkbox"
                                       :checked="ticket.monthly_days.includes(day - 1)"
                                       @change="toggleMonthDay(ticket, day - 1)">
                                <span class="ml-2" x-text="day"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 pt-3 border-t">
                    <button type="button"
                            class="btn btn_secondary btn_sm"
                            @click.prevent="duplicateTicket(index)">
                        Duplicate
                    </button>
                    <button type="button"
                            class="btn btn_danger btn_sm"
                            @click.prevent="removeTicket(index)">
                        Remove
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Hidden payload -->
    <input type="hidden"
           name="{{ $name ?? 'tickets' }}"
           id="tickets_json"
           x-bind:value="serialize()">
</div>

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .btn_sm { padding: .35rem .6rem; font-size: .825rem; }
        .form-checkbox { width: 1rem; height: 1rem; }
    </style>
@endpush

@push('scripts')
    <script>
        function ticketBuilder() {
            return {
                tickets: [],
                _initialized: false,
                weekDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],

                init() {
                    // Restore from hidden payload if exists
                    try {
                        const hidden = document.getElementById('tickets_json');
                        if (hidden && hidden.value) {
                            const data = JSON.parse(hidden.value);
                            if (Array.isArray(data) && data.length > 0) {
                                this.tickets = data.map(t => ({
                                    uid: crypto.randomUUID(),
                                    name: t.name || '',
                                    scope: t.scope || '',
                                    price: t.price || '',
                                    age_category: t.age_category || '',
                                    is_active: t.is_active ?? true,
                                    max_per_order: t.max_per_order || '',
                                    frequency: t.frequency || '',
                                    weekly_days: t.weekly_days || [],
                                    monthly_days: t.monthly_days || []
                                }));
                            }
                        }
                    } catch(e) {
                        console.error('Error loading tickets:', e);
                    }

                    this._initialized = true;
                },

                addTicket() {
                    this.tickets.push({
                        uid: crypto.randomUUID(),
                        name: '',
                        scope: '',
                        price: '',
                        age_category: '',
                        is_active: true,
                        max_per_order: '',
                        frequency: '',
                        weekly_days: [],
                        monthly_days: []
                    });
                    this.syncPayload();
                },

                removeTicket(index) {
                    this.tickets.splice(index, 1);
                    this.syncPayload();
                },

                duplicateTicket(index) {
                    if (!this.tickets[index]) return;

                    const original = this.tickets[index];
                    const clone = {
                        uid: crypto.randomUUID(),
                        name: original.name,
                        scope: original.scope,
                        price: original.price,
                        age_category: original.age_category,
                        is_active: original.is_active,
                        max_per_order: original.max_per_order,
                        frequency: original.frequency,
                        weekly_days: [...original.weekly_days],
                        monthly_days: [...original.monthly_days]
                    };

                    this.tickets.splice(index + 1, 0, clone);
                    this.syncPayload();
                },

                onFrequencyChange(ticket) {
                    // Clear conditional fields when frequency changes
                    if (ticket.frequency !== 'weekly') {
                        ticket.weekly_days = [];
                    }
                    if (ticket.frequency !== 'monthly') {
                        ticket.monthly_days = [];
                    }
                },

                toggleMonthDay(ticket, dayIndex) {
                    const currentDays = [...ticket.monthly_days];
                    const idx = currentDays.indexOf(dayIndex);

                    if (idx > -1) {
                        currentDays.splice(idx, 1);
                    } else {
                        currentDays.push(dayIndex);
                    }

                    ticket.monthly_days = currentDays.sort((a, b) => a - b);
                    this.syncPayload();
                },

                toggleWeekDay(ticket, dayIndex) {
                    const idx = ticket.weekly_days.indexOf(dayIndex);
                    if (idx > -1) {
                        ticket.weekly_days.splice(idx, 1);
                    } else {
                        ticket.weekly_days.push(dayIndex);
                    }
                    ticket.weekly_days.sort((a, b) => a - b);

                    ticket.weekly_days = [...ticket.weekly_days];

                    this.syncPayload();
                },

                serialize() {
                    return JSON.stringify(
                        this.tickets.map(t => {
                            const base = {
                                name: t.name || '',
                                scope: t.scope || '',
                                price: parseFloat(t.price) || 0,
                                age_category: t.age_category || '',
                                is_active: !!t.is_active,
                                max_per_order: parseInt(t.max_per_order) || null,
                                frequency: t.frequency || ''
                            };

                            if (t.frequency === 'weekly') {
                                base.weekly_days = t.weekly_days || [];
                            }

                            if (t.frequency === 'monthly') {
                                base.monthly_days = t.monthly_days || [];
                            }

                            return base;
                        })
                    );
                },

                syncPayload() {
                    if (!this._initialized) return;
                    // Alpine will handle this via x-bind:value on the hidden input
                }
            };
        }
    </script>
@endpush