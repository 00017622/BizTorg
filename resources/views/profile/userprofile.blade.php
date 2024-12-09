@extends('layouts.profile')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6 text-white">
    <!-- Left Section: User Profile -->
    <div class="lg:col-span-1 border border-gray-900 rounded-2xl shadow-lg p-6 flex flex-col items-center">
        <!-- User Image -->
        @if ($user->profile && $user->profile->avatar)
        <img src="{{ $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : asset('images/default-avatar.png') }}" 
        alt="User Avatar" 
        class="w-48 h-48 rounded-3xl mb-4 shadow-lg border border-gray-700">
        @else
        <p>Нет фото профиля</p>
        @endif

        <!-- Additional Info -->
            <h1 class="text-white font-medium mb-2">Ваше местоположение</h1>
            <div class="w-full h-56 border border-gray-700 rounded-lg overflow-hidden" id="map"></div>
        
    </div>

    <!-- Right Section: Settings Form -->
    <div class="lg:col-span-2 border border-gray-900 rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Профиль</h1>

        <!-- Form Section -->
        <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- First Name -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2" for="first_name">First Name</label>
                <div class=" border border-gray-700  rounded-lg p-2.5 text-gray-300">
                    <span>{{ explode(' ', $user->name)[0] ?? 'Имя' }}</span>
                </div>
            </div>

            <!-- Last Name -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2" for="last_name">Last Name</label>
                <div class=" border border-gray-700 rounded-lg p-2.5 text-gray-300">
                    <span>{{ explode(' ', $user->name)[1] ?? 'Фамилия' }}</span>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2" for="email">Email</label>
                <div class=" border border-gray-700 rounded-lg p-2.5 text-gray-300">
                    <span>{{ $user->email }}</span>
                </div>
            </div>

            <!-- Phone Number -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2" for="phone">Phone Number</label>
                <div class=" border border-gray-700 rounded-lg p-2.5 text-gray-300">
                    <span>{{ $user->profile->phone ?? '+ Add Phone Number' }}</span>
                </div>
            </div>

            <!-- City -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2" for="city">City</label>
                <div class=" border border-gray-700 rounded-lg p-2.5 text-gray-300">
                    <span>{{ $user->profile->region->name ?? 'Регион' }}</span>
                </div>
            </div>

            <!-- Save Changes Button -->
           
        </form>

        <section class="flex items-center gap-4">

            @if (auth()->user()->profile)
            <a href="{{ route('profile.edit') }}">
                <button 
                    type="button" 
                    class="text-white hover:bg-gray-800 transition-all px-6 py-3 rounded-lg border border-gray-900 flex items-center gap-2">
                    <i class='bx bx-edit text-xl text-white'></i>
                    Изменить профиль
                </button>
            </a>
        @else
            <a href="{{ route('profile.navigate') }}">
                <button 
                    type="button" 
                    class="text-white hover:bg-gray-800 transition-all px-6 py-3 rounded-lg border border-gray-900 flex items-center gap-2">
                    <i class='bx bx-edit text-xl text-white'></i>
                    Создать профиль
                </button>
            </a> 
        @endif
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button 
                    type="submit" 
                    class="text-white hover:bg-gray-800 transition-all px-6 py-3 rounded-lg border border-gray-900 flex items-center gap-2">
                    <i class='bx bx-log-out text-xl text-white'></i>
                    Выйти с аккаунта
                </button>
            </form>

        </section>
        
    </div>
</div>

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const defaultLat = 41.2995;
    const defaultLng = 69.2401; 

    const userLat = parseFloat('{{ $user->profile->latitude ?? 41.2995 }}');
    const userLng = parseFloat('{{ $user->profile->longitude ?? 69.2401 }}');

    const map = L.map('map', {
        center: [userLat, userLng],
        zoom: 12, 
        dragging: true, 
        scrollWheelZoom: true, 
        doubleClickZoom: false, 
        boxZoom: false, 
        keyboard: false, 
        zoomControl: false, 
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: ''
    }).addTo(map);

    L.marker([userLat, userLng]).addTo(map);
});

</script>