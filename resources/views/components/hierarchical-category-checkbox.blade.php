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
    $initialValue = is_string($value) ? json_decode($value, true) : (array)$value;

    // Ensure data is properly formatted
    $formattedData = [];
    foreach ($data as $item) {
        $formattedData[] = [
            'id' => $item['id'] ?? $item['value'] ?? null,
            'name' => $item['name'] ?? 'Unknown',
            'parent_id' => $item['parent_id'] ?? null
        ];
    }
@endphp

<div id="{{ $componentId }}"
     x-data="hierarchicalCategories{{ $componentId }}()"
     x-init="init()"
     class="space-y-3">

    <!-- Label -->
    <div class="flex items-center justify-between mb-2">
        <label class="label block font-medium text-gray-800">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
        <button type="button"
                class="text-xs text-blue-600 hover:text-blue-800"
                @click.prevent="clearAll()"
                x-show="selected.length > 0">
            Clear All
        </button>
    </div>

    <!-- Error Message -->
    @if($error)
        <div class="text-red-500 text-sm mb-2">{{ $error }}</div>
    @endif

    <!-- Search Box -->
    <div class="mb-3">
        <input type="text"
               class="form-control text-sm"
               placeholder="Search categories..."
               x-model="searchQuery"
               @input="filterCategories()">
    </div>

    <!-- Selected Count -->
    <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
        <span><strong x-text="selectedCount"></strong> selected</span>
        <span class="text-xs text-gray-400" x-show="!loading">
            <span x-text="totalCategories"></span> total categories
        </span>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-4 text-gray-500">
        Loading categories...
    </div>

    <!-- Category Tree -->
    <div x-show="!loading"
         class="border border-gray-200 rounded-lg p-3 bg-white max-h-96 overflow-y-auto">

        <template x-if="filteredTree.length === 0 && !loading">
            <p class="text-gray-500 text-sm">
                <span x-show="searchQuery.length > 0">No categories match your search</span>
                <span x-show="searchQuery.length === 0">No categories available</span>
            </p>
        </template>

        <template x-for="category in filteredTree" :key="category.id">
            <div>
                <div x-html="renderCategory(category, 0)"></div>
            </div>
        </template>
    </div>

    <!-- Hidden Inputs -->
    <template x-for="id in selected" :key="id">
        <input type="hidden" :name="`{{ $name }}[]`" :value="id">
    </template>

    <!-- Debug Info (remove in production) -->
    <details class="mt-2 text-xs text-gray-500">
        <summary class="cursor-pointer">Debug Info</summary>
        <pre x-text="JSON.stringify({
            totalRaw: rawData.length,
            totalTree: tree.length,
            totalFiltered: filteredTree.length,
            selected: selected
        }, null, 2)" class="mt-2 p-2 bg-gray-100 rounded"></pre>
    </details>
</div>

@push('styles')
    <style>
        #{{ $componentId }} .category-row {
            display: flex;
            align-items: center;
            padding: 0.375rem 0.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s;
        }

        #{{ $componentId }} .category-row:hover {
             background-color: #f3f4f6;
         }

        #{{ $componentId }} .category-children {
             margin-left: 1.5rem;
             border-left: 2px solid #e5e7eb;
             padding-left: 0.75rem;
             margin-top: 0.25rem;
             animation: slideDown 0.2s ease-out;
         }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #{{ $componentId }} .has-children-indicator {
             width: 16px;
             height: 16px;
             display: inline-flex;
             align-items: center;
             justify-content: center;
             font-size: 10px;
             color: #9ca3af;
             margin-right: 0.25rem;
             flex-shrink: 0;
         }

        #{{ $componentId }} .category-checkbox {
             width: 1rem;
             height: 1rem;
             margin-right: 0.5rem;
             cursor: pointer;
             flex-shrink: 0;
         }

        #{{ $componentId }} .category-label {
             flex: 1;
             cursor: pointer;
             user-select: none;
             font-size: 0.875rem;
         }

        #{{ $componentId }} .category-count {
             font-size: 0.75rem;
             color: #9ca3af;
             margin-left: 0.5rem;
             background-color: #f3f4f6;
             padding: 0.125rem 0.375rem;
             border-radius: 0.25rem;
         }
    </style>
@endpush
@push('scripts')
    <script>
        function hierarchicalCategories{{ $componentId }}() {
            return {
                rawData: @json($formattedData),
                selected: @json($initialValue),
                searchQuery: '',
                filteredTree: [],
                tree: [],
                loading: true,

                init() {
                    console.log('{{ $componentId }}: Initializing with', this.rawData.length, 'categories');

                    try {
                        this.tree = this.buildTree(this.rawData);
                        this.filteredTree = this.tree;
                        this.loading = false;
                    } catch (error) {
                        console.error('{{ $componentId }}: Error during init:', error);
                        this.loading = false;
                    }
                },

                buildTree(flatData) {
                    if (!flatData || flatData.length === 0) {
                        return [];
                    }

                    const map = {};
                    const roots = [];

                    // Create map of all items
                    flatData.forEach(item => {
                        if (!item.id) return;

                        map[item.id] = {
                            id: item.id,
                            name: item.name || 'Unknown',
                            parent_id: item.parent_id,
                            children: []
                        };
                    });

                    // Build tree structure
                    Object.values(map).forEach(item => {
                        if (item.parent_id && map[item.parent_id]) {
                            map[item.parent_id].children.push(item);
                        } else if (!item.parent_id || item.parent_id === null) {
                            roots.push(item);
                        }
                    });

                    // Sort by name
                    const sortChildren = (items) => {
                        items.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                        items.forEach(item => {
                            if (item.children.length > 0) {
                                sortChildren(item.children);
                            }
                        });
                    };

                    sortChildren(roots);
                    return roots;
                },

                toggleCategory(categoryData) {
                    const category = typeof categoryData === 'string'
                        ? JSON.parse(categoryData)
                        : categoryData;

                    const isCurrentlySelected = this.selected.includes(category.id);

                    if (isCurrentlySelected) {
                        // Uncheck: remove ONLY this category (and its descendants)
                        const allDescendants = this.getAllDescendants(category);
                        this.selected = this.selected.filter(id => !allDescendants.includes(id));
                    } else {
                        // Check: add ONLY this category (NOT its children)
                        this.selected.push(category.id);
                    }

                    console.log('{{ $componentId }}: Toggled', category.name, '→', !isCurrentlySelected);
                    console.log('{{ $componentId }}: Selected:', this.selected);
                },

                getAllDescendants(category) {
                    let descendants = [category.id];

                    if (category.children && category.children.length > 0) {
                        category.children.forEach(child => {
                            descendants = descendants.concat(this.getAllDescendants(child));
                        });
                    }

                    return descendants;
                },

                clearAll() {
                    this.selected = [];
                },

                filterCategories() {
                    if (!this.searchQuery.trim()) {
                        this.filteredTree = this.tree;
                        return;
                    }

                    const query = this.searchQuery.toLowerCase();

                    const filterTree = (items) => {
                        return items.reduce((acc, item) => {
                            const nameMatch = (item.name || '').toLowerCase().includes(query);
                            const filteredChildren = item.children && item.children.length > 0
                                ? filterTree(item.children)
                                : [];

                            if (nameMatch || filteredChildren.length > 0) {
                                acc.push({
                                    ...item,
                                    children: filteredChildren
                                });
                            }

                            return acc;
                        }, []);
                    };

                    this.filteredTree = filterTree(this.tree);
                },

                renderCategory(category, level) {
                    const hasChildren = category.children && category.children.length > 0;
                    const isSelected = this.selected.includes(category.id);
                    const indent = level * 1.5;
                    const categoryJson = JSON.stringify(category).replace(/"/g, '&quot;');

                    let html = `<div class="category-row" style="padding-left: ${indent}rem;">`;

                    // Visual indicator for categories with children
                    if (hasChildren) {
                        html += `<span class="has-children-indicator">▸</span>`;
                    } else {
                        html += `<span style="width: 16px; display: inline-block;"></span>`;
                    }

                    // Checkbox
                    html += `
                <input type="checkbox"
                       class="category-checkbox"
                       ${isSelected ? 'checked' : ''}
                       @click.stop
                       @change="toggleCategory('${categoryJson}')">
            `;

                    // Label with count
                    html += `
                <span class="category-label"
                      @click.stop="toggleCategory('${categoryJson}')">
                    ${this.escapeHtml(category.name)}
            `;

                    if (hasChildren) {
                        html += `<span class="category-count">${category.children.length} sub</span>`;
                    }

                    html += `</span></div>`;

                    // Show children if parent is selected
                    if (hasChildren && isSelected) {
                        html += `<div class="category-children">`;
                        category.children.forEach(child => {
                            html += this.renderCategory(child, level + 1);
                        });
                        html += `</div>`;
                    }

                    return html;
                },

                escapeHtml(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, m => map[m]);
                },

                get selectedCount() {
                    return this.selected.length;
                },

                get totalCategories() {
                    const countAll = (items) => {
                        let count = items.length;
                        items.forEach(item => {
                            if (item.children && item.children.length > 0) {
                                count += countAll(item.children);
                            }
                        });
                        return count;
                    };
                    return countAll(this.tree);
                }
            }
        }
    </script>
@endpush