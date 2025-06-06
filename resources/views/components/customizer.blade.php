@if(config('app.customizer_enabled') === true)
    <!-- IMPORTANT: Replace the following line with the contents from included file for Laravel Mix -->
    <!-- Customizer -->
    <aside id="customizer" class="sidebar sidebar_customizer">

        <!-- Togglers -->
        <div class="toggler-wrapper">
            <div>
                <button class="toggler" data-toggle="customizer">
                    <span class="la la-gear animate-spin-slow"></span>
                </button>
                <button class="randomizer mt-2" data-toggle="randomizer">
                    <span class="la la-random"></span>
                </button>
            </div>
        </div>

        <!-- Theme Customizer -->
        <div class="flex items-center justify-between h-20 p-4">
            <div>
                <h2>Theme Customizer</h2>
                <p>Customize & Preview</p>
            </div>
            <button class="close text-2xl leading-none hover:text-primary la la-times" data-toggle="customizer"></button>
        </div>
        <hr>
        <div class="overflow-y-auto">
            <div class="flex items-center justify-between p-4">
                <h5>Dark Mode</h5>
                <label class="switch switch_outlined">
                    <input data-toggle="dark-mode" type="checkbox">
                    <span></span>
                </label>
            </div>
            <hr>
            <div class="flex items-center justify-between p-4">
                <h5>RTL</h5>
                <label class="switch switch_outlined">
                    <input data-toggle="rtl" type="checkbox">
                    <span></span>
                </label>
            </div>
            <hr>
            <div class="flex items-center justify-between p-4">
                <div>
                    <h5>Branded Menu</h5>
                    <small>Menu will be set to primary color</small>
                </div>
                <label class="switch switch_outlined">
                    <input data-toggle="branded-menu" type="checkbox">
                    <span></span>
                </label>
            </div>
            <hr>
            <div class="p-4">
                <h5>Menu Types</h5>
                <div id="customizerMenuTypes" class="flex flex-col space-y-2 mt-5"></div>
            </div>
            <hr>
            <div class="p-4">
                <h5>Themes</h5>
                <div id="customizerThemes" class="themes"></div>
            </div>
            <hr>
            <div class="p-4">
                <div>
                    <h5>50 Shades of Gray</h5>
                    <small>5 x 10 Shades</small>
                </div>
                <div id="customizerGrays" class="themes"></div>
            </div>
            <hr>
            <div class="p-4">
                <h5>Fonts</h5>
                <div id="customizerFonts" class="themes fonts"></div>
            </div>
        </div>
    </aside>
@endif
