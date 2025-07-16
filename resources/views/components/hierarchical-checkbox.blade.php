@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'value' => [],
    'subcategorySource' => null,
    'componentName' => '',
    'required' => false
])

@php
    // Extract table name from subcategorySource
    $tableName = 'venue_categories'; // default fallback
    if ($subcategorySource && is_array($subcategorySource) && isset($subcategorySource['source'])) {
        $source = $subcategorySource['source'];
        if (is_array($source) && count($source) >= 3) {
            $tableName = $source[2]; // third element is the table name
        }
    }
@endphp

<div class="form-group">
    <label class="form-label">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    
    <div class="hierarchical-checkbox-container" data-component-name="{{ $componentName }}" data-table-name="{{ $tableName }}">
        <!-- Top-level categories -->
        <div class="top-level-categories">
            @foreach($options as $option)
                <div class="category-item mb-3">
                    <label class="flex items-start space-x-2">
                        <input 
                            type="checkbox" 
                            name="{{ $name }}[]" 
                            value="{{ $option['value'] }}"
                            class="parent-category-checkbox mt-1"
                            data-category-id="{{ $option['value'] }}"
                            @if(is_array($value) && in_array($option['value'], $value)) checked @endif
                        >
                        <span class="font-medium">{{ $option['name'] }}</span>
                    </label>
                    
                    <!-- Subcategories container (initially hidden) -->
                    <div class="subcategories-container ml-6 mt-2 hidden" data-parent-id="{{ $option['value'] }}">
                        <div class="loading-message text-gray-500 text-sm">Loading subcategories...</div>
                        <div class="subcategories-list"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('[data-component-name="{{ $componentName }}"]');
    if (!container) return;

    const parentCheckboxes = container.querySelectorAll('.parent-category-checkbox');
    
    parentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const categoryId = this.dataset.categoryId;
            const subcategoriesContainer = container.querySelector(`[data-parent-id="${categoryId}"]`);
            
            if (this.checked) {
                // Show subcategories
                subcategoriesContainer.classList.remove('hidden');
                loadSubcategories(categoryId, subcategoriesContainer);
            } else {
                // Hide subcategories and uncheck all
                subcategoriesContainer.classList.add('hidden');
                const subcategoryCheckboxes = subcategoriesContainer.querySelectorAll('input[type="checkbox"]');
                subcategoryCheckboxes.forEach(cb => cb.checked = false);
            }
        });
        
        // If checkbox is already checked on page load, load subcategories
        if (checkbox.checked) {
            const categoryId = checkbox.dataset.categoryId;
            const subcategoriesContainer = container.querySelector(`[data-parent-id="${categoryId}"]`);
            subcategoriesContainer.classList.remove('hidden');
            loadSubcategories(categoryId, subcategoriesContainer);
        }
    });
    
    function loadSubcategories(parentId, container) {
        const loadingMessage = container.querySelector('.loading-message');
        const subcategoriesList = container.querySelector('.subcategories-list');
        
        loadingMessage.style.display = 'block';
        subcategoriesList.innerHTML = '';
        
        // Get table name from the main container data attribute
        const mainContainer = document.querySelector('[data-component-name="{{ $componentName }}"]');
        const tableName = mainContainer.dataset.tableName || 'venue_categories';
        
        // Make AJAX request to get subcategories
        fetch(`/api/subcategories/${tableName}?parent_id=${parentId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingMessage.style.display = 'none';
            
            if (data.success && data.subcategories.length > 0) {
                data.subcategories.forEach(subcategory => {
                    const subcategoryItem = document.createElement('div');
                    subcategoryItem.className = 'subcategory-item mb-2';
                    subcategoryItem.innerHTML = `
                        <label class="flex items-center space-x-2">
                            <input 
                                type="checkbox" 
                                name="{{ $name }}[]" 
                                value="${subcategory.id}"
                                class="subcategory-checkbox"
                                ${isSubcategorySelected(subcategory.id) ? 'checked' : ''}
                            >
                            <span class="text-sm">${subcategory.name}</span>
                        </label>
                    `;
                    subcategoriesList.appendChild(subcategoryItem);
                });
            } else {
                subcategoriesList.innerHTML = '<div class="text-gray-500 text-sm">No subcategories found</div>';
            }
        })
        .catch(error => {
            loadingMessage.style.display = 'none';
            subcategoriesList.innerHTML = '<div class="text-red-500 text-sm">Error loading subcategories</div>';
            console.error('Error loading subcategories:', error);
        });
    }
    
    function isSubcategorySelected(subcategoryId) {
        const selectedValues = @json($value);
        return Array.isArray(selectedValues) && selectedValues.includes(subcategoryId.toString());
    }
});
</script>

<style>
.hierarchical-checkbox-container {
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #f8fafc;
}

.category-item {
    padding-bottom: 0.75rem;
}

.category-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.subcategories-container {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    padding: 0.75rem;
}

.subcategory-item {
    padding-left: 1rem;
    border-left: 2px solid #e2e8f0;
}

input[name="venue_category_id[]"] {
  margin: 0.25rem;
}
</style> 