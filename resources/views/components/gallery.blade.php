@props([
    'name' => 'gallery',
    'label' => 'Product Gallery',
    'value' => null,
    'error' => null,
    'required' => false,
    'minImages' => 0,
    'maxImages' => 5,
    'bucket' => 'venue-galleries'
])

@php

    $images = [];
    if ($value) {
        if (is_string($value)) {
            $parsed = json_decode($value, true);
            if (is_array($parsed)) $images = $parsed;
        } elseif (is_array($value)) {
            $images = $value;
        }
    }

    try { $galleryId = 'gal_' . bin2hex(random_bytes(5)); }
    catch (\Exception $e) { $galleryId = 'gal_' . preg_replace('/[^A-Za-z0-9_]/', '_', uniqid('', true)); }
@endphp

<div id="{{ $galleryId }}_wrap"
     class="form-group"
     data-skip-filejs="1"
     x-data="galleryComponent_{{ $galleryId }}()"
     x-init="init()"
     x-on:gallery-delete.window="markForDeletion($event.detail)"
     x-on:gallery-undo.window="removeFromDeleteList($event.detail)"
>
    <label class="form-label text-sm font-medium text-gray-800">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
        <span class="ml-2 text-xs text-gray-500 font-normal">({{ $minImages }}–{{ $maxImages }} images)</span>
    </label>

    @if($error)
        <div class="text-red-500 text-xs mt-1">{{ $error }}</div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl p-4 mt-2 shadow-sm">


        <input type="hidden" name="{{ $name }}_existing" value='@json($images)'>
        <input type="hidden" name="{{ $name }}_bucket" value="{{ $bucket }}">

        <template x-for="p in previews" :key="'h-'+p.id">
            <input type="hidden" name="gallery_image_base64[]" :value="p.url">
        </template>

        <div class="gallery-header flex items-center justify-between gap-3 flex-wrap mb-3">
            <div class="header-actions flex items-center gap-3 flex-wrap">
                <button type="button"
                        class="gallery-add-btn inline-flex items-center gap-2 text-sm px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap"
                        @click="triggerFileDialog()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add images
                </button>
                <span class="text-xs text-gray-500">or drag & drop below</span>
            </div>

            <div class="text-xs text-gray-500">
                <span class="font-medium" x-text="totalImages"></span> / {{ $maxImages }}
            </div>
        </div>

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

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

            @foreach($images as $index => $image)
                @php $src = $image['url'] ?? $image['src'] ?? $image['path'] ?? null; @endphp
                <div class="gallery-card relative rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm"
                     x-data="{ deleted:false }">
                    <div class="aspect-square w-full overflow-hidden">
                        <img src="{{ $src }}" class="w-full h-full object-cover" alt="Image {{ $index + 1 }}">
                    </div>

                    <button type="button"
                            class="gallery-x-btn absolute h-10 w-10 rounded-full bg-white text-black border-2 border-black/70
                            hover:bg-gray-100 hover:border-blue-400 shadow focus:outline-none focus:ring-2 focus:ring-black/30 z-50"
                            title="Remove"
                            @click="deleted = true; $dispatch('gallery-delete', { index: {{ $index }} })">
                        <span class="text-lg leading-none">×</span>
                    </button>

                    <div x-cloak x-show="deleted"
                         class="absolute inset-0 z-40 bg-red-600/15 backdrop-blur-[1px] flex flex-col items-center justify-center gap-2">
                        <span class="text-xs font-semibold text-red-700">Marked for deletion</span>
                        <button type="button"
                                class="text-xs px-2 py-1 rounded border border-red-600 text-red-700 bg-white"
                                @click="deleted=false; $dispatch('gallery-undo', { index: {{ $index }} })">
                            Undo
                        </button>
                    </div>

                    <input x-cloak x-show="deleted" type="hidden" name="{{ $name }}_delete[]" value="{{ $index }}">
                </div>
            @endforeach

            <template x-for="(preview, i) in previews" :key="preview.id">
                <div class="gallery-card relative rounded-lg overflow-hidden border border-green-300 bg-white shadow-sm">
                    <div class="aspect-square w-full overflow-hidden">
                        <img :src="preview.url" class="w-full h-full object-cover" :alt="`New image ${i+1}`">
                    </div>

                    <button type="button"
                            class="gallery-x-btn absolute h-10 w-10 rounded-full bg-white text-black border-2 border-black/70
                            hover:bg-gray-100 hover:border-black shadow focus:outline-none focus:ring-2 focus:ring-black/30 z-50"
                            title="Remove"
                            @click="removePreview(i)">
                        <span class="text-lg leading-none">×</span>
                    </button>

                    <span class="absolute bottom-2 left-2 text-[10px] font-semibold px-2 py-0.5 rounded bg-green-600 text-white">NEW</span>
                </div>
            </template>

            <template x-for="i in emptySlots" :key="`empty-${i}`">
                <button type="button"
                        class="aspect-square rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-blue-50 hover:border-blue-400 flex items-center justify-center text-xs text-gray-500"
                        @click="triggerFileDialog()">
                    Add image
                </button>
            </template>
        </div>

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
        function galleryComponent_{{ $galleryId }}() {
            return {
                previews: [],
                deletedIndexes: [],
                existingCount: {{ count($images) }},
                maxImages: {{ $maxImages }},
                isDragging: false,

                init() {},

                // computed
                get totalImages() {
                    return this.existingCount - this.deletedIndexes.length + this.previews.length;
                },
                get emptySlots() {
                    const n = this.maxImages - this.totalImages;
                    return n > 0 ? n : 0;
                },

                // ui actions
                triggerFileDialog() {
                    const el = document.getElementById('gallery-input-{{ $galleryId }}');
                    if (el) el.click();
                },
                onDragOver()  { this.isDragging = true;  },
                onDragLeave() { this.isDragging = false; },
                onDrop(e) {
                    this.isDragging = false;
                    const files = Array.from(e.dataTransfer?.files || []);
                    this.processIncomingFiles(files);
                },
                handleFileSelect(e) {
                    const files = Array.from(e.target.files || []);
                    this.processIncomingFiles(files);
                    e.target.value = '';
                },

                // core
                processIncomingFiles(files) {
                    let remaining = this.maxImages - this.totalImages;
                    if (remaining <= 0) return;

                    if (files.length > remaining) {
                        alert(`You can only add ${remaining} more image(s). Maximum is {{ $maxImages }}.`);
                        files = files.slice(0, remaining);
                    }

                    files.forEach(file => {
                        if (!this.validateFile(file)) return;
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
                validateFile(file) {
                    if (!file.type.startsWith('image/')) {
                        alert(`${file.name} is not an image file.`);
                        return false;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`${file.name} is too large. Maximum size is 5MB.`);
                        return false;
                    }
                    return true;
                },

                // existing images soft-delete
                markForDeletion(payload) {
                    const index = (payload && typeof payload === 'object') ? payload.index : payload;
                    if (!this.deletedIndexes.includes(index)) this.deletedIndexes.push(index);
                },
                removeFromDeleteList(payload) {
                    const index = (payload && typeof payload === 'object') ? payload.index : payload;
                    const i = this.deletedIndexes.indexOf(index);
                    if (i > -1) this.deletedIndexes.splice(i, 1);
                },

                removePreview(i) {
                    this.previews.splice(i, 1);
                }
            }
        }
    </script>
@endpush

<style>
    [x-cloak]{display:none!important}
    .aspect-square{aspect-ratio:1/1}

    #{{ $galleryId }}_wrap .gallery-header{row-gap:.25rem}

    #{{ $galleryId }}_wrap .gallery-card{position:relative}
    #{{ $galleryId }}_wrap .gallery-x-btn{
        position: absolute;
        top: .15rem;
        right: .15rem;
        height: 2.25rem;
        width:  2.25rem;
        border-radius: 9999px;
        background: #fff;
        color: #111;
        border: 2px solid rgba(0,0,0,.85);
        box-shadow: 0 2px 8px rgba(0,0,0,.22);
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: auto;
    }

    #{{ $galleryId }}_wrap .gallery-x-btn:hover{
        background: #f3f4f6;     /* subtle hover */
        border-color: #000;
    }

    #{{ $galleryId }}_wrap .gallery-x-btn svg,
    #{{ $galleryId }}_wrap .gallery-x-btn span{
        pointer-events: none;    /* keep the whole circle clickable */
    }
    #{{ $galleryId }}_wrap .gallery-hidden-input + *{display:none!important}

</style>
