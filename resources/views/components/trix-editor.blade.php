@props([
    'name',
    'label' => null,
    'value' => '',
    'rows' => 5,
    'error' => null,
    'success' => null,
    'placeholder' => null,
    'required' => false,
    'enableAI' => true
])

<div class="form-group mb-3">
    @if($label)
        <label class="form-label" for="{{ $name }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <input id="{{ $name }}" type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}">
    
    <trix-editor 
        class="form-control trix-content {{ $error ? 'is-invalid' : '' }}" 
        input="{{ $name }}" 
        rows="{{ $rows }}" 
        data-trix-allow-files="false"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif>
    </trix-editor>
    
    @if($error)
        <div class="invalid-feedback">
            {{ $error }}
        </div>
    @endif
    
    @if($success)
        <div class="valid-feedback">
            {{ $success }}
        </div>
    @endif
    
    @if($enableAI)
        <div class="text-left mt-2">
            <button type="button" class="btn btn_secondary uppercase" style="border-radius: 0;" onclick="generateAIDescription('{{ $name }}', '{{ strtolower($name) }}')">
                Generate AI Description
            </button>
        </div>
    @endif
    
    <div class="form-text text-muted">
        <small>
            <i class="fas fa-info-circle me-1"></i>
            Use the rich text editor to format your content. AI generation available for quick content creation.
        </small>
    </div>
</div>

<!-- AI Description Generation Modal (only include once per page) -->
@once
<div class="modal fade" id="aiDescriptionModal" tabindex="-1" aria-labelledby="aiDescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aiDescriptionModalLabel">
                    <i class="fas fa-magic me-2"></i>Generate AI Description
                </h5>
                <button type="button" class="btn-close" onclick="closeAIDescriptionModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="aiDescriptionForm">
                    <div class="mb-3 text-center">
                        <label for="aiPrompt" class="form-label">
                            <i class="fas fa-lightbulb me-1"></i>Describe what you want to generate
                        </label>
                        <textarea id="aiPrompt" class="form-control" rows="3" 
                                placeholder="Example: A detailed description of a tech conference about artificial intelligence and machine learning..." 
                                required></textarea>
                        <div class="form-text">Be specific about the content, tone, and key points you want included.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="aiModel" class="form-label">
                                <i class="fas fa-brain me-1"></i>AI Model
                            </label>
                            <select id="aiModel" class="form-select">
                                <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Faster)</option>
                                <option value="gpt-4">GPT-4 (Higher Quality)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="aiMaxLength" class="form-label">
                                <i class="fas fa-ruler me-1"></i>Max Length
                            </label>
                            <select id="aiMaxLength" class="form-select">
                                <option value="200">Short (~200 chars)</option>
                                <option value="500" selected>Medium (~500 chars)</option>
                                <option value="1000">Long (~1000 chars)</option>
                                <option value="2000">Very Long (~2000 chars)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="aiTemperature" class="form-label">
                            <i class="fas fa-thermometer-half me-1"></i>Creativity Level: <span id="temperatureValue">0.7</span>
                        </label>
                        <input type="range" id="aiTemperature" class="form-range" min="0" max="2" step="0.1" value="0.7" 
                               oninput="document.getElementById('temperatureValue').textContent = this.value">
                        <div class="form-text">Lower = more focused, Higher = more creative</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn_secondary uppercase" style="border-radius: 0;" onclick="closeAIDescriptionModal()">Cancel</button>
                <button type="button" class="btn btn_secondary uppercase" style="border-radius: 0; margin-left: 10px;" id="generateAIBtn">
                    Generate Description
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAIFieldName = '';
let currentAIFieldType = '';

async function generateAIDescription(fieldName, fieldType) {
    currentAIFieldName = fieldName;
    currentAIFieldType = fieldType;
    
    // Show the modal using the same approach as file-browser
    const modal = document.getElementById('aiDescriptionModal');
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function closeAIDescriptionModal() {
    const modal = document.getElementById('aiDescriptionModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateAIBtn');
    const modal = document.getElementById('aiDescriptionModal');
    
    if (generateBtn) {
        generateBtn.addEventListener('click', async function() {
            const prompt = document.getElementById('aiPrompt').value;
            const model = document.getElementById('aiModel').value;
            const maxLength = document.getElementById('aiMaxLength').value;
            const temperature = document.getElementById('aiTemperature').value;
            
            if (!prompt.trim()) {
                alert('Please enter a description prompt.');
                return;
            }
            
            // Show loading state
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            
            try {
                const response = await fetch('/ai/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        field_name: currentAIFieldType,
                        model: model,
                        max_length: parseInt(maxLength),
                        temperature: parseFloat(temperature)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Get the Trix editor instance
                    const trixEditor = document.querySelector(`trix-editor[input="${currentAIFieldName}"]`);
                    const hiddenInput = document.getElementById(currentAIFieldName);
                    
                    if (trixEditor && hiddenInput) {
                        // Set the content in Trix editor
                        trixEditor.editor.setSelectedRange([0, trixEditor.editor.getDocument().getLength()]);
                        trixEditor.editor.deleteInDirection("forward");
                        trixEditor.editor.insertHTML(result.text.replace(/\n/g, '<br>'));
                        
                        // Update hidden input
                        hiddenInput.value = result.text;
                    }
                    
                    // Hide modal
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                        document.body.classList.remove('modal-open');
                    }
                    
                    // Show success message
                    showNotification(`Description generated successfully! (${result.word_count} words, ${result.character_count} characters)`, 'success');
                    
                } else {
                    alert('Error generating description: ' + result.error);
                }
                
            } catch (error) {
                console.error('AI Generation Error:', error);
                alert('Failed to generate description. Please try again.');
            } finally {
                // Reset button state
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-magic me-2"></i>Generate Description';
            }
        });
    }
});

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}
</script>
@endonce


