@props([
    'countyValue' => null,
    'cityValue' => null,
    'required' => false
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- County Selector -->
    <div data-field="county">
        <label class="label block mb-2" for="county">
            County
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
        <div class="custom-select">
            <select
                    id="county"
                    name="county"
                    class="form-control county-selector"
                    {{ $required ? 'required' : '' }}
            >
                <option value="">Select County</option>
            </select>
            <div class="custom-select-icon la la-caret-down"></div>
        </div>
    </div>

    <!-- City Selector -->
    <div data-field="city">
        <label class="label block mb-2" for="city">
            City
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
        <div class="custom-select">
            <select
                    id="city"
                    name="city"
                    class="form-control city-selector"
                    {{ $required ? 'required' : '' }}
                    disabled
            >
                <option value="">Select City</option>
            </select>
            <div class="custom-select-icon la la-caret-down"></div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container {
                width: 100% !important;
            }
            .select2-container--default .select2-selection--single {
                height: 42px;
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                padding: 0.5rem 0.75rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 26px;
                padding-left: 0;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
                right: 10px;
            }
            .select2-container--default.select2-container--disabled .select2-selection--single {
                background-color: #f3f4f6;
                cursor: not-allowed;
            }
        </style>
    @endpush

    @push('scripts')
        <!-- Ensure jQuery is loaded first -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            (function($) {
                'use strict';

                $(document).ready(function() {
                    console.log('Initializing county-city selector...');

                    // Romanian Counties and Cities Data
                    const romaniaData = {
                        "Alba": ["Alba Iulia", "Aiud", "Blaj", "Sebeș", "Cugir", "Ocna Mureș", "Câmpeni", "Abrud", "Zlatna"],
                        "Arad": ["Arad", "Chișineu-Criș", "Ineu", "Lipova", "Pâncota", "Pecica", "Sântana", "Sebiș"],
                        "Argeș": ["Pitești", "Câmpulung", "Curtea de Argeș", "Mioveni", "Costești", "Ștefănești", "Topoloveni"],
                        "Bacău": ["Bacău", "Moinești", "Onești", "Comănești", "Buhuși", "Dărmănești", "Slănic-Moldova"],
                        "Bihor": ["Oradea", "Beiuș", "Salonta", "Marghita", "Aleșd", "Valea lui Mihai", "Săcueni", "Nucet"],
                        "Bistrița-Năsăud": ["Bistrița", "Beclean", "Năsăud", "Sângeorz-Băi"],
                        "Botoșani": ["Botoșani", "Dorohoi", "Darabani", "Săveni", "Flămânzi"],
                        "Brașov": ["Brașov", "Făgăraș", "Săcele", "Codlea", "Zărnești", "Predeal", "Râșnov", "Victoria"],
                        "Brăila": ["Brăila", "Ianca", "Însurăței", "Făurei"],
                        "București": ["Sectorul 1", "Sectorul 2", "Sectorul 3", "Sectorul 4", "Sectorul 5", "Sectorul 6"],
                        "Buzău": ["Buzău", "Râmnicu Sărat", "Nehoiu", "Pogoanele", "Pătârlagele"],
                        "Caraș-Severin": ["Reșița", "Caransebeș", "Bocșa", "Anina", "Moldova Nouă", "Oravița", "Oțelu Roșu"],
                        "Călărași": ["Călărași", "Oltenița", "Lehliu Gară", "Budești", "Fundulea"],
                        "Cluj": ["Cluj-Napoca", "Turda", "Dej", "Câmpia Turzii", "Gherla", "Huedin"],
                        "Constanța": ["Constanța", "Mangalia", "Medgidia", "Năvodari", "Cernavodă", "Eforie", "Techirghiol", "Murfatlar", "Ovidiu"],
                        "Covasna": ["Sfântu Gheorghe", "Târgu Secuiesc", "Covasna", "Baraolt", "Întorsura Buzăului"],
                        "Dâmbovița": ["Târgoviște", "Moreni", "Pucioasa", "Găești", "Titu", "Fieni", "Răcari"],
                        "Dolj": ["Craiova", "Băilești", "Calafat", "Dăbuleni", "Segarcea", "Filiaș"],
                        "Galați": ["Galați", "Tecuci", "Târgu Bujor", "Berești"],
                        "Giurgiu": ["Giurgiu", "Bolintin-Vale", "Mihăilești"],
                        "Gorj": ["Târgu Jiu", "Motru", "Rovinari", "Novaci", "Bumbeşti-Jiu", "Tismana", "Țicleni"],
                        "Harghita": ["Miercurea Ciuc", "Odorheiu Secuiesc", "Gheorgheni", "Toplița", "Cristuru Secuiesc", "Bălan", "Borsec"],
                        "Hunedoara": ["Deva", "Hunedoara", "Petroșani", "Vulcan", "Lupeni", "Orăștie", "Brad", "Simeria", "Petrila", "Aninoasa"],
                        "Ialomița": ["Slobozia", "Fetești", "Urziceni", "Țăndărei", "Amara", "Fierbinți-Târg"],
                        "Iași": ["Iași", "Pașcani", "Hârlău", "Târgu Frumos", "Podu Iloaiei"],
                        "Ilfov": ["Buftea", "Chitila", "Măgurele", "Otopeni", "Pantelimon", "Popești-Leordeni", "Voluntari", "Bragadiru", "1 Decembrie", "Afumați", "Băneasa", "Balotești", "Berceni", "Cernica", "Chiajna", "Clinceni", "Copăceni", "Corbeanca", "Cornetu", "Dărăști-Ilfov", "Domnești", "Dragomirești-Vale", "Găneasa", "Glina", "Grădiștea", "Gruiu", "Jilava", "Moara Vlăsiei", "Mogoșoaia", "Petrăchioaia", "Snagov", "Ștefăneștii de Jos", "Tunari", "Vidra"],
                        "Maramureș": ["Baia Mare", "Sighetu Marmației", "Borșa", "Vișeu de Sus", "Târgu Lăpuș", "Cavnic", "Săliștea de Sus", "Seini"],
                        "Mehedinți": ["Drobeta-Turnu Severin", "Orșova", "Strehaia", "Vânju Mare"],
                        "Mureș": ["Târgu Mureș", "Reghin", "Sighișoara", "Târnăveni", "Luduș", "Iernut", "Sovata", "Miercurea Nirajului"],
                        "Neamț": ["Piatra Neamț", "Roman", "Târgu Neamț", "Roznov", "Bicaz"],
                        "Olt": ["Slatina", "Caracal", "Balș", "Corabia", "Scornicești", "Drăgănești-Olt", "Piatra-Olt", "Potcoava"],
                        "Prahova": ["Ploiești", "Câmpina", "Mizil", "Băicoi", "Vălenii de Munte", "Sinaia", "Bușteni", "Azuga", "Urlați", "Breaza", "Comarnic", "Slănic", "Boldești-Scăeni"],
                        "Satu Mare": ["Satu Mare", "Carei", "Negrești-Oaș", "Ardud", "Livada", "Tășnad"],
                        "Sălaj": ["Zalău", "Șimleu Silvaniei", "Jibou", "Cehu Silvaniei"],
                        "Sibiu": ["Sibiu", "Mediaș", "Cisnădie", "Agnita", "Avrig", "Copșa Mică", "Dumbrăveni", "Miercurea Sibiului", "Ocna Sibiului", "Tălmaciu"],
                        "Suceava": ["Suceava", "Fălticeni", "Rădăuți", "Câmpulung Moldovenesc", "Vatra Dornei", "Gura Humorului", "Siret", "Broșteni", "Solca", "Salcea", "Vicovu de Sus"],
                        "Teleorman": ["Alexandria", "Roșiorii de Vede", "Turnu Măgurele", "Videle", "Zimnicea"],
                        "Timiș": ["Timișoara", "Lugoj", "Sânnicolau Mare", "Jimbolia", "Buziaș", "Deta", "Făget", "Recaș"],
                        "Tulcea": ["Tulcea", "Babadag", "Măcin", "Sulina", "Isaccea"],
                        "Vaslui": ["Vaslui", "Bârlad", "Huși", "Murgeni", "Negrești"],
                        "Vâlcea": ["Râmnicu Vâlcea", "Drăgășani", "Băbeni", "Călimănești", "Brezoi", "Băile Olănești", "Ocnele Mari", "Horezu", "Bălcești"],
                        "Vrancea": ["Focșani", "Adjud", "Mărășești", "Odobești", "Panciu"]
                    };

                    const countySelect = $('#county');
                    const citySelect = $('#city');

                    console.log('County select found:', countySelect.length);
                    console.log('City select found:', citySelect.length);

                    // Initialize Select2
                    countySelect.select2({
                        placeholder: 'Select County',
                        allowClear: true,
                        width: '100%'
                    });

                    citySelect.select2({
                        placeholder: 'Select City',
                        allowClear: true,
                        width: '100%'
                    });

                    // Populate counties
                    const counties = Object.keys(romaniaData).sort();
                    console.log('Populating', counties.length, 'counties');

                    counties.forEach(function(county) {
                        countySelect.append(new Option(county, county, false, false));
                    });

                    // Trigger update for Select2
                    countySelect.trigger('change.select2');

                    // Set initial values
                    const initialCounty = '{{ $countyValue }}';
                    const initialCity = '{{ $cityValue }}';

                    console.log('Initial county:', initialCounty);
                    console.log('Initial city:', initialCity);

                    if (initialCounty) {
                        countySelect.val(initialCounty).trigger('change');
                    }

                    // Handle county change
                    countySelect.on('change', function() {
                        const selectedCounty = $(this).val();
                        console.log('County changed to:', selectedCounty);

                        // Clear city select
                        citySelect.empty();
                        citySelect.append(new Option('Select City', '', false, false));

                        if (selectedCounty && romaniaData[selectedCounty]) {
                            // Enable city select
                            citySelect.prop('disabled', false);

                            // Populate cities
                            const cities = romaniaData[selectedCounty].sort();
                            console.log('Populating', cities.length, 'cities for', selectedCounty);

                            cities.forEach(function(city) {
                                citySelect.append(new Option(city, city, false, false));
                            });

                            citySelect.trigger('change.select2');

                            // Set initial city if provided and county matches
                            if (initialCity && selectedCounty === initialCounty) {
                                setTimeout(function() {
                                    citySelect.val(initialCity).trigger('change');
                                    console.log('Set initial city:', initialCity);
                                }, 100);
                            }
                        } else {
                            // Disable city select
                            citySelect.prop('disabled', true);
                            citySelect.trigger('change.select2');
                        }
                    });

                    // Trigger initial county change if value exists
                    if (initialCounty) {
                        setTimeout(function() {
                            countySelect.trigger('change');
                        }, 100);
                    }

                    console.log('County-city selector initialized successfully');
                });
            })(jQuery);
        </script>
    @endpush
@endonce