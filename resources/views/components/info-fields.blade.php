@props([
    'name' => 'info_fields',
    'label' => 'Additional Information',
    'value' => '[]',
    'required' => false,
    'error' => null
])

@php
    $encodedValue = is_array($value) ? json_encode($value) : (is_string($value) ? $value : '[]');
@endphp

<div id="info_fields_component"
     x-data="infoFieldsBuilder()"
     x-init="init()"
     x-cloak
     class="card p-4 rounded-xl shadow-sm border border-gray-200">

    <h3 class="text-lg font-semibold mb-3">{{ $label }}</h3>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
        <p class="text-sm text-blue-800">
            <strong>Instructions:</strong> Add information fields with titles such as:
            <span class="font-semibold">Precauții</span>,
            <span class="font-semibold">Ingrediente</span>,
            <span class="font-semibold">Mod de utilizare</span>,
            <span class="font-semibold">Contraindicații</span>, etc.
        </p>
    </div>

    <!-- List of existing fields -->
    <div class="space-y-4 mb-4" x-show="fields.length > 0">
        <template x-for="(field, index) in fields" :key="field.uid">
            <div class="border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-700">
                        Info Field #<span x-text="index + 1"></span>
                    </h4>
                    <button
                            type="button"
                            class="btn btn_danger btn_sm"
                            @click.prevent="removeField(index)"
                            title="Remove this field">
                        <i class="la la-trash"></i> Remove
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <!-- Title Input -->
                    <div>
                        <label class="label block mb-2">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input
                                type="text"
                                class="form-control"
                                x-model="field.title"
                                @input="syncPayload()"
                                placeholder="e.g., Ingrediente, Precauții"
                                required>
                        <small class="text-gray-500">The section title (e.g., "Ingredients")</small>
                    </div>

                    <!-- Description Input -->
                    <div>
                        <label class="label block mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea
                                class="form-control"
                                rows="4"
                                x-model="field.description"
                                @input="syncPayload()"
                                placeholder="Enter detailed description..."
                                required></textarea>
                        <small class="text-gray-500">The content for this section</small>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty state -->
    <div x-show="fields.length === 0" class="text-center py-8 text-gray-500">
        <i class="la la-info-circle text-4xl mb-2"></i>
        <p>No information fields added yet. Click the button below to add one.</p>
    </div>

    <!-- Add Button -->
    <div class="mt-4">
        <button
                type="button"
                class="btn btn_primary w-full"
                @click.prevent="addField()">
            <i class="la la-plus-circle"></i> Add a new info field
        </button>
    </div>

    <!-- Hidden Input -->
    <input
            type="hidden"
            name="{{ $name }}"
            id="{{ $name }}"
            x-model="hiddenValue"
            value="{{ $encodedValue }}">

    @if($error)
        <small class="block mt-2 text-red-500">{{ $error }}</small>
    @endif
</div>

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .btn_sm { padding: .35rem .6rem; font-size: .825rem; }
    </style>
@endpush

@push('scripts')
    <script>
        function infoFieldsBuilder() {
            return {
                fields: [],
                hiddenValue: '[]',
                _initialized: false,
                init() {
                    // Restore from hidden input if exists
                    try {
                        const hidden = document.getElementById('{{ $name }}');
                        if (hidden && hidden.value) {
                            const data = JSON.parse(hidden.value);
                            if (Array.isArray(data)) {
                                this.fields = data.map(item => ({
                                    uid: crypto.randomUUID(),
                                    title: item.key || item.title || '',
                                    description: item.value || item.description || ''
                                }));
                            }
                        }
                    } catch(e) {
                        console.error('Error parsing info fields:', e);
                        this.fields = [];
                    }

                    this._initialized = true;
                    this.syncPayload();
                },

                addField() {
                    this.fields.push({
                        uid: crypto.randomUUID(),
                        key: '',
                        value: ''
                    });
                    this.syncPayload();
                },

                removeField(index) {
                    if (confirm('Are you sure you want to remove this field?')) {
                        this.fields.splice(index, 1);
                        this.syncPayload();
                    }
                },

                syncPayload() {
                    if (!this._initialized) return;

                    try {
                        const payload = this.fields.map(field => ({
                            key: field.title || '',
                            value: field.description || ''
                        }));

                        this.hiddenValue = JSON.stringify(payload);
                    } catch(e) {
                        console.error('Error syncing payload:', e);
                    }
                }
            }
        }
    </script>
@endpush
