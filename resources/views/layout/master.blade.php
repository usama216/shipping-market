<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('assets/image/favicon.ico') }}" type="image/x-icon">
    <meta name="description"
        content="Marketsz is the solution to getting all of your orders from any online store and receive it straight at your door. Within just a few days, you will have straight at your door with our trusted, international partners; DHL, FEDEX or UPS.">
    <link rel="stylesheet" href="{{ asset('assets/css/tw.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/web_custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sample-styles.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&family=Itim&display=swap" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="csrf_token" content="{{ csrf_token() }}" />

    <script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js"
        integrity="sha384-rOA1PnstxnOBLzCLMcre8ybwbTmemjzdNlILg8O7z1lUkLXozs4DHonlDtnE7fpc" crossorigin="anonymous">
        </script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>@yield('title')</title>
</head>

<body>
    <div>
        <div class="flex flex-col min-h-screen">
            <div class="data"></div>
            @include('layout.header')
            <div>
                @yield('content')
            </div>
            @include('layout.footer')
        </div>
    </div>
    <script src="{{ asset('assets/js/whale-animate.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/gsap@3.9.0/dist/MotionPathPlugin.min.js"></script>
    <script src="{{ asset('assets/js/sample-script.js') }}"></script>
    @include('scripts.script')
    @yield('script')
</body>

</html>