<div class="mt-5">
    <label class="label block mb-2" for="{{ $name }}">{{ $label }}</label>

    <!-- Image Preview Container -->
    <div id="imagePreviewContainer" style="margin-bottom: 20px;">
        @if($isImage && $preview)
            <img id="filePreview" src="{{ $preview }}" alt="File Image" class="img-thumbnail" style="max-height: 200px; max-width: 200px;">
        @endif
    </div>

    <label class="input-group font-normal">
        <div id="fileName" class="file-name input-addon input-addon-prepend input-group-item w-full overflow-x-hidden">
            {{ $value ?? 'No file chosen' }}
        </div>
        <input type="file" class="hidden" name="{{ $name }}" onchange="updateFile(this)">
        <div class="input-group-item btn btn_primary uppercase">Choose File</div>
    </label>

    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div>
<script>
    function updateFile(input) {
        var fileName = 'No file chosen';
        if (input.files && input.files.length > 0) {
            var file = input.files[0];
            fileName = file.name;
            var isImage = file.type.startsWith('image/');
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewContainer = document.getElementById('imagePreviewContainer');
                previewContainer.innerHTML = ''; // Clear the preview container

                if (isImage) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.id = 'filePreview';
                    img.alt = 'File Image';
                    img.classList.add('img-thumbnail');
                    img.style.maxHeight = '50px';
                    previewContainer.appendChild(img);
                }

                // Optionally update fileName elsewhere if needed
            };
            reader.readAsDataURL(file);
        }

        // Update the fileName div only if needed
        var fileNameDiv = document.getElementById('fileName');
        if (fileNameDiv) {
            fileNameDiv.textContent = fileName;
        }
    }
</script>
