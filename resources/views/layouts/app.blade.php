<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="BizTorgUz — бесплатная платформа для размещения объявлений. Покупайте и продавайте транспорт, недвижимость, электронику, товары для дома и сада. Размещайте услуги и предложения для бизнеса. Присоединяйтесь к крупнейшей базе объявлений в Узбекистане!">
        <meta property="og:title" content="BizTorgUz - Бесплатная доска обьявлений: Транспорт, Недвижимость, Электроника, Услуги и Дом" />
        <meta property="og:description" content="BizTorgUz — бесплатная платформа для размещения объявлений. Покупайте и продавайте транспорт, недвижимость, электронику, товары для дома и сада. Размещайте услуги и предложения для бизнеса. Присоединяйтесь к крупнейшей базе объявлений в Узбекистане!" />
        <meta property="og:url" content="https://biztorg.uz/" />
        <meta property="og:image" content="https://biztorg.uz/logo.jpg" />
        <meta property="og:type" content="website" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="BizTorgUz - Бесплатная доска обьявлений: Транспорт, Недвижимость, Электроника, Услуги и Дом" />
        <meta name="twitter:description" content="BizTorgUz — бесплатная платформа для размещения объявлений. Покупайте и продавайте транспорт, недвижимость, электронику, товары для дома и сада. Размещайте услуги и предложения для бизнеса. Присоединяйтесь к крупнейшей базе объявлений в Узбекистане!" />
        <meta name="twitter:image" content="https://biztorg.uz/logo.jpg" />

        <link rel="icon" type="image/png" href="/my-favicon/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/my-favicon/favicon.svg" />
        <link rel="shortcut icon" href="/my-favicon/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/my-favicon/apple-touch-icon.png" />
        <link rel="manifest" href="/my-favicon/site.webmanifest" />

        @yield('meta')

        <title>@yield('title', 'BizTorgUz - Бесплатная доска обьявлений: Транспорт, Недвижимость, Электроника, Услуги и Дом')</title>

        <link rel="icon" href="https://biztorg.uz/logo.jpg" type="image/x-icon">

        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebPage",
                "name": "BizTorgUz - Бесплатная доска обьявлений: Транспорт, Недвижимость, Электроника, Услуги и Дом",
                "description": "BizTorgUz — бесплатная платформа для размещения объявлений. Покупайте и продавайте транспорт, недвижимость, электронику, товары для дома и сада. Размещайте услуги и предложения для бизнеса. Присоединяйтесь к крупнейшей базе объявлений в Узбекистане!",
                "url": "https://biztorg.uz/",
                "sameAs": [
                    "https://www.facebook.com/profile.php?id=61570125598203",
                    "https://www.instagram.com/biztorg/",
                    "https://t.me/s/biztorguz"
                ],
                "image": "https://biztorg.uz/logo.jpg"
            }
            </script>
            

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
