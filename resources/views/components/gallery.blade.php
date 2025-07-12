@props([
    'name' => 'gallery',
    'label' => 'Gallery',
    'value' => null,
    'error' => null,
    'required' => false,
    'minImages' => 1,
    'maxImages' => 6,
    'bucket' => 'venue-galleries'
])

@php
    // Parse existing gallery data
    $galleryData = [];
    if ($value) {
        if (is_string($value)) {
            $parsedValue = json_decode($value, true);
            if ($parsedValue) {
                $galleryData = $parsedValue;
            }
        } elseif (is_array($value)) {
            $galleryData = $value;
        }
    }
    
    // Generate unique gallery ID if not exists
    $galleryId = $galleryData['gallery_id'] ?? uniqid('gallery_', true);
    $images = $galleryData['images'] ?? [];
@endphp

<div class="form-group">
    <label class="form-label">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
        <span class="text-sm text-gray-500 font-normal">
            ({{ $minImages }}-{{ $maxImages }} images{{ $required ? ', at least ' . $minImages . ' required' : '' }})
        </span>
    </label>
    
    @if($error)
        <div class="text-red-500 text-sm mt-1">{{ $error }}</div>
    @endif
    
    <div class="gallery-container bg-white border border-gray-300 rounded-lg p-4 mt-2" data-gallery-id="{{ $galleryId }}">
        <!-- Hidden input to store gallery data -->
        <input type="hidden" name="{{ $name }}" id="{{ $name }}_data" value="{{ json_encode(['gallery_id' => $galleryId, 'images' => $images]) }}">
        
        <!-- Upload Area -->
        <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center mb-4 hover:border-blue-400 transition-colors cursor-pointer" 
             id="upload-area-{{ $galleryId }}"
             ondrop="handleDrop(event, '{{ $galleryId }}')" 
             ondragover="handleDragOver(event)" 
             ondragleave="handleDragLeave(event)"
             onclick="document.getElementById('file-input-{{ $galleryId }}').click()">
            
            <div class="upload-icon text-gray-400 mb-2">
               <!-- <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg> -->
            </div>
            
            <div class="upload-text">
                <p class="text-lg font-medium text-gray-700">Drop images here or click to upload</p>
                <p class="text-sm text-gray-500 mt-1">
                    Supports: JPG, PNG, GIF (max 5MB each)
                </p>
                <p class="text-sm text-gray-500">
                    <span id="image-count-{{ $galleryId }}">{{ count($images) }}</span> / {{ $maxImages }} images
                </p>
            </div>
            
            <input type="file" 
                   id="file-input-{{ $galleryId }}" 
                   class="hidden" 
                   multiple 
                   accept="image/*"
                   onchange="handleFileSelect(event, '{{ $galleryId }}')">
        </div>
        
        <!-- Progress Bar -->
        <div class="upload-progress hidden mb-4" id="progress-{{ $galleryId }}">
            <div class="bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%" id="progress-bar-{{ $galleryId }}"></div>
            </div>
            <p class="text-sm text-gray-600 mt-1" id="progress-text-{{ $galleryId }}">Uploading...</p>
        </div>
        
        <!-- Gallery Grid -->
        <div class="gallery-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="gallery-grid-{{ $galleryId }}">
            @foreach($images as $index => $image)
                <div class="gallery-item relative group border rounded-lg overflow-hidden bg-gray-50" data-index="{{ $index }}">
                    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? 'Gallery image' }}" 
                         class="w-full h-32 object-cover">
                    
                    <!-- Image Controls -->
                    <div class="image-controls absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button type="button" 
                                class="btn-control bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full"
                                onclick="editImage('{{ $galleryId }}', {{ $index }})"
                                title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        
                        <button type="button" 
                                class="btn-control bg-red-500 hover:bg-red-600 text-white p-2 rounded-full"
                                onclick="deleteImage('{{ $galleryId }}', {{ $index }})"
                                title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Drag Handle -->
                    <div class="drag-handle absolute top-2 right-2 bg-white bg-opacity-80 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity cursor-move">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Empty State -->
        @if(empty($images))
            <div class="empty-state text-center py-8 text-gray-500" id="empty-state-{{ $galleryId }}">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-lg font-medium">No images uploaded yet</p>
                <p class="text-sm">Upload your first image to get started</p>
            </div>
        @endif
    </div>
</div>

<!-- Edit Image Modal -->
<div id="edit-modal-{{ $galleryId }}" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Edit Image</h3>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
            <input type="text" 
                   id="edit-alt-{{ $galleryId }}" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Describe this image">
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Caption</label>
            <textarea id="edit-caption-{{ $galleryId }}" 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      rows="3"
                      placeholder="Add a caption for this image"></textarea>
        </div>
        
        <div class="flex justify-end gap-2">
            <button type="button" 
                    class="btn btn_secondary px-4 py-2"
                    onclick="closeEditModal('{{ $galleryId }}')">
                Cancel
            </button>
            <button type="button" 
                    class="btn btn_primary px-4 py-2"
                    onclick="saveImageEdit('{{ $galleryId }}')">
                Save Changes
            </button>
        </div>
    </div>
</div>

<script>
    // Global gallery data storage
    let galleryData = {};
    
    // Initialize gallery data
    function initGallery(galleryId) {
        if (!galleryData[galleryId]) {
            const hiddenInput = document.getElementById(`{{ $name }}_data`);
            const existingData = JSON.parse(hiddenInput.value || '{"gallery_id": "", "images": []}');
            galleryData[galleryId] = existingData;
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initGallery('{{ $galleryId }}');
        updateImageCount('{{ $galleryId }}');
        
        // Initialize drag and drop sorting
        initSortable('{{ $galleryId }}');
    });
    
    // Handle drag and drop
    function handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
    }
    
    function handleDragLeave(event) {
        event.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
    }
    
    function handleDrop(event, galleryId) {
        event.preventDefault();
        event.currentTarget.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = event.dataTransfer.files;
        handleFiles(files, galleryId);
    }
    
    function handleFileSelect(event, galleryId) {
        const files = event.target.files;
        handleFiles(files, galleryId);
    }
    
    function handleFiles(files, galleryId) {
        initGallery(galleryId);
        
        const maxImages = {{ $maxImages }};
        const currentCount = galleryData[galleryId].images.length;
        const remainingSlots = maxImages - currentCount;
        
        if (files.length > remainingSlots) {
            alert(`You can only upload ${remainingSlots} more image(s). Maximum is ${maxImages} images.`);
            return;
        }
        
        // Validate files
        const validFiles = [];
        for (let file of files) {
            if (validateFile(file)) {
                validFiles.push(file);
            }
        }
        
        if (validFiles.length === 0) {
            return;
        }
        
        // Upload files
        uploadFiles(validFiles, galleryId);
    }
    
    function validateFile(file) {
        // Check file type
        if (!file.type.startsWith('image/')) {
            alert(`${file.name} is not an image file.`);
            return false;
        }
        
        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            alert(`${file.name} is too large. Maximum size is 5MB.`);
            return false;
        }
        
        return true;
    }
    
    function uploadFiles(files, galleryId) {
        const progressContainer = document.getElementById(`progress-${galleryId}`);
        const progressBar = document.getElementById(`progress-bar-${galleryId}`);
        const progressText = document.getElementById(`progress-text-${galleryId}`);
        
        progressContainer.classList.remove('hidden');
        
        let uploadedCount = 0;
        const totalFiles = files.length;
        
        files.forEach((file, index) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('gallery_id', galleryId);
            formData.append('bucket', '{{ $bucket }}');
            
            fetch('/api/gallery/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add image to gallery data
                    galleryData[galleryId].images.push({
                        id: data.image.id,
                        url: data.image.url,
                        path: data.image.path,
                        alt: '',
                        caption: '',
                        order: galleryData[galleryId].images.length
                    });
                    
                    // Update UI
                    addImageToGrid(galleryId, galleryData[galleryId].images[galleryData[galleryId].images.length - 1]);
                    updateHiddenInput(galleryId);
                    updateImageCount(galleryId);
                } else {
                    alert(`Failed to upload ${file.name}: ${data.error}`);
                }
                
                uploadedCount++;
                const progress = (uploadedCount / totalFiles) * 100;
                progressBar.style.width = progress + '%';
                progressText.textContent = `Uploaded ${uploadedCount} of ${totalFiles} files`;
                
                if (uploadedCount === totalFiles) {
                    setTimeout(() => {
                        progressContainer.classList.add('hidden');
                        progressBar.style.width = '0%';
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert(`Failed to upload ${file.name}`);
                
                uploadedCount++;
                if (uploadedCount === totalFiles) {
                    progressContainer.classList.add('hidden');
                }
            });
        });
    }
    
    function addImageToGrid(galleryId, image) {
        const grid = document.getElementById(`gallery-grid-${galleryId}`);
        const emptyState = document.getElementById(`empty-state-${galleryId}`);
        
        // Hide empty state
        if (emptyState) {
            emptyState.classList.add('hidden');
        }
        
        const imageElement = document.createElement('div');
        imageElement.className = 'gallery-item relative group border rounded-lg overflow-hidden bg-gray-50';
        imageElement.setAttribute('data-index', galleryData[galleryId].images.length - 1);
        
        imageElement.innerHTML = `
            <img src="${image.url}" alt="${image.alt || 'Gallery image'}" class="w-full h-32 object-cover">
            
            <div class="image-controls absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button type="button" 
                        class="btn-control bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full"
                        onclick="editImage('${galleryId}', ${galleryData[galleryId].images.length - 1})"
                        title="Edit">
                   <!-- <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                
                <button type="button" 
                        class="btn-control bg-red-500 hover:bg-red-600 text-white p-2 rounded-full"
                        onclick="deleteImage('${galleryId}', ${galleryData[galleryId].images.length - 1})"
                        title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            
            <div class="drag-handle absolute top-2 right-2 bg-white bg-opacity-80 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity cursor-move">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </div>
        `;
        
        grid.appendChild(imageElement);
    }
    
    function deleteImage(galleryId, index) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }
        
        const image = galleryData[galleryId].images[index];
        
        // Delete from storage
        fetch('/api/gallery/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                gallery_id: galleryId,
                image_path: image.path,
                bucket: '{{ $bucket }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from gallery data
                galleryData[galleryId].images.splice(index, 1);
                
                // Update UI
                refreshGalleryGrid(galleryId);
                updateHiddenInput(galleryId);
                updateImageCount(galleryId);
            } else {
                alert('Failed to delete image: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('Failed to delete image');
        });
    }
    
    function editImage(galleryId, index) {
        const image = galleryData[galleryId].images[index];
        const modal = document.getElementById(`edit-modal-${galleryId}`);
        const altInput = document.getElementById(`edit-alt-${galleryId}`);
        const captionInput = document.getElementById(`edit-caption-${galleryId}`);
        
        altInput.value = image.alt || '';
        captionInput.value = image.caption || '';
        
        modal.classList.remove('hidden');
        modal.dataset.editIndex = index;
    }
    
    function closeEditModal(galleryId) {
        const modal = document.getElementById(`edit-modal-${galleryId}`);
        modal.classList.add('hidden');
    }
    
    function saveImageEdit(galleryId) {
        const modal = document.getElementById(`edit-modal-${galleryId}`);
        const index = parseInt(modal.dataset.editIndex);
        const altInput = document.getElementById(`edit-alt-${galleryId}`);
        const captionInput = document.getElementById(`edit-caption-${galleryId}`);
        
        // Update image data
        galleryData[galleryId].images[index].alt = altInput.value;
        galleryData[galleryId].images[index].caption = captionInput.value;
        
        // Update hidden input
        updateHiddenInput(galleryId);
        
        // Close modal
        closeEditModal(galleryId);
    }
    
    function refreshGalleryGrid(galleryId) {
        const grid = document.getElementById(`gallery-grid-${galleryId}`);
        const emptyState = document.getElementById(`empty-state-${galleryId}`);
        
        grid.innerHTML = '';
        
        if (galleryData[galleryId].images.length === 0) {
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }
            return;
        }
        
        if (emptyState) {
            emptyState.classList.add('hidden');
        }
        
        galleryData[galleryId].images.forEach((image, index) => {
            const imageElement = document.createElement('div');
            imageElement.className = 'gallery-item relative group border rounded-lg overflow-hidden bg-gray-50';
            imageElement.setAttribute('data-index', index);
            
            imageElement.innerHTML = `
                <img src="${image.url}" alt="${image.alt || 'Gallery image'}" class="w-full h-32 object-cover">
                
                <div class="image-controls absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button type="button" 
                            class="btn-control bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full"
                            onclick="editImage('${galleryId}', ${index})"
                            title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    
                    <button type="button" 
                            class="btn-control bg-red-500 hover:bg-red-600 text-white p-2 rounded-full"
                            onclick="deleteImage('${galleryId}', ${index})"
                            title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="drag-handle absolute top-2 right-2 bg-white bg-opacity-80 rounded p-1 opacity-0 group-hover:opacity-100 transition-opacity cursor-move">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </div>
            `;
            
            grid.appendChild(imageElement);
        });
    }
    
    function updateHiddenInput(galleryId) {
        const hiddenInput = document.getElementById('{{ $name }}_data');
        hiddenInput.value = JSON.stringify(galleryData[galleryId]);
    }
    
    function updateImageCount(galleryId) {
        const countElement = document.getElementById(`image-count-${galleryId}`);
        if (countElement) {
            countElement.textContent = galleryData[galleryId]?.images?.length || 0;
        }
    }
    
    function initSortable(galleryId) {
        // This would integrate with a drag-and-drop library like Sortable.js
        // For now, we'll implement basic functionality
        console.log('Sortable initialized for gallery:', galleryId);
    }
</script>

<style>
    .gallery-container {
        max-width: 100%;
    }
    
    .upload-area {
        transition: all 0.3s ease;
    }
    
    .upload-area.dragover {
        border-color: #3B82F6;
        background-color: #EFF6FF;
    }
    
    .gallery-item {
        transition: all 0.3s ease;
    }
    
    .gallery-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-control {
        transition: all 0.2s ease;
    }
    
    .btn-control:hover {
        transform: scale(1.1);
    }
    
    .image-controls {
        backdrop-filter: blur(4px);
    }
    
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .gallery-item img {
            height: 120px;
        }
    }
</style> 