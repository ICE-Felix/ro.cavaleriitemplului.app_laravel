@props([
  'name' => 'parent_ids',
  'label' => 'Parents',
  'options' => [],   // [id => name]
  'value' => [],
  'placeholder' => 'Select parent…',
  'required' => false,
  'error' => null,
])

@php
    $cid = 'multi_' . bin2hex(random_bytes(4));
    $inputId = $cid . '_input';

    $initial = is_string($value) ? (json_decode($value, true) ?: []) : (array)$value;
    $initial = array_values(array_unique(array_map('strval', $initial)));

    $opts = [];
    foreach ($options as $k => $v) $opts[] = ['id'=>(string)$k,'name'=>(string)$v];
@endphp

<div id="{{ $cid }}" x-data="multi{{ $cid }}()" x-init="init()" class="form-group" x-on:click.outside="open=false">
    <label class="label block mb-2" for="{{ $inputId }}">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <div class="token-box"
         @pointerdown.capture="$refs.input.focus()"
         role="combobox"
         aria-haspopup="listbox"
         :aria-expanded="open ? 'true' : 'false'"
         :aria-owns="'{{ $cid }}_listbox'">

        <template x-for="it in selected" :key="it.id">
          <span class="chip">
            <span x-text="it.name"></span>
            <button type="button" class="x" @click.stop="remove(it.id)" aria-label="Remove">×</button>
          </span>
        </template>

        <input
                id="{{ $inputId }}"
                x-ref="input"
                type="text"
                class="inp"
                :placeholder="selected.length ? '' : placeholder"
                x-model="q"
                @focus="filter(); open = filtered.length > 0"
                @input="filter()"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="choose(active)"
                @keydown.tab="if(open){choose(active); $event.preventDefault()}"
                @keydown.backspace="backspace"
                aria-autocomplete="list"
                :aria-controls="'{{ $cid }}_listbox'"
                :aria-activedescendant="open && filtered[active] ? '{{ $cid }}_opt_' + filtered[active].id : null"
        >
    </div>

    <div class="dd" x-show="open" @mousedown.prevent role="listbox" id="{{ $cid }}_listbox">
        <template x-for="(o,i) in filtered" :key="o.id">
            <div class="row"
                 :id="'{{ $cid }}_opt_' + o.id"
                 role="option"
                 :aria-selected="i===active ? 'true' : 'false'"
                 :class="{'on': i===active}"
                 @mouseenter="active=i"
                 @click="choose(i)">
                <span x-text="o.name"></span>
            </div>
        </template>
        <div class="empty" x-show="filtered.length===0">No results</div>
    </div>

    <template x-for="it in selected" :key="'h-'+it.id">
        <input type="hidden" name="{{ $name }}[]" :value="it.id">
    </template>

    @if($error)
        <small class="invalid-feedback block mt-2">{{ $error }}</small>
    @endif
</div>

@push('styles')
    <style>
        #{{ $cid }} .token-box{
            display:flex;flex-wrap:wrap;gap:.375rem;align-items:center;
            min-height:2.5rem;padding:.375rem .5rem;background:#fff;
            border:1px solid #e5e7eb;border-radius:.375rem;
            cursor:text;
        }
        #{{ $cid }} .token-box:focus-within{
             border-color:#3b82f6; /* Tailwind blue-500 */
             box-shadow:0 0 0 3px rgba(59,130,246,.15);
         }
        #{{ $cid }} .chip{
             display:inline-flex;align-items:center;gap:.25rem;
             padding:.125rem .5rem;background:#f3f4f6;border:1px solid #e5e7eb;
             border-radius:999px;font-size:.8125rem
         }
        #{{ $cid }} .chip .x{border:0;background:transparent;cursor:pointer;font-size:1rem;line-height:1;padding:0 .25rem}
        #{{ $cid }} .inp{flex:1 1 auto;min-width:8rem;border:0;outline:0;padding:.25rem;font-size:.875rem}
        #{{ $cid }} .dd{
             margin-top:.25rem;border:1px solid #e5e7eb;border-radius:.375rem;background:#fff;
             max-height:240px;overflow:auto;box-shadow:0 4px 10px rgba(0,0,0,.06)
         }
        #{{ $cid }} .row{padding:.5rem .625rem;cursor:pointer}
        #{{ $cid }} .row.on, #{{ $cid }} .row:hover{background:#f3f4f6}
        #{{ $cid }} .empty{padding:.5rem .625rem;color:#9ca3af}
    </style>
@endpush

@push('scripts')
    <script>
        function multi{{ $cid }}(){
            return {
                all: @json($opts),
                selected: [],
                q: '',
                filtered: [],
                open: false,
                active: 0,
                placeholder: @json($placeholder),

                init(){
                    const ids = @json($initial);
                    this.selected = ids
                        .map(id => this.all.find(o => o.id === String(id)))
                        .filter(Boolean);
                    this.filter();
                    // Escape closes
                    document.addEventListener('keydown', e => { if(e.key==='Escape') this.open=false; });
                },

                normalize(s){
                    return (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
                },

                filter(){
                    const s = new Set(this.selected.map(x => x.id));
                    const qn = this.normalize(this.q.trim());
                    let list = this.all.filter(o => !s.has(o.id));

                    if (qn) {
                        list = list.filter(o => this.normalize(o.name).includes(qn));
                    }

                    list.sort((a,b)=> (a.name||'').localeCompare(b.name||''));
                    this.filtered = list.slice(0,50);
                    this.active = 0;
                    this.open = this.filtered.length > 0;
                },

                choose(i){
                    const o = this.filtered[i];
                    if(!o) return;
                    this.selected.push(o);
                    this.q = '';
                    this.filter();
                    this.$nextTick(()=>this.$refs.input.focus());
                },

                remove(id){
                    this.selected = this.selected.filter(x => x.id !== String(id));
                    this.filter();
                },

                move(d){
                    if(!this.open){ this.open = this.filtered.length > 0; return; }
                    const n = this.filtered.length; if(!n) return;
                    this.active = (this.active + d + n) % n;
                },

                backspace(){
                    if(this.q.length===0 && this.selected.length>0){
                        this.remove(this.selected[this.selected.length-1].id);
                    }
                },
            }
        }
    </script>
@endpush
