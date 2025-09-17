@props([
    'name' => '',
    'label' => '',
    'value' => [],
    'data' => [],
    'subcategorySource' => [],
    'filterSource' => [],
    'required' => false
])

<div class="form-group">
    <label class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>

    <div class="three-level-hierarchical-container" data-name="{{ $name }}">
        <div class="parent-categories mb-3">
            <h6 class="text-muted mb-2">Parent Categories</h6>
            @foreach($data as $parent)
                <div class="form-check mb-2">
                    <input
                            class="form-check-input parent-checkbox"
                            type="checkbox"
                            id="parent_{{ $parent['id'] }}"
                            value="{{ $parent['id'] }}"
                            data-parent-id="{{ $parent['id'] }}"
                            data-should-check="false"
                    >
                    <label class="form-check-label fw-bold" for="parent_{{ $parent['id'] }}">
                        {{ $parent['name'] }}
                    </label>
                    <div class="subcategories-container ms-4 mt-2" id="subcategories_{{ $parent['id'] }}" style="display:none;">
                        <h6 class="text-muted mb-2">Subcategories</h6>
                        <div class="subcategories-list"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="hidden-input-bag"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.three-level-hierarchical-container[data-name="{{ $name }}"]');
        const bag = container.querySelector('.hidden-input-bag');
        const subcategorySource = @json($subcategorySource);
        const tableName = subcategorySource?.source?.[2] || 'venue_categories';
        let selectedValues = Array.isArray(@json($value)) ? @json($value) : [];

        function rebuildBag() {
            bag.innerHTML = '';
            selectedValues.forEach(function(val) {
                if (!val) return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '{{ $name }}[]';
                input.value = String(val);
                bag.appendChild(input);
            });
        }

        function addValue(uuid) {
            uuid = String(uuid);
            if (!selectedValues.includes(uuid)) {
                selectedValues.push(uuid);
                rebuildBag();
            }
        }

        function removeValue(uuid) {
            uuid = String(uuid);
            const i = selectedValues.indexOf(uuid);
            if (i !== -1) {
                selectedValues.splice(i, 1);
                rebuildBag();
            }
        }

        container.addEventListener('change', async function(e) {
            if (e.target.classList.contains('parent-checkbox')) {
                const parentId = e.target.dataset.parentId;
                const subcategoriesContainer = document.getElementById('subcategories_' + parentId);
                if (e.target.checked) {
                    addValue(parentId);
                    subcategoriesContainer.style.display = 'block';
                    await loadSubcategories(parentId, subcategoriesContainer.querySelector('.subcategories-list'));
                } else {
                    removeValue(parentId);
                    subcategoriesContainer.style.display = 'none';
                    removeChildrenOfParent(parentId);
                }
                return;
            }

            if (e.target.classList.contains('subcategory-checkbox')) {
                const subcategoryId = e.target.dataset.subcategoryId;
                const filtersContainer = document.getElementById('filters_' + subcategoryId);
                if (e.target.checked) {
                    addValue(subcategoryId);
                    if (filtersContainer) {
                        filtersContainer.style.display = 'block';
                        await loadFilters(subcategoryId, filtersContainer.querySelector('.filters-list'));
                    }
                } else {
                    removeValue(subcategoryId);
                    if (filtersContainer) filtersContainer.style.display = 'none';
                    removeFilterSelections(subcategoryId);
                }
                return;
            }

            if (e.target.classList.contains('filter-checkbox')) {
                const id = e.target.dataset.filterId || e.target.value;
                if (e.target.checked) addValue(id); else removeValue(id);
                return;
            }
        });

        async function loadSubcategories(parentId, listEl) {
            try {
                const res = await fetch(`/api/subcategories/${tableName}?parent_id=${encodeURIComponent(parentId)}&level=2`);
                const subs = res.ok ? await res.json() : [];
                listEl.innerHTML = subs.length ? subs.map(s => {
                    const checked = selectedValues.includes(String(s.id)) ? 'checked' : '';
                    return `
                    <div class="form-check mb-2">
                        <input class="form-check-input subcategory-checkbox"
                               type="checkbox"
                               id="subcategory_${s.id}"
                               value="${s.id}"
                               data-subcategory-id="${s.id}"
                               data-parent-id="${parentId}"
                               ${checked}>
                        <label class="form-check-label" for="subcategory_${s.id}">${s.name}</label>
                        <div class="filters-container ms-4 mt-2" id="filters_${s.id}" style="display:${checked ? 'block':'none'};">
                            <h6 class="text-muted mb-2">Filter Options</h6>
                            <div class="filters-list"></div>
                        </div>
                    </div>`;
                }).join('') : '<div class="text-muted fst-italic">No subcategories found</div>';

                for (const s of subs) {
                    if (selectedValues.includes(String(s.id))) {
                        const fc = document.getElementById('filters_' + s.id);
                        if (fc) await loadFilters(s.id, fc.querySelector('.filters-list'));
                    }
                }
            } catch {
                listEl.innerHTML = '<div class="text-danger">Error loading subcategories</div>';
            }
        }

        async function loadFilters(subcategoryId, listEl) {
            try {
                const res = await fetch(`/api/subcategories/${tableName}?parent_id=${encodeURIComponent(subcategoryId)}&level=3`);
                const filters = res.ok ? await res.json() : [];
                listEl.innerHTML = filters.length ? filters.map(f => {
                    const checked = selectedValues.includes(String(f.id)) ? 'checked' : '';
                    return `
                    <div class="form-check mb-1">
                        <input class="form-check-input filter-checkbox"
                               type="checkbox"
                               id="filter_${f.id}"
                               value="${f.id}"
                               data-filter-id="${f.id}"
                               data-subcategory-id="${subcategoryId}"
                               ${checked}>
                        <label class="form-check-label" for="filter_${f.id}">${f.name}</label>
                    </div>`;
                }).join('') : '<div class="text-muted fst-italic">No filters found</div>';
            } catch {
                listEl.innerHTML = '<div class="text-danger">Error loading filters</div>';
            }
        }

        function removeChildrenOfParent(parentId) {
            const container = document.getElementById('subcategories_' + parentId);
            if (!container) return;
            container.querySelectorAll('.subcategory-checkbox:checked').forEach(cb => removeValue(cb.value));
            container.querySelectorAll('.filter-checkbox:checked').forEach(cb => removeValue(cb.value));
        }

        function removeFilterSelections(subcategoryId) {
            const filtBox = document.getElementById('filters_' + subcategoryId);
            if (!filtBox) return;
            filtBox.querySelectorAll('.filter-checkbox:checked').forEach(cb => removeValue(cb.value));
        }

        rebuildBag();

        const form = container.closest('form');
        if (form) form.addEventListener('submit', rebuildBag);
    });
</script>

<style>
    .three-level-hierarchical-container .form-check { border-left: 2px solid #e9ecef; padding-left: 15px; margin-left: 10px; }
    .three-level-hierarchical-container .parent-categories > .form-check { border-left: 3px solid #007bff; background-color: #f8f9fa; padding: 10px 15px; border-radius: 5px; margin-bottom: 15px; }
    .three-level-hierarchical-container .subcategories-container { background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px; margin-top: 10px; }
    .three-level-hierarchical-container .filters-container { background-color: #f1f3f4; border: 1px solid #e9ecef; border-radius: 3px; padding: 8px; margin-top: 8px; }
    .three-level-hierarchical-container h6 { font-size: 0.875rem; font-weight: 600; color: #6c757d; margin-bottom: 8px; }
</style>
