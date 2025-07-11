<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="UTF-8" lang="{{ str_replace('_', '-', app()->getLocale()) }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Select all forms on the page
            const forms = document.querySelectorAll('form');

            forms.forEach(function(form) {
                // Assuming the first submit input/button in the form is the one to disable
                // If you're using a class to mark your submit buttons, adjust the selector accordingly
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');

                form.addEventListener('submit', function () {
                    if (submitBtn) {
                        submitBtn.disabled = true; // Disable the button
                        // Change the button text or value if needed
                        if (submitBtn.tagName.toLowerCase() === 'button') {
                            submitBtn.innerText = 'Processing...';
                        } else if (submitBtn.tagName.toLowerCase() === 'input') {
                            submitBtn.value = 'Processing...';
                        }
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/trix@2.1.15/dist/trix.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/trix@2.1.15/dist/trix.min.css" rel="stylesheet">
    <link href="/assets/css/trix.css" rel="stylesheet">

    <title>{{env('APP_NAME')}}</title>
    @yield('pre-header')

    <!-- Generics -->
    <link rel="icon" href="/assets/images/favicon/favicon.ico">

    <!-- Android -->
    <link rel="shortcut icon" href="/assets/images/favicon/android-chrome-512x512.png">

    <!-- iOS -->
    <link rel="apple-touch-icon" href="/assets/images/favicon/apple-touch-icon.png">

    <link rel="stylesheet" href="/assets/css/style.css"/>
    @vite(['resources/js/app.js'])
    @yield('header')
    @stack('styles')
</head>

<body>

@include('components.top-bar')

@include('components.menu')

@include('components.customizer')

<!-- Workspace -->
<main class="workspace">

    <!-- Breadcrumb -->
    <section class="breadcrumb">
        <h1>@yield('page_title', 'Page title')</h1>

        @if(!empty($breadcrumbs))
            <ul>
                @foreach ($breadcrumbs as $breadcrumb)
                    @if ($loop->last)
                        <li>@yield('page_title', 'Page title')</li>
                    @else
                        <li><a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a></li>
                        <li class="divider la la-arrow-right"></li>
                    @endif
                @endforeach
            </ul>
        @endif

    </section>

    {{ $slot }}

    <!-- Footer -->
    <footer class="mt-auto">
        <div class="footer">
            <span class='uppercase'>&copy; {{date('Y')}} {{env('APP_NAME', 'app')}}</span>
            <nav>
                <a href="{{ config('links.support_link') }}">Support</a>
                <span class="divider">|</span>
                <a href="{{ config('links.documentation.user_guide') }}" target="_blank" rel="noreferrer">Docs</a>
            </nav>
        </div>
    </footer>

</main>

<!-- Scripts -->
@yield('pre-footer')
<script src="/assets/js/vendor.js"></script>
<script src="/assets/js/script.js"></script>
<script>
    function confirmDelete(event) {
        event.preventDefault(); // Prevent the form from submitting immediately
        const confirmed = confirm('Are you sure you want to delete this?');

        if (confirmed) {
            // If confirmed, find the form by ID and submit it
            event.target.closest('form').submit();
        }
    }
</script>
@yield('footer')
@stack('scripts')

</body>

</html>
