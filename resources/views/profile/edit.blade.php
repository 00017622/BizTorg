@extends('layouts.profile')

@section('content')
<div class="max-w-6xl mx-auto border border-gray-900 p-8 rounded-2xl shadow-md text-white">
    <h2 class="text-2xl font-bold mb-6">Изменить профиль</h2>

    @if (session('status') === 'profile-updated')
        <div class="bg-green-500 text-white p-3 rounded mb-6">
            Профиль успешно обновлен
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PATCH')

        <!-- Personal Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" class="w-full border border-gray-700 text-white bg-black rounded-lg p-2.5 @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" class="w-full border border-gray-700 text-white bg-black rounded-lg p-2.5 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->profile->phone ?? '') }}" class="w-full border border-gray-700 text-white bg-black rounded-lg p-2.5 @error('phone') border-red-500 @enderror">
                @error('phone')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Address Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="address" class="block text-sm font-medium">Address</label>
                <input type="text" name="address" id="address" value="{{ old('address', $user->profile->address ?? '') }}" class="w-full border border-gray-700 text-white bg-black rounded-lg p-2.5 @error('address') border-red-500 @enderror">
                @error('address')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div class="relative">
                <label for="region_id" class="block text-sm font-medium">Region</label>
                <select name="region_id" id="region_id" class="w-full border border-gray-700 text-white bg-black rounded-lg p-2.5 @error('region_id') border-red-500 @enderror">
                    <option value="">Выбрать регион</option>
                    @foreach ($regions as $region)
                        <option 
                            value="{{ $region->id ?? ''}}" 
                            data-lat="{{ $region->latitude }}" 
                            data-lng="{{ $region->longitude }}"
                            {{ old('region_id', $user->profile->region_id ?? '') == $region->id ? 'selected' : '' }}>
                            {{ $region->name ?? ''}}
                        </option>
                    @endforeach
                </select>
                @error('region_id')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Profile Picture -->
        <section class="flex items-center justify-around gap-2">
            <!-- Avatar Upload -->
            <div class="flex flex-col items-center relative">
                <label for="avatar" class="block text-sm font-medium mb-4">Загрузить аватар</label>
                <div class="relative w-48 h-48 bg-gray-800 border-2 border-dashed border-gray-600 rounded-lg flex items-center justify-center">
                    <!-- Display the current avatar or the default image -->
                    <img 
                        id="imagePreview" 
                        src="{{ $user->profile && $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : 'https://brilliant24.ru/files/cat/template_01.png' }}" 
                        alt="Preview" 
                        class="absolute inset-0 object-cover w-full h-full rounded-lg"
                    >
                    
                    <!-- Add new image label -->
                    <label for="imageInput" class="flex flex-col items-center justify-center text-gray-500 cursor-pointer">
                        <i class="fas fa-plus text-2xl"></i>
                        <span class="text-sm mt-2">Нажмите сюда</span>
                    </label>
                    
                    <!-- File input for uploading a new avatar -->
                    <input 
                        type="file" 
                        id="imageInput" 
                        name="avatar" 
                        accept="image/*" 
                        class="hidden" 
                        onchange="previewImage(event)"
                    >
                    
                    <!-- Button to remove the image -->
                    <button 
                        type="button" 
                        class="absolute right-[-50px] top-[50%] transform -translate-y-1/2 bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg"
                        onclick="removeImage()"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        
            <!-- Map -->
            <div class="w-1/2 h-64 border border-gray-900 rounded-lg overflow-hidden" id="map"></div>
        </section>
        

        <!-- Submit Button -->
        <div class="col-span-1 flex justify-center">
            <button type="submit" class="text-white bg-gray-800 transition-all px-48 py-3 rounded-lg border border-gray-900 hover:bg-black  flex items-center gap-2">
                Обновить профиль
            </button>
        </div>
        <input type="hidden" name="latitude" value="{{ $user->profile->latitude ?? '' }}">
        <input type="hidden" name="longitude" value="{{ $user->profile->longitude ?? '' }}">

    </form>
</div>
@endsection

<script>
    let map, marker;

    // Initialize map
    document.addEventListener('DOMContentLoaded', function () {
        const defaultLat = 41.2995; 
    const defaultLng = 69.2401;

    // Check if the user already has coordinates
    const savedLat = parseFloat('{{ $user->profile->latitude ?? "null" }}');
    const savedLng = parseFloat('{{ $user->profile->longitude ?? "null" }}');

    // Use saved coordinates if they exist, otherwise default to Tashkent
    const initialLat = !isNaN(savedLat) ? savedLat : defaultLat;
    const initialLng = !isNaN(savedLng) ? savedLng : defaultLng;

    // Initialize the map with the saved or default coordinates
    map = L.map('map').setView([initialLat, initialLng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    // Add a draggable marker at the saved or default coordinates
    marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    // Update hidden inputs with the saved coordinates
    updateCoordinates(initialLat, initialLng);

    // Event listener for marker drag
    marker.on('dragend', function () {
        const lat = marker.getLatLng().lat.toFixed(6);
        const lng = marker.getLatLng().lng.toFixed(6);
        updateCoordinates(lat, lng);
    });

    // Update map based on selected region
    const regionDropdown = document.getElementById('region_id');
    regionDropdown.addEventListener('change', function () {
        const selectedOption = regionDropdown.options[regionDropdown.selectedIndex];
        const lat = parseFloat(selectedOption.dataset.lat) || defaultLat;
        const lng = parseFloat(selectedOption.dataset.lng) || defaultLng;

        // Update the map view and marker position
        map.setView([lat, lng], 12);
        marker.setLatLng([lat, lng]);
        updateCoordinates(lat, lng);
    });

    // Allow user to click on the map to adjust marker position
    map.on('click', function (e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);

        marker.setLatLng([lat, lng]); // Move the marker to the clicked location
        updateCoordinates(lat, lng);
    });

    
// Function to update hidden inputs with coordinates
function updateCoordinates(lat, lng) {
    document.querySelector('input[name="latitude"]').value = lat;
    document.querySelector('input[name="longitude"]').value = lng;
}

    function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function () {
                preview.src = reader.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function removeImage() {
        const preview = document.getElementById('imagePreview');
        const input = document.getElementById('imageInput');
        input.value = '';
        preview.src = 'https://brilliant24.ru/files/cat/template_01.png'; // Default image
    }
});

</script>

