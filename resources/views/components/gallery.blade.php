@props([
    'name' => 'gallery',
    'label' => 'Gallery',
    'value' => null,
    'error' => null,
    'required' => false,
    'minImages' => 0,
    'maxImages' => 5,
    'mode' => 'create'
])

@php
    $images = [];

    // Handle different input formats
    if ($value) {
        if (is_string($value)) {
            $parsed = json_decode($value, true);
            if (is_array($parsed)) {
                $images = $parsed;
            }
        } elseif (is_array($value)) {
            $images = $value;
        }
    }

    // Normalize the images array to consistent format
    $existing = [];
    foreach ($images as $img) {
        $imageData = [
            'id' => $img['id'] ?? ($img['uuid'] ?? null),
            'url' => $img['url'] ?? ($img['src'] ?? ($img['path'] ?? null)),
            'file_name' => $img['file_name'] ?? ($img['filename'] ?? (isset($img['url']) ? basename(parse_url($img['url'], PHP_URL_PATH)) : null))
        ];

        // Only add if we have at least an ID and URL
        if ($imageData['id'] && $imageData['url']) {
            $existing[] = $imageData;
        }
    }

    try {
        $galleryId = 'gal_' . bin2hex(random_bytes(5));
    } catch (\Exception $e) {
        $galleryId = 'gal_' . preg_replace('/[^A-Za-z0-9_]/', '_', uniqid('', true));
    }
@endphp

<div id="{{ $galleryId }}_wrap"
     class="form-group"
     data-skip-filejs="1"
     x-data="galleryComponent_{{ $galleryId }}({ mode: '{{ $mode }}', max: {{ (int)$maxImages }}, existing: @js($existing) })"
     x-init="init()">
    <label class="form-label text-sm font-medium text-gray-800">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        <span class="ml-2 text-xs text-gray-500 font-normal">({{ $minImages }}–{{ $maxImages }} images)</span>
    </label>

    @if($error)
        <div class="text-red-500 text-xs mt-1">{{ $error }}</div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl p-4 mt-2 shadow-sm">
        <!-- Hidden inputs: only send new image base64 strings -->
        <template x-for="p in previews" :key="'p-'+p.id">
            <input type="hidden" name="gallery_images[]" :value="p.url">
        </template>

        <!-- Hidden inputs for deleted image IDs (edit mode only) -->
        <template x-if="mode === 'edit'">
            <template x-for="id in deletedIds" :key="'d-'+id">
                <input type="hidden" name="deleted_images[]" :value="id">
            </template>
        </template>

        <!-- Gallery header -->
        <div class="gallery-header flex items-center justify-between gap-3 flex-wrap mb-4">
            <div class="header-actions flex items-center gap-3 flex-wrap">
                <button type="button"
                        class="gallery-add-btn inline-flex items-center gap-2 text-sm px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap"
                        :disabled="totalImages >= maxImages"
                        :class="{ 'opacity-50 cursor-not-allowed': totalImages >= maxImages }"
                        @click="triggerFileDialog()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add images
                </button>
                <span class="text-xs text-gray-500">or drag & drop below</span>
            </div>
            <div class="text-xs" :class="totalImages >= maxImages ? 'text-red-500 font-semibold' : 'text-gray-500'">
                <span class="font-medium" x-text="totalImages"></span> / {{ $maxImages }}
            </div>
        </div>

        <!-- Drop zone -->
        <div class="border border-dashed rounded-lg p-4 mb-4 text-center transition"
             :class="isDragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300 bg-gray-50'"
             @dragover.prevent="onDragOver"
             @dragleave.prevent="onDragLeave"
             @drop.prevent="onDrop">
            <div class="flex items-center justify-center gap-3">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 15a4 4 0 004 4h10a4 4 0 004-4M7 10l5-5m0 0l5 5m-5-5v12"/>
                </svg>
                <div class="text-xs text-gray-600">
                    Drop up to {{ $maxImages }} images — JPG/PNG/GIF, 5MB max
                </div>
            </div>
        </div>

        <!-- Image grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            <!-- Existing images (edit mode only) -->
            <template x-if="mode === 'edit'">
                <template x-for="(img, idx) in existingCards" :key="'ex-'+(img.id||idx)">
                    <div class="gallery-card relative rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm">
                        <div class="aspect-square w-full overflow-hidden">
                            <img :src="img.url" class="w-full h-full object-cover" :alt="img.file_name || `Image ${idx+1}`">
                        </div>
                        <button type="button"
                                class="gallery-x-btn absolute h-10 w-10 rounded-full bg-white text-black border-2 border-black/70 hover:bg-gray-100 hover:border-red-500 shadow focus:outline-none focus:ring-2 focus:ring-black/30 z-50"
                                title="Remove"
                                @click="toggleDelete(img)">
                            <span class="text-lg leading-none">×</span>
                        </button>

                        <!-- Deletion overlay -->
                        <div x-cloak x-show="isDeleted(img)"
                             class="absolute inset-0 z-40 bg-red-600/15 backdrop-blur-[1px] flex flex-col items-center justify-center gap-2">
                            <span class="text-xs font-semibold text-red-700">Marked for deletion</span>
                            <button type="button"
                                    class="text-xs px-2 py-1 rounded border border-red-600 text-red-700 bg-white hover:bg-red-50"
                                    @click="undoDelete(img)">
                                Undo
                            </button>
                        </div>
                    </div>
                </template>
            </template>

            <!-- New uploaded images (previews) -->
            <template x-for="(preview, i) in previews" :key="preview.id">
                <div class="gallery-card relative rounded-lg overflow-hidden border-2 border-green-500 bg-white shadow-sm">
                    <div class="aspect-square w-full overflow-hidden">
                        <img :src="preview.url" class="w-full h-full object-cover" :alt="`New image ${i+1}`">
                    </div>
                    <button type="button"
                            class="gallery-x-btn absolute h-10 w-10 rounded-full bg-white text-black border-2 border-black/70 hover:bg-gray-100 hover:border-black shadow focus:outline-none focus:ring-2 focus:ring-black/30 z-50"
                            title="Remove"
                            @click="removePreview(i)">
                        <span class="text-lg leading-none">×</span>
                    </button>
                    <span class="absolute bottom-2 left-2 text-[10px] font-semibold px-2 py-0.5 rounded bg-green-600 text-white uppercase">New</span>
                </div>
            </template>

            <!-- Empty slots -->
            <template x-for="i in emptySlots" :key="`empty-${i}`">
                <button type="button"
                        class="aspect-square rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-blue-50 hover:border-blue-400 flex items-center justify-center text-xs text-gray-500 transition"
                        @click="triggerFileDialog()">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </template>
        </div>

        <!-- Hidden file input -->
        <input type="file"
               data-skip-filejs="1"
               id="gallery-input-{{ $galleryId }}"
               class="hidden gallery-hidden-input"
               name="{{ $name }}[]"
               accept="image/*"
               multiple
               @change.stop="handleFileSelect($event)">
    </div>
</div>

@push('scripts')
    <script>
        function galleryComponent_{{ $galleryId }}(cfg) {
            return {
                mode: cfg.mode,
                maxImages: cfg.max || 5,
                previews: [],
                isDragging: false,
                existing: cfg.existing || [],
                deletedIds: [],

                init() {
                    console.log('Gallery initialized:', {
                        mode: this.mode,
                        maxImages: this.maxImages,
                        existing: this.existing,
                        existingCount: this.existing.length
                    });
                },

                // Get existing images that aren't marked for deletion
                get existingCards() {
                    return (this.existing || []).filter(e => e && e.url);
                },

                // Get kept (not deleted) existing images
                get keptExisting() {
                    return (this.existing || []).filter(e => !this.deletedIds.includes(e.id));
                },

                // Total number of images (kept existing + new previews)
                get totalImages() {
                    return (this.mode === 'edit' ? this.keptExisting.length : 0) + this.previews.length;
                },

                // Number of empty slots to show
                get emptySlots() {
                    const n = this.maxImages - this.totalImages;
                    return n > 0 ? n : 0;
                },

                // Open file dialog
                triggerFileDialog() {
                    if (this.totalImages >= this.maxImages) {
                        alert(`Maximum ${this.maxImages} images allowed`);
                        return;
                    }
                    const el = document.getElementById('gallery-input-{{ $galleryId }}');
                    if (el) el.click();
                },

                // Drag and drop handlers
                onDragOver() { this.isDragging = true; },
                onDragLeave() { this.isDragging = false; },
                onDrop(e) {
                    this.isDragging = false;
                    const files = Array.from(e.dataTransfer?.files || []);
                    this.processIncomingFiles(files);
                },

                // File input change handler
                handleFileSelect(e) {
                    const files = Array.from(e.target.files || []);
                    this.processIncomingFiles(files);
                    e.target.value = ''; // Reset input
                },

                // Process dropped/selected files
                processIncomingFiles(files) {
                    let remaining = this.maxImages - this.totalImages;
                    if (remaining <= 0) {
                        alert(`Maximum ${this.maxImages} images allowed`);
                        return;
                    }

                    const imageFiles = files.filter(f => this.validateFile(f));
                    const slice = imageFiles.slice(0, remaining);

                    if (imageFiles.length > slice.length) {
                        alert(`Only ${slice.length} images will be added (limit: ${this.maxImages})`);
                    }

                    slice.forEach(file => {
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            this.previews.push({
                                id: Date.now() + Math.random(),
                                url: ev.target.result,
                                file
                            });
                        };
                        reader.readAsDataURL(file);
                    });
                },

                // Validate file type and size
                validateFile(file) {
                    if (!file.type || !file.type.startsWith('image/')) {
                        console.warn('Invalid file type:', file.type);
                        return false;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`File "${file.name}" is too large (max 5MB)`);
                        return false;
                    }
                    return true;
                },

                // Remove new preview
                removePreview(i) {
                    this.previews.splice(i, 1);
                },

                // Mark existing image for deletion
                toggleDelete(img) {
                    if (!img || !img.id) return;
                    if (this.deletedIds.includes(img.id)) return;
                    this.deletedIds.push(img.id);
                    console.log('Marked for deletion:', img.id);
                },

                // Undo deletion
                undoDelete(img) {
                    if (!img || !img.id) return;
                    const i = this.deletedIds.indexOf(img.id);
                    if (i > -1) {
                        this.deletedIds.splice(i, 1);
                        console.log('Undone deletion:', img.id);
                    }
                },

                // Check if image is marked for deletion
                isDeleted(img) {
                    if (!img || !img.id) return false;
                    return this.deletedIds.includes(img.id);
                }
            }
        }
    </script>
@endpush

<style>
    [x-cloak]{display:none!important}
    .aspect-square{aspect-ratio:1/1}
    #{{ $galleryId }}_wrap .gallery-header{row-gap:.2rem}
    #{{ $galleryId }}_wrap .gallery-card{position:relative}
    #{{ $galleryId }}_wrap .gallery-x-btn{
        position:absolute;top:.10rem;right:.10rem;height:1.75rem;width:1.75rem;border-radius:9999px;background:#fff;color:#111;border:1.5px solid rgba(0,0,0,.85);box-shadow:0 2px 8px rgba(0,0,0,.18);z-index:50;display:flex;align-items:center;justify-content:center;pointer-events:auto
    }
    #{{ $galleryId }}_wrap .gallery-x-btn:hover{background:#f3f4f6;border-color:#000}
    #{{ $galleryId }}_wrap .gallery-x-btn svg,#{{ $galleryId }}_wrap .gallery-x-btn span{pointer-events:none}
    #{{ $galleryId }}_wrap .gallery-hidden-input + *{display:none!important}
    #{{ $galleryId }}_wrap .form-label{font-size:.875rem}
    #{{ $galleryId }}_wrap .bg-white.border{padding:.75rem;border-radius:.75rem}
    #{{ $galleryId }}_wrap .gallery-add-btn{font-size:.75rem;padding:.4rem .6rem;border-radius:9999px}
    #{{ $galleryId }}_wrap .gallery-add-btn svg{width:.95rem;height:.95rem}
    #{{ $galleryId }}_wrap .border-dashed{padding:.75rem}
    #{{ $galleryId }}_wrap .border-dashed svg{width:1.1rem;height:1.1rem}
    #{{ $galleryId }}_wrap .border-dashed .text-xs{font-size:.7rem}
    #{{ $galleryId }}_wrap .grid{gap:.5rem}
    @media (min-width:768px){
        #{{ $galleryId }}_wrap .grid{grid-template-columns:repeat(6,minmax(0,1fr))}
    }
    @media (min-width:1024px){
        #{{ $galleryId }}_wrap .grid{grid-template-columns:repeat(8,minmax(0,1fr))}
    }
    #{{ $galleryId }}_wrap .gallery-card{border-radius:.5rem}
    #{{ $galleryId }}_wrap .gallery-card .absolute.bottom-2.left-2{font-size:.55rem;padding:.1rem .3rem;border-radius:.25rem}
</style>