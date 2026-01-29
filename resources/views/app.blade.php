<!DOCTYPE html data-theme="light">
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/image/apple-touch-icon.png') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/registration.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">

    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead

    <!-- GTranslate Widget - All Languages -->
    <script>
        window.gtranslateSettings = {
            "default_language": "en",
            "native_language_names": true,
            "detect_browser_language": true,
            "wrapper_selector": ".gtranslate_wrapper",
            "float_switcher_open_direction": "top"
        }
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>

    <!-- LiveChat -->
    <script>
        window.__lc = window.__lc || {};
        window.__lc.license = 12524322;

        (function () {
            var lc = document.createElement('script');
            lc.async = true;
            lc.type = 'text/javascript';
            lc.src = 'https://cdn.livechatinc.com/tracking.js';

            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(lc, s);
        })();
    </script>

    <noscript>
        <a href="https://www.livechat.com/chat-with/12524322/" rel="nofollow">Chat with us</a>
        powered by <a href="https://www.livechat.com/?welcome" rel="noopener nofollow" target="_blank">LiveChat</a>
    </noscript>

    <!-- Facebook Pixel Code -->
    @if(config('facebook.enabled') && config('facebook.pixel_id'))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ config('facebook.pixel_id') }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ config('facebook.pixel_id') }}&ev=PageView&noscript=1"/>
    </noscript>
    @endif

    <!-- AdRoll Pixel Code -->
    @if(config('adroll.enabled') && config('adroll.adv_id') && config('adroll.pix_id'))
    <script type="text/javascript">
        adroll_adv_id = "{{ config('adroll.adv_id') }}";
        adroll_pix_id = "{{ config('adroll.pix_id') }}";
        adroll_version = "2.0";
        (function(w, d, e, o, a) {
            w.__adroll_loaded = true;
            w.adroll = w.adroll || [];
            w.adroll.f = [ 'setProperties', 'identify', 'track' ];
            var roundtripUrl = "https://s.adroll.com/j/" + adroll_adv_id + "/roundtrip.js";
            for (a = 0; a < w.adroll.f.length; a++) {
                w.adroll[w.adroll.f[a]] = w.adroll[w.adroll.f[a]] || (function(n) {
                    return function() {
                        w.adroll.push([ n, arguments ])
                    }
                })(w.adroll.f[a])
            }
            e = d.createElement('script');
            o = d.getElementsByTagName('script')[0];
            e.async = 1;
            e.src = roundtripUrl;
            o.parentNode.insertBefore(e, o);
        })(window, document);
        adroll.track("pageView");
    </script>
    @endif
</head>

@php
    $overFlow = in_array(request()->route()->getName(), ['login', 'register', 'customer.shipment.success'])
        ? 'overflow-y-scroll'
        : 'overflow-hidden';
@endphp

<body class="font-sans antialiased bg-gray-100 flex flex-col h-screen {{ $overFlow }} text-black">
    <!-- GTranslate Container -->
    <div class="gtranslate_wrapper"></div>
    
    @inertia
</body>

</html>