<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @yield('meta')

        <title>@yield('title', 'BizTorg')</title>

        <!-- Fonts -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>




<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-black text-white">
        <header>@include('layout_components.header')</header>
        <main class="p-6">
            @yield('main')
        </main>
        <footer>@include('layout_components.footer')</footer>

        <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
        
    </body>
</html>
