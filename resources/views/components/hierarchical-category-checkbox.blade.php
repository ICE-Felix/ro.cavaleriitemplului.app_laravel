@props([
    'name' => 'categories',
    'label' => 'Categories',
    'data' => [],
    'value' => [],
    'error' => null,
    'required' => false
])

@php
    $componentId = 'hierarchy_' . bin2hex(random_bytes(4));
    $initialValue = is_string($value) ? (json_decode($value, true) ?: []) : (array) $value;

    // Normalize input to: { id, name, parent_ids: [] }
    $formattedData = [];
    foreach ($data as $item) {
        $pid = $item['parent_ids'] ?? null;
        if (is_null($pid)) {
            // Backward compat: single parent_id → array or empty
            $single = $item['parent_id'] ?? null;
            $pid = $single ? [$single] : [];
        }

        // Always an array of strings
        $parentIds = array_values(array_filter(array_map(
            fn($x) => is_null($x) ? null : (string)$x,
            (array)$pid
        ), fn($x) => $x !== null && $x !== ''));

        $formattedData[] = [
            'id'         => (string)($item['id'] ?? $item['value'] ?? ''),
            'name'       => (string)($item['name'] ?? 'Unknown'),
            'parent_ids' => $parentIds,
        ];
    }
@endphp

<div id="{{ $componentId }}"
     x-data="hierarchicalCategories{{ $componentId }}()"
     x-init="init()"
     class="space-y-3">

    <div class="flex items-center justify-between mb-2">
        <label class="label block font-medium text-gray-800">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>

        <div class="flex items-center gap-3">
            <span class="text-xs text-amber-600" x-text="message" x-show="message"></span>
            <button type="button"
                    class="text-xs text-blue-600 hover:text-blue-800"
                    @click.prevent="clearAll()"
                    x-show="selected.length > 0">
                Clear All
            </button>
        </div>
    </div>

    @if($error)
        <div class="text-red-500 text-sm mb-2">{{ $error }}</div>
    @endif

    <div class="mb-3">
        <input type="text"
               class="form-control text-sm"
               placeholder="Search categories..."
               x-model="searchQuery"
               @input="filterCategories()">
    </div>

    <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
        <span><strong x-text="selectedCount"></strong> selected</span>
        <div class="text-xs text-gray-500">
            <span x-show="!loading"><span x-text="totalCategories"></span> total</span>
            <span class="ml-2 text-amber-600">Levels 0–2: single; Level 3+ auto-selected under chosen Level 2</span>
        </div>
    </div>

    <div x-show="loading" class="text-center py-4 text-gray-500">Loading categories...</div>

    <div x-show="!loading"
         class="border border-gray-200 rounded-lg p-3 bg-white max-h-96 overflow-y-auto">

        <template x-if="filteredTree.length === 0 && !loading">
            <p class="text-gray-500 text-sm">
                <span x-show="searchQuery.length > 0">No categories match your search</span>
                <span x-show="searchQuery.length === 0">No categories available</span>
            </p>
        </template>

        <!-- Render tree -->
        <template x-for="node in filteredTree" :key="node.key">
            <div x-html="renderBranch(node, 0)"></div>
        </template>
    </div>

    <!-- Hidden inputs -->
    <template x-for="id in selected" :key="id">
        <input type="hidden" :name="`{{ $name }}[]`" :value="id">
    </template>
</div>

@push('styles')
    <style>
        #{{ $componentId }} .row {
            display:flex;
            align-items:center;
            padding:.375rem .5rem;
            border-radius:.375rem;
            transition: background-color 0.2s;
        }
        #{{ $componentId }} .row:hover { background:#f3f4f6; }
        #{{ $componentId }} .row.disabled {
             opacity:.5;
             cursor:not-allowed;
             background:#fafafa;
         }
        #{{ $componentId }} .kids {
             margin-left:1.5rem;
             border-left:2px solid #e5e7eb;
             padding-left:.75rem;
             margin-top:.25rem;
         }
        #{{ $componentId }} .dot {
             width:16px;
             height:16px;
             display:inline-flex;
             align-items:center;
             justify-content:center;
             font-size:10px;
             color:#9ca3af;
             margin-right:.25rem;
             flex-shrink:0;
         }
        #{{ $componentId }} .chk {
             width:1rem;
             height:1rem;
             margin-right:.5rem;
             cursor:pointer;
             flex-shrink:0;
         }
        #{{ $componentId }} .chk:disabled { cursor:not-allowed; }
        #{{ $componentId }} .lbl {
             flex:1;
             cursor:pointer;
             user-select:none;
             font-size:.875rem;
         }
        #{{ $componentId }} .lbl.disabled { cursor:not-allowed; }
        #{{ $componentId }} .count {
             font-size:.75rem;
             color:#9ca3af;
             margin-left:.5rem;
             background:#f3f4f6;
             padding:.125rem .375rem;
             border-radius:.25rem;
         }
        #{{ $componentId }} .level-badge {
             font-size:.65rem;
             padding:.125rem .375rem;
             border-radius:.25rem;
             margin-left:.25rem;
             font-weight:600;
         }
        #{{ $componentId }} .level-0 { background:#dbeafe; color:#1e40af; }
        #{{ $componentId }} .level-1 { background:#ddd6fe; color:#5b21b6; }
        #{{ $componentId }} .level-2 { background:#fce7f3; color:#9f1239; }
        #{{ $componentId }} .level-3 { background:#fed7aa; color:#9a3412; }
        #{{ $componentId }} .level-4plus { background:#d1fae5; color:#065f46; }
    </style>
@endpush

@push('scripts')
    <script>
        function hierarchicalCategories{{ $componentId }}() {
            return {
                // ---- state ----
                raw: @json($formattedData),
                selected: @json(array_values(array_unique(array_map('strval', $initialValue)))),

                searchQuery: '',
                loading: true,
                tree: [],
                filteredTree: [],
                message: '',

                // Levels 0..2 are exclusive (single selection)
                exclusiveLevels: new Set([0, 1, 2]),
                selectedByLevel: {},  // { 0: id, 1: id, 2: id }

                // graph
                byId: {},
                childrenById: {},

                // ---- lifecycle ----
                init() {
                    try {
                        this.indexRaw();
                        this.buildForest();
                        this.filteredTree = this.tree;
                        this.loading = false;

                        this.rebuildSelectedByLevel();

                        this.$nextTick(() => {
                            this.attachDelegatedHandlers();
                            this.applyDisabledState();
                        });
                    } catch (e) {
                        console.error('init error', e);
                        this.loading = false;
                    }
                },

                rebuildSelectedByLevel() {
                    this.selectedByLevel = {};
                    this.selected.forEach(id => {
                        const level = this.getNodeLevel(id);
                        if (this.exclusiveLevels.has(level) && !this.selectedByLevel[level]) {
                            this.selectedByLevel[level] = id;
                        }
                    });
                },

                getNodeLevel(id) {
                    const node = this.byId[id];
                    if (!node) return 0;
                    let level = 0;
                    let cur = node;
                    let guard = 0;
                    while (cur && cur.parent_ids && cur.parent_ids.length > 0 && guard++ < 64) {
                        level++;
                        const p = this.byId[cur.parent_ids[0]];
                        cur = p;
                    }
                    return level;
                },

                // ---- graph ----
                indexRaw() {
                    this.byId = {};
                    this.childrenById = {};
                    this.raw.forEach(item => {
                        if (!item.id) return;
                        this.byId[item.id] = { id: item.id, name: item.name || 'Unknown', parent_ids: item.parent_ids || [] };
                    });
                    Object.values(this.byId).forEach(n => {
                        (n.parent_ids || []).forEach(pid => {
                            if (!this.childrenById[pid]) this.childrenById[pid] = new Set();
                            this.childrenById[pid].add(n.id);
                        });
                    });
                },

                buildForest() {
                    const roots = Object.values(this.byId).filter(n => !n.parent_ids || n.parent_ids.length === 0);
                    const make = (id) => {
                        const base = this.byId[id];
                        if (!base) return null;
                        const kids = Array.from(this.childrenById[id] || []);
                        return {
                            key: `${id}-${Math.random().toString(36).slice(2)}`,
                            id: base.id,
                            name: base.name,
                            children: kids.map(make).filter(Boolean)
                        };
                    };
                    this.tree = roots.map(r => make(r.id)).filter(Boolean);

                    // orphans (all parents missing)
                    Object.keys(this.byId).forEach(id => {
                        const n = this.byId[id];
                        const hasParents = n.parent_ids && n.parent_ids.length > 0;
                        if (hasParents) {
                            const parentsMissing = n.parent_ids.every(pid => !this.byId[pid]);
                            if (parentsMissing) {
                                const b = make(id);
                                if (b) this.tree.push(b);
                            }
                        }
                    });
                },

                renderBranch(node, level) {
                    const hasChildren = node.children && node.children.length > 0;
                    const isSelected = this.selected.includes(node.id);
                    const isDisabled = this.isDisabled(node.id, level);

                    const levelClass = level <= 3 ? `level-${level}` : 'level-4plus';
                    const levelLabel = level === 0 ? 'L0' : level === 1 ? 'L1' : level === 2 ? 'L2' : level === 3 ? 'L3' : `L${level}+`;

                    let html = `<div class="row ${isDisabled ? 'disabled' : ''}" style="padding-left:${level * 1.5}rem;">`;
                    html += hasChildren ? `<span class="dot">▸</span>` : `<span style="width:16px;display:inline-block;"></span>`;

                    html += `
                <input type="checkbox"
                    class="chk"
                    data-id="${node.id}"
                    data-level="${level}"
                    ${isSelected ? 'checked' : ''}
                    ${isDisabled ? 'disabled' : ''}
                >
            `;

                    html += `
                <span class="lbl ${isDisabled ? 'disabled' : ''}" data-role="label" data-id="${node.id}" data-level="${level}">
                    ${this.escape(node.name)}
                    <span class="level-badge ${levelClass}">${levelLabel}</span>
                    ${hasChildren ? `<span class="count">${node.children.length} sub</span>` : ''}
                </span>
            `;

                    html += `</div>`;

                    // show children only when selected (keeps tree compact like your original)
                    if (hasChildren && isSelected) {
                        html += `<div class="kids">`;
                        node.children.forEach(child => { html += this.renderBranch(child, level + 1); });
                        html += `</div>`;
                    }

                    return html;
                },

                // ---- interactions ----
                attachDelegatedHandlers() {
                    // checkbox
                    this.$el.addEventListener('change', (e) => {
                        const t = e.target;
                        if (t && t.classList && t.classList.contains('chk')) {
                            const id = t.dataset.id;
                            const level = parseInt(t.dataset.level, 10) || 0;
                            this.handleToggle(id, level, t.checked);
                        }
                    });
                    // label
                    this.$el.addEventListener('click', (e) => {
                        const t = e.target;
                        if (t && t.dataset && t.dataset.role === 'label') {
                            const id = t.dataset.id;
                            const level = parseInt(t.dataset.level, 10) || 0;
                            const row = t.closest('.row');
                            const chk = row ? row.querySelector('.chk') : null;
                            if (!chk) return;
                            if (chk.disabled) {
                                this.bumpMessage(`Only one selection allowed at Level ${level}. Uncheck the current one first.`);
                                return;
                            }
                            chk.checked = !chk.checked;
                            this.handleToggle(id, level, chk.checked);
                        }
                    });
                },

                handleToggle(id, level, willSelect) {
                    // Enforce single at levels 0–2
                    if (willSelect && this.exclusiveLevels.has(level)) {
                        const already = this.selectedByLevel[level];
                        if (already && already !== id) {
                            this.bumpMessage(`Only one selection allowed at Level ${level}. Uncheck "${this.byId[already]?.name || already}" first.`);
                            this.syncCheckbox(id, level, false);
                            return;
                        }
                        this.selectedByLevel[level] = id;
                    }

                    if (willSelect) {
                        // Always include the clicked node
                        if (!this.selected.includes(id)) this.selected.push(id);

                        if (level === 2) {
                            // When choosing a new L2, keep deep selections only from this branch
                            const validDeep = new Set([id, ...this.descendantsOf(id)]);
                            this.selected = this.selected.filter(sid => {
                                const lvl = this.getNodeLevel(sid);
                                return lvl < 2 || validDeep.has(sid);
                            });
                        } else if (level >= 3) {
                            // L3+ behave independently: include ONLY this node + its own descendants
                            const desc = this.descendantsOf(id);
                            this.addToSelected(desc); // id already added above
                        }
                    } else {
                        // Deselect: remove this node and all its descendants
                        const all = new Set([id, ...this.descendantsOf(id)]);
                        this.selected = this.selected.filter(sid => !all.has(sid));

                        // Release exclusivity lock if applicable
                        if (this.exclusiveLevels.has(level) && this.selectedByLevel[level] === id) {
                            delete this.selectedByLevel[level];
                        }
                    }

                    // Rebuild exclusives from the remaining selection (keeps L0–L2 tidy)
                    this.rebuildSelectedByLevel();

                    // Refresh UI
                    this.applyDisabledState();
                    this.filteredTree = JSON.parse(JSON.stringify(this.filteredTree));
                },

                // ---- bulk helpers ----
                findAncestorAtLevel(id, targetLevel) {
                    let node = this.byId[id];
                    if (!node) return null;
                    let cur = node, guard = 0;
                    while (guard++ < 64) {
                        const lvl = this.getNodeLevel(cur.id);
                        if (lvl === targetLevel) return cur.id;
                        if (!cur.parent_ids || cur.parent_ids.length === 0) return null;
                        const p = this.byId[cur.parent_ids[0]];
                        if (!p) return null;
                        cur = p;
                    }
                    return null;
                },

                collectNodesUnderAncestorAtLevel(ancestorId, levelK) {
                    if (!ancestorId) return [];
                    const desc = this.descendantsOf(ancestorId);
                    return desc.filter(d => this.getNodeLevel(d) === levelK);
                },

                selectAllDescendantsFromLevel(id, minLevel) {
                    const desc = this.descendantsOf(id);
                    const toAdd = desc.filter(d => this.getNodeLevel(d) >= minLevel);
                    this.addToSelected([id, ...toAdd]);
                },

                addToSelected(ids) {
                    const set = new Set(this.selected);
                    ids.forEach(x => set.add(x));
                    this.selected = Array.from(set);
                },

                // ---- enable/disable ----
                applyDisabledState() {
                    const inputs = this.$el.querySelectorAll('.chk');
                    inputs.forEach(inp => {
                        const lvl = parseInt(inp.dataset.level, 10) || 0;
                        const id = inp.dataset.id;
                        let disabled = false;

                        if (this.exclusiveLevels.has(lvl)) {
                            const held = this.selectedByLevel[lvl];
                            if (held && held !== id) disabled = true;
                        }

                        inp.disabled = disabled;
                        const row = inp.closest('.row');
                        row && row.classList.toggle('disabled', disabled);
                    });
                },

                isDisabled(id, level) {
                    if (!this.exclusiveLevels.has(level)) return false;
                    const held = this.selectedByLevel[level];
                    return !!held && held !== id;
                },

                syncCheckbox(id, level, checked) {
                    const sel = `.chk[data-id="${CSS.escape(id)}"][data-level="${level}"]`;
                    const box = this.$el.querySelector(sel);
                    if (box) box.checked = !!checked;
                },

                descendantsOf(id) {
                    const out = new Set();
                    const stack = [id];
                    const seen = new Set([id]);
                    while (stack.length) {
                        const cur = stack.pop();
                        const kids = this.childrenById[cur] ? Array.from(this.childrenById[cur]) : [];
                        for (const c of kids) {
                            if (!seen.has(c)) {
                                seen.add(c);
                                out.add(c);
                                stack.push(c);
                            }
                        }
                    }
                    out.delete(id);
                    return Array.from(out);
                },

                // ---- search & utils ----
                filterCategories() {
                    if (!this.searchQuery.trim()) {
                        this.filteredTree = this.tree;
                        this.filteredTree = JSON.parse(JSON.stringify(this.filteredTree));
                        this.$nextTick(() => this.applyDisabledState());
                        return;
                    }
                    const q = this.searchQuery.toLowerCase();
                    const filterBranch = (node) => {
                        const nameMatch = (node.name || '').toLowerCase().includes(q);
                        const keptKids = (node.children || []).map(filterBranch).filter(Boolean);
                        if (nameMatch || keptKids.length) return { ...node, children: keptKids };
                        return null;
                    };
                    this.filteredTree = this.tree.map(filterBranch).filter(Boolean);
                    this.filteredTree = JSON.parse(JSON.stringify(this.filteredTree));
                    this.$nextTick(() => this.applyDisabledState());
                },

                escape(s) {
                    return (s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
                },

                bumpMessage(text) {
                    this.message = text;
                    setTimeout(() => { this.message = '' }, 2500);
                },

                clearAll() {
                    this.selected = [];
                    this.selectedByLevel = {};
                    this.applyDisabledState();
                    this.filteredTree = JSON.parse(JSON.stringify(this.filteredTree));
                },

                get selectedCount() { return this.selected.length; },
                get totalCategories() { return Object.keys(this.byId).length; }
            }
        }
    </script>
@endpush
