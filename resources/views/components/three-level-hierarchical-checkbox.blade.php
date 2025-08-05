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
    <label class="form-label">{{ $label }}</label>
    @if($required)
        <span class="text-danger">*</span>
    @endif
    
    <div class="three-level-hierarchical-container" data-name="{{ $name }}">
        <!-- Parent Categories -->
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
                    
                    <!-- Subcategories Container -->
                    <div class="subcategories-container ms-4 mt-2" id="subcategories_{{ $parent['id'] }}" style="display: none;">
                        <h6 class="text-muted mb-2">Subcategories</h6>
                        <div class="subcategories-list">
                            <!-- Subcategories will be loaded here -->
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Hidden input to store selected values -->
        <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ json_encode($value) }}">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Three-level hierarchical checkbox initialized');
    
    const container = document.querySelector('.three-level-hierarchical-container[data-name="{{ $name }}"]');
    const hiddenInput = document.getElementById('{{ $name }}');
    const subcategorySource = @json($subcategorySource);
    const filterSource = @json($filterSource);
    
    // Extract table name from subcategory source
    const tableName = subcategorySource?.source?.[2] || 'venue_categories';
    
    let selectedValues = @json($value);
    
    console.log('Initial config:', {
        tableName: tableName,
        subcategorySource: subcategorySource,
        filterSource: filterSource,
        selectedValues: selectedValues
    });
    
    // Handle parent checkbox changes
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('parent-checkbox')) {
            const parentId = e.target.dataset.parentId;
            const subcategoriesContainer = document.getElementById('subcategories_' + parentId);
            
            console.log('Parent checkbox changed:', parentId, 'checked:', e.target.checked);
            
            if (e.target.checked) {
                // Show subcategories container
                subcategoriesContainer.style.display = 'block';
                
                // Load subcategories
                loadSubcategories(parentId, subcategoriesContainer.querySelector('.subcategories-list'));
            } else {
                // Hide subcategories container
                subcategoriesContainer.style.display = 'none';
                
                // Remove all subcategory and filter selections for this parent
                removeChildSelections(parentId);
            }
            
            updateHiddenInput();
        } else if (e.target.classList.contains('subcategory-checkbox')) {
            const subcategoryId = e.target.dataset.subcategoryId;
            const parentId = e.target.dataset.parentId;
            const filtersContainer = document.getElementById('filters_' + subcategoryId);
            
            console.log('Subcategory checkbox changed:', subcategoryId, 'checked:', e.target.checked);
            
            if (e.target.checked) {
                // Show filters container
                if (filtersContainer) {
                    filtersContainer.style.display = 'block';
                    
                    // Load filters
                    loadFilters(subcategoryId, filtersContainer.querySelector('.filters-list'));
                }
            } else {
                // Hide filters container
                if (filtersContainer) {
                    filtersContainer.style.display = 'none';
                }
                
                // Remove all filter selections for this subcategory
                removeFilterSelections(subcategoryId);
            }
            
            updateHiddenInput();
        } else if (e.target.classList.contains('filter-checkbox')) {
            console.log('Filter checkbox changed:', e.target.value, 'checked:', e.target.checked);
            updateHiddenInput();
        }
    });
    
    async function loadSubcategories(parentId, container) {
        console.log('Loading subcategories for parent:', parentId);
        
        try {
            const response = await fetch(`/api/subcategories/${tableName}?parent_id=${parentId}&level=2`);
            
            if (!response.ok) {
                throw new Error('Failed to load subcategories');
            }
            
            const subcategories = await response.json();
            console.log('Loaded subcategories:', subcategories);
            
            let html = '';
            
            // Check if subcategories array is empty
            if (!subcategories || subcategories.length === 0) {
                html = '<div class="text-muted fst-italic">No subcategories found</div>';
            } else {
                subcategories.forEach(subcategory => {
                    const isChecked = selectedValues.includes(subcategory.id);
                    console.log('Subcategory check:', {
                        subcategoryId: subcategory.id,
                        selectedValues: selectedValues,
                        isChecked: isChecked
                    });
                    html += `
                        <div class="form-check mb-2">
                            <input 
                                class="form-check-input subcategory-checkbox" 
                                type="checkbox" 
                                id="subcategory_${subcategory.id}" 
                                value="${subcategory.id}"
                                data-subcategory-id="${subcategory.id}"
                                data-parent-id="${parentId}"
                                ${isChecked ? 'checked' : ''}
                            >
                            <label class="form-check-label" for="subcategory_${subcategory.id}">
                                ${subcategory.name}
                            </label>
                            
                            <!-- Filters Container -->
                            <div class="filters-container ms-4 mt-2" id="filters_${subcategory.id}" style="display: none;">
                                <h6 class="text-muted mb-2">Filter Options</h6>
                                <div class="filters-list">
                                    <!-- Filters will be loaded here -->
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            container.innerHTML = html;
            
            // Load filters for already checked subcategories (only if subcategories exist)
            if (subcategories && subcategories.length > 0) {
                subcategories.forEach(subcategory => {
                    if (selectedValues.includes(subcategory.id)) {
                        const filtersContainer = document.getElementById('filters_' + subcategory.id);
                        if (filtersContainer) {
                            filtersContainer.style.display = 'block';
                            loadFilters(subcategory.id, filtersContainer.querySelector('.filters-list'));
                        }
                    }
                });
            }
            
        } catch (error) {
            console.error('Error loading subcategories:', error);
            container.innerHTML = '<div class="text-danger">Error loading subcategories</div>';
        }
    }
    
    async function loadFilters(subcategoryId, container) {
        console.log('Loading filters for subcategory:', subcategoryId);
        console.log('API URL:', `/api/subcategories/${tableName}?parent_id=${subcategoryId}&level=3`);
        
        try {
            const response = await fetch(`/api/subcategories/${tableName}?parent_id=${subcategoryId}&level=3`);
            
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error('Failed to load filters');
            }
            
            const filters = await response.json();
            console.log('Loaded filters:', filters);
            console.log('Filters count:', filters ? filters.length : 0);
            
            let html = '';
            
            // Check if filters array is empty
            if (!filters || filters.length === 0) {
                html = '<div class="text-muted fst-italic">No filters found</div>';
            } else {
                filters.forEach(filter => {
                    const isChecked = selectedValues.includes(filter.id);
                    console.log('Filter check:', {
                        filterId: filter.id,
                        selectedValues: selectedValues,
                        isChecked: isChecked
                    });
                    html += `
                        <div class="form-check mb-1">
                            <input 
                                class="form-check-input filter-checkbox" 
                                type="checkbox" 
                                id="filter_${filter.id}" 
                                value="${filter.id}"
                                data-filter-id="${filter.id}"
                                data-subcategory-id="${subcategoryId}"
                                ${isChecked ? 'checked' : ''}
                            >
                            <label class="form-check-label" for="filter_${filter.id}">
                                ${filter.name}
                            </label>
                        </div>
                    `;
                });
            }
            
            container.innerHTML = html;
            
        } catch (error) {
            console.error('Error loading filters:', error);
            container.innerHTML = '<div class="text-danger">Error loading filters</div>';
        }
    }
    
    function removeChildSelections(parentId) {
        // Remove all subcategory and filter selections for this parent
        const subcategoriesContainer = document.getElementById('subcategories_' + parentId);
        const subcategoryCheckboxes = subcategoriesContainer.querySelectorAll('.subcategory-checkbox');
        const filterCheckboxes = subcategoriesContainer.querySelectorAll('.filter-checkbox');
        
        subcategoryCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedValues = selectedValues.filter(value => value != checkbox.value);
            }
        });
        
        filterCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedValues = selectedValues.filter(value => value != checkbox.value);
            }
        });
    }
    
    function removeFilterSelections(subcategoryId) {
        // Remove all filter selections for this subcategory
        const filtersContainer = document.getElementById('filters_' + subcategoryId);
        const filterCheckboxes = filtersContainer.querySelectorAll('.filter-checkbox');
        
        filterCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedValues = selectedValues.filter(value => value != checkbox.value);
            }
        });
    }
    
    function updateHiddenInput() {
        // Collect all checked values
        const allCheckboxes = container.querySelectorAll('input[type="checkbox"]:checked');
        selectedValues = [];
        
        allCheckboxes.forEach(checkbox => {
            if (checkbox.value && !checkbox.classList.contains('parent-checkbox')) {
                selectedValues.push(checkbox.value); // Keep UUID as string, don't parseInt()
            }
        });
        
        console.log('Updated selected values (UUIDs):', selectedValues);
        hiddenInput.value = JSON.stringify(selectedValues);
    }
    
    // Initialize: Check parents and load subcategories based on selected values
    async function initializeFromSelectedValues() {
        console.log('Initializing from selected values:', selectedValues);
        
        if (!selectedValues || selectedValues.length === 0) {
            return; // No pre-selected values
        }
        
        // For each parent, check if any selected values belong to its hierarchy
        const parentCheckboxes = document.querySelectorAll('.parent-checkbox');
        
        for (const parentCheckbox of parentCheckboxes) {
            const parentId = parentCheckbox.dataset.parentId;
            const subcategoriesContainer = document.getElementById('subcategories_' + parentId);
            
            try {
                // Load subcategories for this parent to check if any are selected
                const response = await fetch(`/api/subcategories/${tableName}?parent_id=${parentId}&level=2`);
                if (response.ok) {
                    const subcategories = await response.json();
                    let shouldCheckParent = false;
                    
                    // Check if any subcategories are in selectedValues
                    for (const subcategory of subcategories) {
                        if (selectedValues.includes(subcategory.id)) {
                            shouldCheckParent = true;
                            break;
                        }
                        
                        // Also check filters for this subcategory
                        try {
                            const filtersResponse = await fetch(`/api/subcategories/${tableName}?parent_id=${subcategory.id}&level=3`);
                            if (filtersResponse.ok) {
                                const filters = await filtersResponse.json();
                                for (const filter of filters) {
                                    if (selectedValues.includes(filter.id)) {
                                        shouldCheckParent = true;
                                        break;
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('Error checking filters for subcategory:', subcategory.id, error);
                        }
                        
                        if (shouldCheckParent) break;
                    }
                    
                    // If we should check this parent, do so and load its subcategories
                    if (shouldCheckParent) {
                        parentCheckbox.checked = true;
                        subcategoriesContainer.style.display = 'block';
                        loadSubcategories(parentId, subcategoriesContainer.querySelector('.subcategories-list'));
                    }
                }
            } catch (error) {
                console.error('Error initializing parent:', parentId, error);
            }
        }
    }
    
    // Call initialization
    initializeFromSelectedValues();
});
</script>

<style>
.three-level-hierarchical-container .form-check {
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
    margin-left: 10px;
}

.three-level-hierarchical-container .parent-categories > .form-check {
    border-left: 3px solid #007bff;
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.three-level-hierarchical-container .subcategories-container {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin-top: 10px;
}

.three-level-hierarchical-container .filters-container {
    background-color: #f1f3f4;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    padding: 8px;
    margin-top: 8px;
}

.three-level-hierarchical-container h6 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 8px;
}
</style>