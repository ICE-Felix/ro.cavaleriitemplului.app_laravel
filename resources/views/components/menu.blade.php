<!-- Menu Bar -->
<aside class="menu-bar menu-sticky">
    <div class="menu-items">
        <div class="menu-header hidden">
            <a href="/" class="flex items-center mx-8 mt-8">
                <span class="avatar w-16 h-16">JD</span>
                <div class="ltr:ml-4 rtl:mr-4 ltr:text-left rtl:text-right">
                    <h5>John Doe</h5>
                    <p class="mt-2">Editor</p>
                </div>
            </a>
            <hr class="mx-8 my-4">
        </div>
        @isset($menus)
            @foreach($menus as $option)
            @if($option['children'] === null)
                <a href="{{$option['href']}}" class="link" data-toggle="tooltip-menu" data-tippy-content="{{$option['label']}}">
                    <span class="icon la {{$option['icon']}}"></span>
                    <span class="title">{{$option['label']}}</span>
                </a>
            @else
                <button class="link" data-target="[data-menu={{strtolower($option['label'])}}]" data-toggle="tooltip-menu" data-tippy-content="{{$option['label']}}">
                    <span class="icon la {{$option['icon']}}"></span>
                    <span class="title">{{$option['label']}}</span>
                </button>
            @endif
        @endforeach
        @endisset
{{--        <button class="link" data-target="[data-menu=ui]" data-toggle="tooltip-menu" data-tippy-content="UI">--}}
{{--            <span class="icon la la-cube"></span>--}}
{{--            <span class="title">UI</span>--}}
{{--        </button>--}}
{{--        <button class="link" data-target="[data-menu=pages]" data-toggle="tooltip-menu" data-tippy-content="Pages">--}}
{{--            <span class="icon la la-file-alt"></span>--}}
{{--            <span class="title">Pages</span>--}}
{{--        </button>--}}
{{--        <button class="link" data-target="[data-menu=applications]" data-toggle="tooltip-menu"--}}
{{--                data-tippy-content="Applications">--}}
{{--            <span class="icon la la-store"></span>--}}
{{--            <span class="title">Applications</span>--}}
{{--        </button>--}}
{{--        <button class="link" data-target="[data-menu=menu]" data-toggle="tooltip-menu" data-tippy-content="Menu">--}}
{{--            <span class="icon la la-sitemap"></span>--}}
{{--            <span class="title">Menu</span>--}}
{{--        </button>--}}
{{--        <a href="blank.html" class="link" data-toggle="tooltip-menu" data-tippy-content="Blank Page">--}}
{{--            <span class="icon la la-file"></span>--}}
{{--            <span class="title">Blank Page</span>--}}
{{--        </a>--}}
{{--        <a href="https://yeti.yetithemes.net/docs" target="_blank" class="link" data-toggle="tooltip-menu"--}}
{{--           data-tippy-content="Docs">--}}
{{--            <span class="icon la la-book-open"></span>--}}
{{--            <span class="title">Docs</span>--}}
{{--        </a>--}}
    </div>

    <!-- Dashboard -->
    <!--
    <div class="menu-detail" data-menu="dashboard">
        <div class="menu-detail-wrapper">
            <a href="index.html">
                <span class="la la-cube"></span>
                Default
            </a>
            <a href="index.html">
                <span class="la la-file-alt"></span>
                Content
            </a>
            <a href="index.html">
                <span class="la la-shopping-bag"></span>
                Ecommerce
            </a>
        </div>
    </div>
    -->
    @isset($menus)
        @foreach($menus as $option)
            @if($option['children'] !== null)
                @if(!empty($option['children']))
                    <div class="menu-detail" data-menu="{{strtolower($option['label'])}}">
                        <div class="menu-detail-wrapper">
                            @foreach($option['children'] as $suboption)
                                @if(!empty($suboption['category_title']))
                                    <h6 class="uppercase">{{$suboption['category_title']}}</h6>
                                 @endif
                                <a href="{{$suboption['href']}}">
                                    <span class="la {{$suboption['icon']}}"></span>
                                    {{$suboption['label']}}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endforeach
    @endisset

    <!-- UI -->
    <div class="menu-detail" data-menu="ui">
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Form</h6>
            <a href="form-components.html">
                <span class="la la-cubes"></span>
                Components
            </a>
            <a href="form-input-groups.html">
                <span class="la la-stop"></span>
                Input Groups
            </a>
            <a href="form-layout.html">
                <span class="la la-th-large"></span>
                Layout
            </a>
            <a href="form-validations.html">
                <span class="la la-check-circle"></span>
                Validations
            </a>
            <a href="form-wizards.html">
                <span class="la la-hand-pointer"></span>
                Wizards
            </a>
            <hr>
            <h6 class="uppercase">Components</h6>
            <a href="components-alerts.html">
                <span class="la la-bell"></span>
                Alerts
            </a>
            <a href="components-avatars.html">
                <span class="la la-user-circle"></span>
                Avatars
            </a>
            <a href="components-badges.html">
                <span class="la la-certificate"></span>
                Badges
            </a>
            <a href="components-buttons.html">
                <span class="la la-play"></span>
                Buttons
            </a>
            <a href="components-cards.html">
                <span class="la la-layer-group"></span>
                Cards
            </a>
            <a href="components-collapse.html">
                <span class="la la-arrow-circle-right"></span>
                Collapse
            </a>
            <a href="components-colors.html">
                <span class="la la-palette"></span>
                Colors
            </a>
            <a href="components-dropdowns.html">
                <span class="la la-arrow-circle-down"></span>
                Dropdowns
            </a>
            <a href="components-modal.html">
                <span class="la la-times-circle"></span>
                Modal
            </a>
            <a href="components-popovers-tooltips.html">
                <span class="la la-thumbtack"></span>
                Popovers & Tooltips
            </a>
            <a href="components-tabs.html">
                <span class="la la-columns"></span>
                Tabs
            </a>
            <a href="components-tables.html">
                <span class="la la-table"></span>
                Tables
            </a>
            <a href="components-toasts.html">
                <span class="la la-bell"></span>
                Toasts
            </a>
            <hr>
            <h6 class="uppercase">Extras</h6>
            <a href="extras-carousel.html">
                <span class="la la-images"></span>
                Carousel
            </a>
            <a href="extras-charts.html">
                <span class="la la-chart-area"></span>
                Charts
            </a>
            <a href="extras-editors.html">
                <span class="la la-keyboard"></span>
                Editors
            </a>
            <a href="{{ route('calendar') }}">
                <span class="la la-calendar"></span>
                Events Calendar
            </a>
            <a href="extras-sortable.html">
                <span class="la la-sort"></span>
                Sortable
            </a>
        </div>
    </div>

    <!-- Pages -->
    <div class="menu-detail" data-menu="pages">
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Authentication</h6>
            <a href="auth-login.html">
                <span class="la la-user"></span>
                Login
            </a>
            <a href="auth-forgot-password.html">
                <span class="la la-user-lock"></span>
                Forgot Password
            </a>
            <a href="auth-register.html">
                <span class="la la-user-plus"></span>
                Register
            </a>
            <hr>
            <h6 class="uppercase">Blog</h6>
            <a href="blog-list.html">
                <span class="la la-list"></span>
                List
            </a>
            <a href="blog-list-card-rows.html">
                <span class="la la-list"></span>
                List - Card Rows
            </a>
            <a href="blog-list-card-columns.html">
                <span class="la la-list"></span>
                List - Card Columns
            </a>
            <a href="blog-add.html">
                <span class="la la-layer-group"></span>
                Add Post
            </a>
            <hr>
            <h6 class="uppercase">Errors</h6>
            <a href="errors-403.html" target="_blank">
                <span class="la la-exclamation-circle"></span>
                403 Error
            </a>
            <a href="errors-404.html" target="_blank">
                <span class="la la-exclamation-circle"></span>
                404 Error
            </a>
            <a href="errors-500.html" target="_blank">
                <span class="la la-exclamation-circle"></span>
                500 Error
            </a>
            <a href="errors-under-maintenance.html" target="_blank">
                <span class="la la-exclamation-circle"></span>
                Under Maintenance
            </a>
            <hr>
            <a href="pages-pricing.html">
                <span class="la la-dollar"></span>
                Pricing
            </a>
            <a href="pages-faqs-layout-1.html">
                <span class="la la-question-circle"></span>
                FAQs - Layout 1
            </a>
            <a href="pages-faqs-layout-2.html">
                <span class="la la-question-circle"></span>
                FAQs - Layout 2
            </a>
            <a href="pages-invoice.html">
                <span class="la la-file-invoice-dollar"></span>
                Invoice
            </a>
        </div>
    </div>

    <!-- Applications -->
    <div class="menu-detail" data-menu="applications">
        <div class="menu-detail-wrapper">
            <a href="applications-media-library.html">
                <span class="la la-image"></span>
                Media Library
            </a>
            <a href="applications-point-of-sale.html">
                <span class="la la-shopping-bag"></span>
                Point Of Sale
            </a>
            <a href="applications-to-do.html">
                <span class="la la-check-circle"></span>
                To Do
            </a>
            <a href="applications-chat.html">
                <span class="la la-comment"></span>
                Chat
            </a>
        </div>
    </div>

    <!-- Menu -->
    <div class="menu-detail" data-menu="menu">
        <div class="menu-detail-wrapper">
            <a href="#no-link">
                <span class="la la-cube"></span>
                Default
            </a>
            <a href="#no-link">
                <span class="la la-file-alt"></span>
                Content
            </a>
            <a href="#no-link">
                <span class="la la-shopping-bag"></span>
                Ecommerce
            </a>
            <hr>
            <a href="#no-link">
                <span class="la la-layer-group"></span>
                Main Level
            </a>
            <a href="#no-link">
                <span class="la la-arrow-circle-right"></span>
                Grand Parent
            </a>
            <button class="collapsible-header active" data-toggle="collapse" data-target="#menuGrandParentOpen">
                <span class="collapsible-indicator la la-arrow-circle-down"></span>
                Grand Parent Open
            </button>
            <div id="menuGrandParentOpen" class="collapsible open">
                <a href="#no-link">
                    <span class="la la-layer-group"></span>
                    Sub Level
                </a>
                <a href="#no-link">
                    <span class="la la-arrow-circle-right"></span>
                    Parent
                </a>
                <button class="collapsible-header active" data-toggle="collapse" data-target="#menuParentOpen">
                    <span class="collapsible-indicator la la-arrow-circle-down"></span>
                    Parent Open
                </button>
                <div id="menuParentOpen" class="collapsible open">
                    <a href="#no-link">
                        <span class="la la-layer-group"></span>
                        Sub Level
                    </a>
                </div>
            </div>
            <hr>
            <h6 class="uppercase">Menu Types</h6>
            <a href="#no-link" data-toggle="menu-type" data-value="default">
                <span class="la la-hand-point-right"></span>
                Default
            </a>
            <a href="#no-link" data-toggle="menu-type" data-value="hidden">
                <span class="la la-hand-point-left"></span>
                Hidden
            </a>
            <a href="#no-link" data-toggle="menu-type" data-value="icon-only">
                <span class="la la-th-large"></span>
                Icons Only
            </a>
            <a href="#no-link" data-toggle="menu-type" data-value="wide">
                <span class="la la-arrows-alt-h"></span>
                Wide
            </a>
        </div>
    </div>
</aside>
