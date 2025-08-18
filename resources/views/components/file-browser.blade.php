<div class="mt-5">
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>

    <!-- Image Preview Container -->
    <div id="imagePreviewContainer-{{ $name }}" style="margin-bottom: 20px;">
        @if($isImage && $preview)
            <img id="filePreview-{{ $name }}" src="{{ $preview }}" alt="File Image" class="img-thumbnail" style="max-height: 200px; max-width: 200px;">
        @endif
    </div>

    <div class="input-group font-normal">
        <div id="fileName-{{ $name }}" class="file-name input-addon input-addon-prepend input-group-item w-full overflow-x-hidden">
            {{ $value ?? 'No file chosen' }}
        </div>
        <input type="file" class="hidden" name="{{ $name }}" id="fileInput-{{ $name }}" onchange="updateFile(this, '{{ $name }}')">
        <div class="input-group-item btn btn_primary uppercase" onclick="document.getElementById('fileInput-{{ $name }}').click()">Choose File</div>
        @if($isImage)
            <div class="input-group-item btn btn_secondary uppercase" onclick="openAiImageModal('{{ $name }}', event)">
                Generate AI Image
            </div>
        @endif
    </div>

    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div>

@if($isImage)
<!-- AI Image Generation Modal -->
<div id="ai-image-modal-{{ $name }}" class="modal" data-animations="fadeInDown, fadeOutUp">
    <div class="modal-dialog max-w-2xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Generate AI Image</h2>
                <button class="close la la-times" onclick="closeAiImageModal('{{ $name }}')"></button>
            </div>
            <div class="modal-body">
                <form id="aiImageForm-{{ $name }}">
                    <div class="mb-4">
                        <label class="label block mb-2" for="aiPrompt-{{ $name }}">Image Prompt</label>
                        <textarea 
                            id="aiPrompt-{{ $name }}" 
                            name="prompt" 
                            class="form-control w-full" 
                            rows="4" 
                            placeholder="Describe the image you want to generate..."
                        ></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="label block mb-2" for="aiSize-{{ $name }}">Image Size</label>
                        <select id="aiSize-{{ $name }}" name="size" class="form-control w-full">
                            <option value="512x512">512x512 (Square - mapped to 1024x1024)</option>
                            <option value="1024x1024" selected>1024x1024 (Square)</option>
                            <option value="1792x1024">1792x1024 (Landscape)</option>
                            <option value="1024x1792">1024x1792 (Portrait)</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <div id="aiImageGenerationStatus-{{ $name }}" class="hidden">
                            <div class="flex items-center">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                                <span>Generating image...</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="flex ltr:ml-auto rtl:mr-auto">
                    <button type="button" class="btn btn_secondary uppercase" onclick="closeAiImageModal('{{ $name }}')">
                        Cancel
                    </button>
                    <button type="button" class="btn btn_primary ltr:ml-2 rtl:mr-2 uppercase" onclick="generateAIImage('{{ $name }}')">
                        Generate Image
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
function updateFile(input, componentName) {
    var fileName = 'No file chosen';
    if (input.files && input.files.length > 0) {
        var file = input.files[0];
        fileName = file.name;
        var isImage = file.type.startsWith('image/');
        var reader = new FileReader();
        reader.onload = function(e) {
            var previewContainer = document.getElementById('imagePreviewContainer-' + componentName);
            previewContainer.innerHTML = ''; // Clear the preview container

            if (isImage) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.id = 'filePreview-' + componentName;
                img.alt = 'File Image';
                img.classList.add('img-thumbnail');
                img.style.maxHeight = '200px';
                img.style.maxWidth = '200px';
                previewContainer.appendChild(img);
            }
        };
        reader.readAsDataURL(file);
    }

    // Update the fileName div
    var fileNameDiv = document.getElementById('fileName-' + componentName);
    if (fileNameDiv) {
        fileNameDiv.textContent = fileName;
    }
}

function openAiImageModal(componentName, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Use the modal structure from components-modal.html
    const modal = document.getElementById('ai-image-modal-' + componentName);
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function closeAiImageModal(componentName) {
    const modal = document.getElementById('ai-image-modal-' + componentName);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

async function generateAIImage(componentName) {
    console.log('Generating AI image for:', componentName);
    
    // Get form elements
    const promptInput = document.getElementById('aiPrompt-' + componentName);
    const sizeSelect = document.getElementById('aiSize-' + componentName);
    const statusDiv = document.getElementById('aiImageGenerationStatus-' + componentName);
    const submitBtn = document.querySelector('[onclick="generateAIImage(\'' + componentName + '\')"]');
    
    // Validate input
    if (!promptInput.value.trim()) {
        alert('Please enter a prompt for the image.');
        return;
    }
    
    // Show loading state
    statusDiv.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Generating...';
    
    try {
        // Create form data
        const formData = new FormData();
        formData.append('prompt', promptInput.value);
        formData.append('size', sizeSelect.value);
        formData.append('component_name', componentName);
        
        console.log('Sending request to /ai/generate-image');
        
        const response = await fetch('/ai/generate-image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData
        });
        
        console.log('Response received:', response.status);
        
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            // Convert base64 data to blob and create File object
            const base64Data = result.file_data.split(',')[1]; // Remove data:image/png;base64, prefix
            const byteCharacters = atob(base64Data);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const imageBlob = new Blob([byteArray], { type: result.mime_type });
            const imageFile = new File([imageBlob], result.filename, { type: result.mime_type });
            
            // Create a new FileList with the generated image
            const fileInput = document.getElementById('fileInput-' + componentName);
            const dt = new DataTransfer();
            dt.items.add(imageFile);
            fileInput.files = dt.files;
            
            // Update the preview using base64 data
            const previewContainer = document.getElementById('imagePreviewContainer-' + componentName);
            previewContainer.innerHTML = `
                <img id="filePreview-${componentName}" src="${result.file_data}" alt="AI Generated Image" class="img-thumbnail" style="max-height: 200px; max-width: 200px;">
            `;
            
            // Update file name
            const fileNameDiv = document.getElementById('fileName-' + componentName);
            fileNameDiv.textContent = result.filename;
            
            // Close modal
            closeAiImageModal(componentName);
            
            // Clear form inputs
            promptInput.value = '';
            sizeSelect.value = '1024x1024';
            
        } else {
            alert('Error: ' + result.error);
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to generate image. Please try again.');
    } finally {
        // Hide loading state
        statusDiv.classList.add('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Generate Image';
    }
}
</script>
