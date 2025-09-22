<div class="mt-5">
    <label class="label block mb-2" for="{{ $name }}">{!! $label !!}</label>

    <!-- Search Box -->
    <div class="mb-4">
        <label class="label block mb-2" for="search-{{ $name }}">Search Location</label>
        <div class="input-group font-normal">
            <input 
                type="text" 
                class="form-control" 
                id="search-{{ $name }}" 
                placeholder="Type an address or place name..."
            >
            <div class="input-group-item btn btn_primary uppercase" onclick="searchLocation('{{ $name }}')">
                <span class="la la-search"></span>
                Search
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <div id="search-results-{{ $name }}" class="mb-4">
        <select class="form-control w-full" id="search-select-{{ $name }}" size="5" style="display: none;">
        </select>
    </div>

    <!-- Map Container -->
    <div id="map-{{ $name }}" class="rounded-lg border border-gray-200" style="height: 400px; margin-bottom: 20px;"></div>

    <!-- Coordinates Section -->
    <div class="flex flex-col gap-4">
        <!-- Latitude Input -->
        <div>
            <label class="label block mb-2" for="latitude-{{ $name }}">Latitude</label>
            <div class="form-control-addon-within">
                <input 
                    type="text" 
                    class="form-control border-none"
                    name="{{ $name }}_latitude" 
                    id="latitude-{{ $name }}" 
                    value="{{ $latitude }}"
                    readonly
                >
                <div class="flex items-center ltr:pr-4 rtl:pl-4">
                    <span class="text-gray-500">°N</span>
                </div>
            </div>
        </div>

        <!-- Longitude Input -->
        <div>
            <label class="label block mb-2" for="longitude-{{ $name }}">Longitude</label>
            <div class="form-control-addon-within">
                <input 
                    type="text" 
                    class="form-control border-none"
                    name="{{ $name }}_longitude" 
                    id="longitude-{{ $name }}" 
                    value="{{ $longitude }}"
                    readonly
                >
                <div class="flex items-center ltr:pr-4 rtl:pl-4">
                    <span class="text-gray-500">°E</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-4">
        <button type="button" class="btn btn_primary uppercase" onclick="getCurrentLocation('{{ $name }}')">
            <span class="la la-location-arrow text-xl leading-none"></span>
            Get Current Location
        </button>
    </div>

    <!-- Hidden input for combined value -->
    <input type="hidden" name="{{ $name }}" id="combined-{{ $name }}" value="{{ $value }}">
    <!-- Hidden input for address (explicit, takes precedence in backend) -->
    <input type="hidden" name="address" id="address-{{ $name }}" value="{{ $address ?? '' }}">

    @if($error || $success)
        <small class="block mt-2 {{ $error ? 'invalid-feedback' : 'valid-feedback' }}">{{ $error ?? $success }}</small>
    @endif
</div>

<!-- Add Leaflet CSS if not already included -->
@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        /* Custom styles for the search results */
        #search-select-{{ $name }} {
            max-height: 200px;
            overflow-y: auto;
            display: none;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
        }
        #search-select-{{ $name }} option {
            padding: 0.5rem;
            cursor: pointer;
        }
        #search-select-{{ $name }} option:hover {
            background-color: #f7fafc;
        }
    </style>
@endpush

<!-- Add Leaflet JS if not already included -->
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeMap('{{ $name }}', {{ $latitude }}, {{ $longitude }});
    
    // Add enter key listener for search
    document.getElementById('search-' + '{{ $name }}').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchLocation('{{ $name }}');
        }
    });

    // Add change listener for search results
    document.getElementById('search-select-' + '{{ $name }}').addEventListener('change', function(e) {
        const selectedOption = this.options[this.selectedIndex];
        const lat = selectedOption.getAttribute('data-lat');
        const lon = selectedOption.getAttribute('data-lon');

        if (lat && lon) {
            const map = window['map_' + '{{ $name }}'];
            const marker = window['marker_' + '{{ $name }}'];
            const latlng = { lat: parseFloat(lat), lng: parseFloat(lon) };
            const addressText = selectedOption.textContent;
            document.getElementById('address-' + '{{ $name }}').value = addressText;

            marker.setLatLng(latlng);
            map.setView(latlng, 16);
            updateCoordinates('{{ $name }}', latlng, addressText);

            this.style.display = 'none';
            document.getElementById('search-' + '{{ $name }}').value = addressText;
        }
    });
});


async function reverseGeocode(lat, lon) {
    const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`,
        { headers: { 'Accept-Language': 'en-US,en;q=0.9' } }
    );
    if (!res.ok) return null;
    const json = await res.json();
    return json.display_name || null;
}

function initializeMap(componentName, defaultLat, defaultLng) {
    var map = L.map('map-' + componentName).setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    marker.on('dragend', async function(e) {
        const ll = e.target.getLatLng();
        const addr = await reverseGeocode(ll.lat, ll.lng);
        updateCoordinates(componentName, ll, addr ?? undefined);
    });

    map.on('click', async function(e) {
        marker.setLatLng(e.latlng);
        const addr = await reverseGeocode(e.latlng.lat, e.latlng.lng);
        updateCoordinates(componentName, e.latlng, addr ?? undefined);
    });

    window['map_' + componentName] = map;
    window['marker_' + componentName] = marker;
}


function updateCoordinates(componentName, latlng, addressText = null) {
    document.getElementById('latitude-' + componentName).value = latlng.lat.toFixed(6);
    document.getElementById('longitude-' + componentName).value = latlng.lng.toFixed(6);

    if (addressText !== null) {
        document.getElementById('search-' + componentName).value = addressText;
        document.getElementById('address-' + componentName).value = addressText;
    }
    const currentAddress = document.getElementById('address-' + componentName).value || null;

    document.getElementById('combined-' + componentName).value = JSON.stringify({
        lat: latlng.lat,
        lng: latlng.lng,
        address: currentAddress
    });
}

async function searchLocation(componentName) {
    const searchInput = document.getElementById('search-' + componentName);
    const searchSelect = document.getElementById('search-select-' + componentName);
    const query = searchInput.value.trim();

    document.getElementById('address-' + componentName).value = query;

    if (!query) return;
    
    try {
        // Show loading state
        searchInput.disabled = true;
        searchSelect.innerHTML = '<option disabled>Searching...</option>';
        searchSelect.style.display = 'block';
        
        // Make request to Nominatim with additional parameters for better results
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`, {
            headers: {
                'Accept-Language': 'en-US,en;q=0.9',
                'User-Agent': 'MommyHAI Location Picker'
            }
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const results = await response.json();
        
        // Clear and populate results
        searchSelect.innerHTML = '';
        
        if (results.length === 0) {
            searchSelect.innerHTML = '<option disabled>No results found</option>';
            searchSelect.style.display = 'block';
            return;
        }
        
        results.forEach((result) => {
            const option = document.createElement('option');
            option.value = result.place_id;
            option.textContent = result.display_name;
            option.setAttribute('data-lat', result.lat);
            option.setAttribute('data-lon', result.lon);
            searchSelect.appendChild(option);
        });
        
        searchSelect.style.display = 'block';
        
    } catch (error) {
        console.error('Search error:', error);
        searchSelect.innerHTML = '<option disabled>Error searching location</option>';
        searchSelect.style.display = 'block';
    } finally {
        searchInput.disabled = false;
    }
}

async function getCurrentLocation(componentName) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(async function(position) {
            const map = window['map_' + componentName];
            const marker = window['marker_' + componentName];
            const latlng = { lat: position.coords.latitude, lng: position.coords.longitude };

            marker.setLatLng(latlng);
            map.setView(latlng, 13);

            const addr = await reverseGeocode(latlng.lat, latlng.lng);
            updateCoordinates(componentName, latlng, addr ?? undefined);
        }, function(error) {
            console.error("Error getting location:", error);
            alert("Could not get your current location. Please check your browser permissions.");
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }
}
</script>