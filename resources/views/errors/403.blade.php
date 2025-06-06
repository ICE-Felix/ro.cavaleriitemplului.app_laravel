<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="UTF-8" lang="{{ str_replace('_', '-', app()->getLocale()) }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{env('APP_NAME')}}</title>
    @yield('pre-header')

    <!-- Generics -->
    <link rel="icon" href="/assets/images/favicon/favicon.ico">

    <!-- Android -->
    <link rel="shortcut icon" href="/assets/images/favicon/android-chrome-512x512.png">

    <!-- iOS -->
    <link rel="apple-touch-icon" href="/assets/images/favicon/apple-touch-icon.png">

    <link rel="stylesheet" href="/assets/css/style.css"/>
    @yield('header')
</head>

<body>

<!-- Top Bar -->
<section class="top-bar">

    <!-- Brand -->
    <span class="brand">
        @if(config('app.logo_type') === 'text')
            {{config('app.logo') != '' ? config('app.logo')  : 'APP'}}
        @else
            <img style="height: 50px" src="{{config('app.logo')}}" alt="{{env('APP_NAME')}} logo">
        @endif
    </span>
</section>

<!-- Workspace -->
<main class="workspace">
    <div class="container flex items-center justify-center mt-20 py-10">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10 text-center uppercase">
                <h1 class="text-9xl font-bold">403</h1>
                <h2 class="text-primary mt-5">Forbidden</h2>
                <h5 class="mt-2">You don’t have access to this page.</h5>
                <a href="javascript:void(0);" onclick="history.back();" class="btn btn_primary mt-5">Go Back</a>
            </div>
        </div>
    </div>
</main>

<!-- Scripts -->
@yield('pre-footer')
<script src="/assets/js/vendor.js"></script>
<script src="/assets/js/script.js"></script>
@yield('footer')

</body>

</html>
