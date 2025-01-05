@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
    <meta name="keywords" content="{{ $product->name }}, {{ $product->subcategory->name }}, {{ $product->price }} {{ $product->currency === 'доллар' ? '$' : 'сум' }}">
    <meta property="og:title" content="Обьявление: {{ $product->name }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
    <meta property="og:image" content="{{ $product->images->isNotEmpty() ? asset('storage/' . $product->images->first()->image_url) : asset('default.png') }}">
    <meta property="og:url" content="{{ route('product.get', $product->slug) }}">
    <meta property="og:type" content="product">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Обьявление: {{ $product->name }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($product->description), 160) }}">
    <meta name="twitter:image" content="{{ $product->images->isNotEmpty() ? asset('storage/' . $product->images->first()->image_url) : asset('default.png') }}">
@endsection



@section('title', 'Обьявление: ' . $product->name . ' - ' . $product->subcategory->name)

@extends('layouts.app')

@section('main')
<div class="container mx-auto my-8 px-2">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 col-span-1 border border-gray-900 rounded-lg p-6">
            <div class="relative">
                <div class="swiper">
                    <div class="swiper-wrapper">
                        @foreach ($product->images as $image)
                        <div class="swiper-slide">
                            <img src="{{ asset('storage/' . $image->image_url) }}" alt="Product Image" class="w-full h-[500px] object-cover rounded-lg">
                        </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-span-1 border border-gray-800 py-2 rounded-lg">
            <div class="border-b border-gray-900 px-4 py-3">
                <h1 class="text-3xl font-bold text-white leading-tight">{{ $product->name }}</h1>
                <div class="flex items-center gap-4 justify-between">
                    <h2 class="text-xl font-medium text-white text-opacity-80 mb-2 leading-tight">{{ $product->subcategory->name }}</h2>
                    <button 
                    class="mt-2 text-gray-400 hover:text-red-500 text-3xl favorite-btn" 
                    data-product-id="{{ $product->id }}">
                    @auth
                    <i class="fas fa-heart text-xl {{ $user->favoriteProducts->contains($product->id) ? 'favorited' : '' }}"></i>
                    @endauth
                    @guest
                    <i class="fas fa-heart text-3xl"></i>
                    @endguest
                </button>
                </div>
                <div class="flex justify-start items-center gap-6">
                    <p class="text-xl text-white font-bold mt-4">{{ $product->price }} {{$product->currency == 'доллар' ? 'у.e.' : 'сум'}}</p>
                    <p class="text-white underline py-1 mt-4">{{$product->type == 'sale' ? 'продажа' : 'покупка'}}</p>
                </div>
                <div>
                    <p class="mt-4 text-gray-600">Опубликовано {{ $product->created_at->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
                </div>
            </div>

            <!-- User Info -->
            <div class="border border-gray-900 rounded-lg p-4 mt-4">
                <h2 class="text-lg font-bold text-white mb-4">Пользователь</h2>
                <div class="flex items-center gap-4">
                    @if ($user->profile->avatar)
                    <div class="w-14 h-14 bg-gray-600 rounded-full overflow-hidden">
                        <img src="{{ asset('storage/' . $user->profile->avatar) }}" alt="User Avatar" class="w-full h-full object-cover">
                    </div>
                    @else
                    <div class="w-14 h-14 bg-gray-600 rounded-full overflow-hidden">
                        <img src="{{ asset('nofoundproduct.webp') }}" alt="User Avatar" class="w-full h-full object-cover">
                    </div>
                    @endif
                    <div>
                        <h3 class="text-lg text-white font-semibold">{{ $user->name }}</h3>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <div class="bg-white text-black w-9 h-9 flex items-center justify-center rounded-full">
                        <i class='bx bx-phone text-black text-2xl'></i>
                    </div>                  
                    <span class="uppercase">{{ $user->profile->phone ?? '+ Add Phone Number' }}</span>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <div class="bg-white text-black w-9 h-9 flex items-center justify-center rounded-full">
                        <i class='bx bx-map text-black text-2xl'></i>
                    </div>
                    <span>{{ $user->profile->address }}</span>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <div class="bg-white text-black w-9 h-9 flex items-center justify-center rounded-full">
                        <i class='bx bx-envelope text-black text-2xl'></i>
                    </div>
                    <span>{{ $user->email }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Attributes and Map Section -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <!-- Attributes -->
        <div class="lg:col-span-1 col-span-1 border border-gray-900 p-4 rounded-lg">
            @foreach ($attributes as $attribute)
            <div class="flex justify-between py-3 border-b border-gray-900 pb-2 -mx-4 px-4">
                <h3 class="text-lg font-medium text-gray-200">{{ $attribute->name }}</h3>
                @foreach ($attribute->attributeValues as $value)
                <p class="text-lg font-medium text-gray-400">{{ $value->value }}</p>
                @endforeach
            </div>
            @endforeach
        </div>
    
        <!-- Map -->
        <div class="lg:col-span-1 col-span-1 border border-gray-900 p-4 rounded-lg">
            <h3 class="text-lg font-medium text-gray-200 mb-4">Местоположение обьявления</h3>
            <div id="map" style="height: 400px; border-radius: 10px;"></div>
            <div class="mt-4 flex items-center gap-4">
                <a href="https://www.google.com/maps?q={{ $product->latitude }},{{ $product->longitude }}" 
                    target="_blank" 
                    class="bg-blue-600 mt-2 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                     Открыть в Google Maps
                 </a>
                 <a href="https://yandex.ru/maps/?ll={{ $product->longitude }},{{ $product->latitude }}&pt={{ $product->longitude }},{{ $product->latitude }}&z=17" 
                    target="_blank" 
                    class="bg-red-700 mt-2 text-white py-2 px-4 rounded hover:bg-red-600 transition">
                     Открыть в Yandex
                 </a>                 
            </div>
            <div class="mt-4 flex items-center gap-4">
                <button id='btn-direction'
                    class=" mt-2 text-white border border-gray-900 flex items-center gap-2 py-2 px-4 rounded-lg transition">
                    <i class='bx bxs-direction-left text-xl'></i>
                     <span>Показать маршрут</span> 
                 </button>    
            </div>
        </div>
    </section>
    
    <section class="mt-8 grid grid-cols-3 gap-8">
        <div class="col-span-3 lg:col-span-2 border border-gray-900 p-4 rounded-lg">
            <h2 class="text-xl text-white font-semibold mb-2 border-b border-gray-900 pb-2 -mx-4 px-4">Описание</h2>
            <p class="text-lg text-white font-medium break-words">{{$product->description}}</p>
        </div>

        {{-- <div class="col-span-3 lg:col-span-1 border border-gray-900 p-4 rounded-lg">
            <h2 class="text-xl text-white font-semibold mb-2 border-b border-gray-900 pb-2">Related Content</h2>
            <p class="text-lg text-white font-medium break-words">This is the sidebar or additional content area.</p>
        </div> --}}
    </section>
    

    <section class="border border-gray-900 rounded-lg border-opacity-65 p-4 mt-6">
    <h2 class="px-2 mb-4 text-xl text-white font-medium">Обьявления данного автора:</h2>
    <section class="flex gap-4 px-2 overflow-x-auto ">
        @foreach ($userProducts as $userProduct)
        <a href="{{ route('product.get', $userProduct->slug) }}" class="block flex-none w-[280px]">
            <div class="border rounded-lg overflow-hidden shadow-md bg-black border-gray-900 hover:shadow-lg transition-shadow">
                <div class="relative">
                    <!-- Image -->
                    <img src="{{ $userProduct->images->isNotEmpty() ? asset('storage/' . $userProduct->images->first()->image_url) : asset('default.png') }}" 
                         alt="{{ $userProduct->name }}" 
                         class="object-cover w-full transition-transform duration-300 ease-in-out transform hover:scale-105 h-[200px]">
                </div>
                <div class="p-4">
                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-100 truncate w-full" style="max-width: 100%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                        {{$userProduct->name}}
                    </h3>                    
                    <!-- Price -->
                    <div class="flex items-center  justify-between">
                    <p class="text-xl font-bold text-gray-100 mt-2">{{$userProduct->price}} {{$userProduct->currency == 'доллар' ? 'у.e.' : 'сум'}}</p>

                    <button 
                    class="mt-2 text-gray-400 hover:text-red-500 text-xl favorite-btn" 
                    data-product-id="{{ $userProduct->id }}">
                    @auth
                    <i class="fas fa-heart text-xl {{ $user->favoriteProducts->contains($userProduct->id) ? 'favorited' : '' }}"></i>
                    @endauth
                    @guest
                    <i class="fas fa-heart text-xl"></i>
                    @endguest
                </button>
                    </div>
                    <!-- Location -->
                    <p class="text-sm text-gray-400 mt-1">{{$userProduct->region->parent->name}}, {{$userProduct->region->name}}</p>
                    <!-- Date -->
                    <p class="text-sm text-gray-400 mt-1">{{ $userProduct->created_at->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </section>
    </section>

    <section class="border border-gray-900 rounded-lg border-opacity-65 p-4 mt-6">
        <h2 class="px-2 mb-4 text-xl text-white font-medium">Похожие обьявления:</h2>
        <section class="flex gap-4 px-2 overflow-x-auto ">
            @foreach ($sameProducts as $sameProduct)
            <a href="{{ route('product.get', $sameProduct->slug) }}" class="block flex-none w-[280px]">
                <div class="border rounded-lg overflow-hidden shadow-md bg-black border-gray-900 hover:shadow-lg transition-shadow">
                    <div class="relative">
                        <!-- Image -->
                        <img src="{{ asset('storage/' . $sameProduct->images->first()->image_url) }}" 
                             alt="{{ $sameProduct->name }}" 
                             class="object-cover w-full transition-transform duration-300 ease-in-out transform hover:scale-105 h-[200px]">
                    </div>
                    <div class="p-4">
                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-100 truncate w-full" style="max-width: 100%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                            {{$sameProduct->name}}
                        </h3>                    
                        <!-- Price -->
                        <p class="text-xl font-bold text-gray-100 mt-2">{{$sameProduct->price}} {{$sameProduct->currency == 'доллар' ? 'у.e.' : 'сум'}}</p>
                        <!-- Location -->
                        <p class="text-sm text-gray-400 mt-1">{{$sameProduct->region->parent->name}}, {{$sameProduct->region->name}}</p>
                        <!-- Date -->
                        <p class="text-sm text-gray-400 mt-1">{{ $sameProduct->created_at->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </section>
        </section>
    
</div>
@endsection

<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof Swiper !== 'undefined') {
        const swiper = new Swiper('.swiper', {
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });
    }

    // Get product and user coordinates
    const productLatitude = {{ $product->latitude ?? 41.2995 }};
    const productLongitude = {{ $product->longitude ?? 69.2401 }};
    const userLatitude = {{ request()->user()->profile->latitude ?? 41.2995 }};
    const userLongitude = {{ request()->user()->profile->longitude ?? 69.2401 }};
    const directionBtn = document.getElementById('btn-direction');

    if (!productLatitude || !productLongitude || !userLatitude || !userLongitude) {
        console.error('Coordinates are missing for the product or user.');
        return;
    }

    const map = L.map('map').setView([productLatitude, productLongitude], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const productMarker = L.marker([productLatitude, productLongitude])
        .addTo(map)
        .bindPopup('Местоположение товара')
        .bindTooltip('Локация обьявления', { permanent: true, direction: 'top', offset: [-15, -15] });

    let routingControl;

    const getDirection = () => {
        if (routingControl) {
            map.removeControl(routingControl);
        }

        const userMarker = L.marker([userLatitude, userLongitude], {
            icon: L.icon({
                iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/images/marker-icon-2x.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
            }),
        }).addTo(map)
            .bindPopup('Ваше местоположение')
            .bindTooltip('Ваше местоположение', { permanent: true, direction: 'top', offset: [0, -42] });

        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(userLatitude, userLongitude),
                L.latLng(productLatitude, productLongitude),
            ],
            routeWhileDragging: true,
            showAlternatives: false,
            createMarker: function (i, wp) {
                return L.marker(wp.latLng, {
                    icon: L.icon({
                        iconUrl: i === 0
                            ? "https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/images/marker-icon-2x.png"
                            : "https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/images/marker-icon.png",
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                    }),
                });
            },
            lineOptions: {
                styles: [{ color: 'blue', weight: 8, opacity: 1 }],
            },
            addWaypoints: false,
            routeDragInterval: 200,
        }).addTo(map);
    };

    if (directionBtn) {
        directionBtn.addEventListener('click', getDirection);
    }
});


</script>


