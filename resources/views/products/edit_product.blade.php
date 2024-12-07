@extends('layouts.profile')

@section('content')
<div class="max-w-7xl mx-auto text-white rounded-2xl shadow-lg">
    <form action="{{ route('products.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col justify-center gap-4">
        @csrf
        @method('PUT')
        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-400 mb-2">Название обьявления ~</label>
                <input type="text" value="{{$product->name ?? ''}}" id="first_name" name="name" class="w-2/3 p-3 rounded-lg border border-gray-900 bg-black text-white" placeholder="Например, сыр гауда за 120 тысяч сум" required>
            </div>

            <div class="relative w-1/3">
                <label for="dropdownButton" class="block text-sm font-medium text-gray-400 mb-2">Категория ~</label>
                <button
                    id="dropdownButton"
                    type="button"
                    class="w-full bg-black text-gray-300 border border-gray-900 rounded-lg p-3 text-left flex justify-between items-center">
                    <span>Выберите категорию</span>
                    <svg class="w-6 h-6 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div id="categoryModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
                <div class="bg-black border border-gray-900 text-white rounded-lg w-3/4 p-6 relative">
                    <!-- Close Button -->
                    <button type="button" id="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-200">
                        <i class="bx bx-x text-2xl"></i>
                    </button>

                    <!-- Modal Content -->
                    <h2 id="main-h2" class="text-2xl font-bold mb-6">Выберите категорию</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        @foreach ($categories as $category)
                        <div data-category-id="{{ $category->id }}" onclick="displaySubcategories({{ $category->id }})" class="bg-gray-700 rounded-lg p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-600">
                            <img src="{{ asset('storage/' . $category->image_url) }}" alt="{{ $category->name }}" class="w-16 h-16 rounded-full">
                            <span>{{ $category->name }}</span>
                        </div>
                        @endforeach
                    </div>

                    <input type="hidden" id="allSubcategories" value="{{ json_encode($subcategories->map(function($subcategory) {
                        return [
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'category_id' => $subcategory->category_id,
                        ];
                    })) }}">

                    <input type="hidden" id="allCategories" value="{{ json_encode($categories->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'image_url' => asset('storage/' . $category->image_url),
                        ];
                    })) }}">
                </div>
            </div>

            <div id="selectedCategory" class="text-gray-300 mt-2 p-1 ml-2 border-b border-gray-900 hidden">
                Выбранная категория: 
                <span id="selectedCategoryName">
                    None
                </span>
                <img 
    id="selectedCategoryImg" 
    src="{{ $product->subcategory->category->image_url 
        ? asset('storage/' . $product->subcategory->category->image_url) 
        : asset('default-category.png') }}" 
    alt="Selected Category" 
    class="w-16 h-16 rounded-full mt-2 hidden"
/>


            </div>
            

            <div id="selectedSubcategory" class="text-gray-300 mt-2 ml-2 hidden">
                Выбранная подкатегория: <span id="selectedSubcategoryName">None</span>
            </div>
            <input type="hidden" id="subcategoryId" name="subcategory_id" value="">
        </section>

        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-400 mb-2">Описание обьявления ~</label>
                <textarea type="text" rows="5" id="description" name="description" class="w-2/3 p-3 rounded-lg border border-gray-900 bg-black text-white" placeholder="Например, сыр гауда за 120 тысяч сум" required>{{$product->description}}</textarea>
            </div>
        </section>

        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <h1 class="font-medium text-white">
                Фото принимаются в форматах: Jpeg, Jpg, Png, Svg
            </h1>
            <div id="imageUploadContainer" class="flex items-center gap-4">
                @for ($i = 1; $i <= 4; $i++)
                    <div
                        class="relative w-44 h-44 bg-gray-800 border-2 border-dashed border-gray-600 rounded-lg flex items-center justify-center cursor-pointer"
                        id="dropZone{{ $i }}"
                        ondragover="event.preventDefault()"
                        ondrop="handleDrop(event, 'imageInput{{ $i }}', 'previewImage{{ $i }}')"
                    >
                        <i class="bx bx-image-add text-4xl font-medium text-white"></i>
                        <img
                            id="previewImage{{ $i }}"
                            src="" {{-- Leave src blank; it will be set via JS --}}
                            alt="Image preview"
                            class="absolute inset-0 object-cover w-full h-full rounded-lg hidden"
                        >
                        <input
                            type="file"
                            id="imageInput{{ $i }}"
                            name="image{{ $i }}"
                            accept="image/*"
                            class="hidden"
                            onchange="previewImage(event, 'previewImage{{ $i }}', 'trashIcon{{ $i }}')"
                        />
                        @foreach ($product->images as $index => $image)
                        <button
                            id="trashIcon{{ $index + 1 }}"
                            type="button"
                            class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-2"
                            onclick="removeImage({{ $index + 1 }}, {{ $image->id }})"
                        >
                            <i class="bx bx-trash text-lg"></i>
                        </button>
                    @endforeach
                    

                    </div>
                @endfor
            </div>
                    
        </section>
        

        <section class="border border-gray-900 rounded-2xl mb-4 flex flex-col gap-5 p-4">
            <h1 class="font-medium text-white">Выберите регион</h1>
            
            <!-- Parent Region Dropdown -->
            <div class="relative w-1/3">
                <label for="parentRegion" class="block text-sm font-medium text-gray-400 mb-2">Выберите родительский регион</label>
                <div class="relative">
                    <button
                        id="parentRegionButton"
                        type="button"
                        class="w-full bg-black text-gray-300 border border-gray-900 rounded-lg p-3 flex justify-between items-center hover:bg-gray-800 focus:outline-none">
                        <span id="parentRegionSelected">Выберите регион</span>
                        <svg class="w-5 h-5 text-gray-300 transform transition-transform" id="parentRegionArrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        id="parentRegionDropdown"
                        class="absolute z-20 hidden bg-black border border-gray-900 rounded-lg w-[calc(100%+4rem)] left-[105%] top-[-60px]  mt-2 shadow-lg max-h-40 overflow-y-auto">
                        <!-- Parent regions will be dynamically populated -->
                    </div>
                </div>
                <input type="hidden" id="parentRegionInput" name="parent_region_id">
            </div>
        
            <!-- Child Region Dropdown -->
            <div class="relative w-1/3 mt-4">
                <label for="childRegion" class="block text-sm font-medium text-gray-400 mb-2">Выберите район</label>
                <div class="relative">
                    <button
                        id="childRegionButton"
                        type="button"
                        class="w-full bg-black text-gray-300 border border-gray-900 rounded-lg p-3 flex justify-between items-center hover:bg-gray-800 focus:outline-none" disabled>
                        <span id="childRegionSelected">Выберите район</span>
                        <svg class="w-5 h-5 text-gray-300 transform transition-transform" id="childRegionArrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        id="childRegionDropdown"
                        class="absolute z-50 hidden bg-black border border-gray-900 rounded-lg w-[calc(100%+4rem)] left-[105%] top-[-50px] shadow-lg max-h-40 overflow-y-auto">
                        <!-- Child regions will be dynamically populated -->
                    </div>
                </div>
                <input type="hidden" id="childRegionInput" name="child_region_id">
            </div>
        </section>
        
        <section id="selectedRegionsSection" class="text-gray-300 mt-2 p-2 hidden">
            <h2 class="text-sm font-medium">Выбранный адрес:</h2>
            <p id="selectedRegionsText">Регион не выбран</p>
        </section>
        
        
        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <h1 class="font-medium text-white">Укажите местоположение на карте</h1>
            <div id="map" class="w-full h-[400px] rounded-lg border border-gray-600"></div>
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
        </section>

            
        <section class="border border-gray-900 rounded-2xl flex flex-col gap-3 p-4">
            <h1 class="font-medium text-white">Дополнительная информация и параметры вашего обьявления</h1>
            
            <!-- Container for dropdowns with gap -->
            <div id="attributesSection" class="grid gap-5 md:w-1/3 lg:w-[380px] w-full">
            </div>
        </section>
        
        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <h1 class="font-medium text-white">Укажите цену и валюту</h1>
            
            <!-- Price Input -->
        
            <div class="flex items-center gap-4">
                <label for="price" class="block text-sm font-medium text-gray-400 mb-2">Цена</label>
                <input 
                    type="number"
                    value="{{$product->price}}"
                    id="price" 
                    name="price" 
                    min="0" 
                    step="1" 
                    placeholder="Введите цену" 
                    class="w-1/3 p-3 rounded-lg border border-gray-900 bg-black text-white" 
                    required>
            </div>
            
            <!-- Currency Dropdown -->
            <div class="relative w-1/3 flex items-center gap-5">
                <label for="currencyDropdown" class="block text-sm font-medium text-gray-400 mb-2">Валюта</label>
                <button 
                    id="currencyDropdownButton" 
                    type="button" 
                    class="w-full bg-black text-gray-300 border border-gray-900 rounded-lg p-3 text-left flex justify-between items-center">
                    <span id="selectedCurrency">Выберите валюту</span>
                    <svg class="w-6 h-6 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <!-- Dropdown Options -->
                <div 
                    id="currencyDropdown" 
                    class="absolute z-10 mt-2 w-full bg-black text-white border border-gray-900 rounded-lg hidden">
                    <div class="px-4 py-2 hover:bg-gray-700 cursor-pointer" data-currency="сум">сум</div>
                    <div class="px-4 py-2 hover:bg-gray-700 cursor-pointer" data-currency="доллар">USD</div>
                </div>
                
                <!-- Hidden Input to Store Selected Currency -->
                <input type="hidden" id="currency" name="currency" required>
            </div>
            
        </section>

        <section class="border border-gray-900 rounded-2xl flex flex-col gap-5 p-4">
            <h1 class="font-medium text-white">Тип объявления</h1>
            <div class="relative w-1/3">
                <div class="relative">
                    <button
                        id="adTypeDropdownButton"
                        type="button"
                        class="w-full bg-black text-gray-300 border border-gray-900 rounded-lg p-3 flex justify-between items-center hover:bg-gray-800 focus:outline-none">
                        <span id="adTypeSelectedText">Выберите тип</span>
                        <svg class="w-5 h-5 text-gray-300 transform transition-transform" id="adTypeDropdownArrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        id="adTypeDropdownMenu"
                        class="absolute z-10 hidden bg-black border border-gray-900 rounded-lg w-full mt-2 shadow-lg">
                        <div
                            class="px-4 py-2 hover:bg-gray-800 cursor-pointer text-gray-300"
                            data-value="sale">
                            Продажа
                        </div>
                        <div
                            class="px-4 py-2 hover:bg-gray-800 cursor-pointer text-gray-300"
                            data-value="purchase">
                            Покупка
                        </div>
                    </div>
                </div>
                <input type="hidden" id="adTypeInput" name="type">
            </div>
        </section>
        
        <section class="border border-gray-900 rounded-2xl flex flex-col items-start gap-5 p-4">
            <button class="hover:bg-gray-800 transition bg-transparent border border-gray-900 px-6 py-4 rounded-lg" type="submit">
                Опубликовать обьявление
            </button>
        </section>
        <input type="hidden" name="product_id" value="{{$product->id}}">
    </form>
</div>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    // DOM elements
    const dropdownButton = document.getElementById('dropdownButton');
    const categoryModal = document.getElementById('categoryModal');
    const closeModal = document.getElementById('closeModal');
    const selectedCategoryDiv = document.getElementById('selectedCategory');
    const selectedSubcategoryDiv = document.getElementById('selectedSubcategory');
    const selectedCategoryName = document.getElementById('selectedCategoryName');
    const selectedCategoryImg = document.getElementById('selectedCategoryImg');
    const selectedSubcategoryName = document.getElementById('selectedSubcategoryName');
    const mainHeading = document.getElementById('main-h2');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');


    const currencyDropdownButton = document.getElementById('currencyDropdownButton');
    const currencyDropdown = document.getElementById('currencyDropdown');
    const selectedCurrency = document.getElementById('selectedCurrency');
    const currencyInput = document.getElementById('currency');

    const dropdownButtonType = document.getElementById('adTypeDropdownButton');
    const dropdownMenu = document.getElementById('adTypeDropdownMenu');
    const selectedText = document.getElementById('adTypeSelectedText');
    const hiddenInput = document.getElementById('adTypeInput');
    const dropdownArrow = document.getElementById('adTypeDropdownArrow');

    const parentRegionButton = document.getElementById('parentRegionButton');
    const parentRegionDropdown = document.getElementById('parentRegionDropdown');
    const parentRegionSelected = document.getElementById('parentRegionSelected');
    const parentRegionInput = document.getElementById('parentRegionInput');
    const parentRegionArrow = document.getElementById('parentRegionArrow');

    const childRegionButton = document.getElementById('childRegionButton');
    const childRegionDropdown = document.getElementById('childRegionDropdown');
    const childRegionSelected = document.getElementById('childRegionSelected');
    const childRegionInput = document.getElementById('childRegionInput');
    const childRegionArrow = document.getElementById('childRegionArrow');
    const imageUploadContainer = document.getElementById('imageUploadContainer');
    const selectedRegionsSection = document.getElementById('selectedRegionsSection');
    const selectedRegionsText = document.getElementById('selectedRegionsText');


// Onload function to prepopulate the region and map

const productRegionData = {
    parentRegion: {!! json_encode($product->region->parent ?? null) !!}, 
    childRegion: {!! json_encode($product->region ?? null) !!}, 
};



    if (productRegionData.parentRegion || productRegionData.childRegion) {
        let text = '';

        if (productRegionData.parentRegion) {
            text += `Регион: ${productRegionData.parentRegion.name}`;
            parentRegionInput.value = productRegionData.parentRegion.id
        }

        if (productRegionData.childRegion) {
            text += `,  ${productRegionData.childRegion.name}`;
            childRegionInput.value = productRegionData.childRegion.id;
        }

        console.log("Selected Region Text:", text);
        selectedRegionsText.textContent = text;
        selectedRegionsSection.classList.remove('hidden');
    } else {
        console.log("No region selected.");
        selectedRegionsText.textContent = 'Регион не выбран';
    }

    fetch('/regions/parents')
        .then(response => response.json())
        .then(data => {
            parentRegionDropdown.innerHTML = ''; // Clear existing options
            data.forEach(region => {
                const option = document.createElement('div');
                option.classList.add('px-4', 'py-2', 'hover:bg-gray-800', 'cursor-pointer', 'text-gray-300');
                option.textContent = region.name;
                option.dataset.value = region.id;
                option.dataset.lat = region.latitude
                option.dataset.lng = region.longitude

                option.addEventListener('click', () => {
                    parentRegionSelected.textContent = region.name;
                    parentRegionInput.value = region.id;
                    parentRegionDropdown.classList.add('hidden');
                    parentRegionArrow.classList.remove('rotate-180');
                    parentRegionInput.dataset.lat = region.latitude
                    parentRegionInput.dataset.lng = region.longitude

                    const lat = parseFloat(option.dataset.lat);
                    const lng  = parseFloat(option.dataset.lng);

                    map.setView([lat, lng], 12);
                    marker.setLatLng([lat, lng])

                    latitudeInput.value = lat;
                    longitudeInput.value = lng;



                    // Enable child region dropdown and fetch child regions
                    childRegionButton.disabled = false;
                    fetchChildRegions(region.id);
                });

                parentRegionDropdown.appendChild(option);
            });
        });

    // Fetch and populate child regions
    function fetchChildRegions(parentId) {
        fetch(`/regions/children/${parentId}`)
            .then(response => response.json())
            .then(data => {
                childRegionDropdown.innerHTML = ''; // Clear existing options
                data.forEach(region => {
                    const option = document.createElement('div');
                    option.classList.add('px-4', 'py-2', 'hover:bg-gray-800', 'cursor-pointer', 'text-gray-300');
                    option.textContent = region.name;
                    option.dataset.value = region.id;
                    option.dataset.lat = region.latitude
                    option.dataset.lng = region.longitude

                    option.addEventListener('click', () => {
                        childRegionSelected.textContent = region.name;
                        childRegionInput.value = region.id;
                        childRegionDropdown.classList.add('hidden');
                        childRegionArrow.classList.remove('rotate-180');

                        const lat = parseFloat(option.dataset.lat) || parseFloat(parentRegionInput.dataset.lat)
                        const lng = parseFloat(option.dataset.lng) || parseFloat(parentRegionInput.dataset.lng)

                        map.setView([lat, lng], 12);
                        marker.setLatLng([lat, lng]);

                        latitudeInput.value = lat;
                        longitudeInput.value = lng;
                    });

                    childRegionDropdown.appendChild(option);
                });
            });
    }

    // Toggle parent region dropdown
    parentRegionButton.addEventListener('click', () => {
        parentRegionDropdown.classList.toggle('hidden');
        parentRegionArrow.classList.toggle('rotate-180');
    });

    // Toggle child region dropdown
    childRegionButton.addEventListener('click', () => {
        childRegionDropdown.classList.toggle('hidden');
        childRegionArrow.classList.toggle('rotate-180');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!parentRegionButton.contains(e.target) && !parentRegionDropdown.contains(e.target)) {
            parentRegionDropdown.classList.add('hidden');
            parentRegionArrow.classList.remove('rotate-180');
        }
        if (!childRegionButton.contains(e.target) && !childRegionDropdown.contains(e.target)) {
            childRegionDropdown.classList.add('hidden');
            childRegionArrow.classList.remove('rotate-180');
        }
    });

    // Toggle dropdown visibility
    dropdownButtonType.addEventListener('click', () => {
        dropdownMenu.classList.toggle('hidden');
        dropdownArrow.classList.toggle('rotate-180'); // Rotate the arrow
    });

    
    const preselectedProductType = {!! json_encode($product->type ?? null) !!};

    if (preselectedProductType) {
    const selectedOption = dropdownMenu.querySelector(`[data-value="${preselectedProductType}"]`);
    if (selectedOption) {
        if (preselectedProductType === 'sale') {
            selectedText.textContent = 'Продажа';
        } else {
            selectedText.textContent = 'Покупка';
        }
        hiddenInput.value = preselectedProductType;
    }
}


    dropdownMenu.querySelectorAll('[data-value]').forEach(option => {
        option.addEventListener('click', (e) => {
            const value = e.target.getAttribute('data-value');
            const text = e.target.textContent;

            selectedText.textContent = text; // Update button text
            hiddenInput.value = value; // Set hidden input value

            dropdownMenu.classList.add('hidden'); // Hide dropdown
            dropdownArrow.classList.remove('rotate-180'); // Reset arrow rotation
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdownButtonType.contains(e.target) && !dropdownButtonType.contains(e.target)) {
            dropdownMenu.classList.add('hidden');
            dropdownArrow.classList.remove('rotate-180');
        }
    });

    
    const preselectedCurrency = {!! json_encode($product->currency) !!};

if (preselectedCurrency) {
    if (preselectedCurrency === 'доллар') {
        selectedCurrency.textContent = 'USD';
    } else if (preselectedCurrency === 'сум') {
        selectedCurrency.textContent = 'сум';
    } else {
        selectedCurrency.textContent = preselectedCurrency; 
    }

    currencyInput.value = preselectedCurrency; 
} else {
    selectedCurrency.textContent = 'Выберите валюту';
    currencyInput.value = ''; 
}

    
    currencyDropdownButton.addEventListener('click', function () {
        currencyDropdown.classList.toggle('hidden');
    });

    // Handle Dropdown Option Click
    currencyDropdown.addEventListener('click', function (e) {
        if (e.target.dataset.currency) {
            const selectedValue = e.target.dataset.currency;
            selectedCurrency.textContent = selectedValue;
            currencyInput.value = selectedValue; // Set hidden input value
            currencyDropdown.classList.add('hidden'); // Close dropdown
        }
    });

    // Close Dropdown When Clicking Outside
    document.addEventListener('click', function (e) {
        if (!currencyDropdownButton.contains(e.target) && !currencyDropdown.contains(e.target)) {
            currencyDropdown.classList.add('hidden');
        }
    });

    // Initialize the map
    const locationData = {
    latitude: {!! json_encode($product->latitude ?? null) !!},
    longitude: {!! json_encode($product->longitude ?? null) !!}
};

// Fallback coordinates if no location is provided
const initialLatitude = parseFloat(locationData.latitude) || 41.2995;
const initialLongitude = parseFloat(locationData.longitude) || 69.2401;

// Initialize the map at the initial location
const map = L.map('map').setView([initialLatitude, initialLongitude], 12);

// Add the OpenStreetMap tile layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Add a draggable marker to the map
const marker = L.marker([initialLatitude, initialLongitude], { draggable: true }).addTo(map);

// Set initial values in latitude and longitude inputs
latitudeInput.value = initialLatitude.toFixed(6);
longitudeInput.value = initialLongitude.toFixed(6);

// Allow the marker to be moved by dragging
marker.on('dragend', function () {
    const lat = marker.getLatLng().lat.toFixed(6);
    const lng = marker.getLatLng().lng.toFixed(6);
    latitudeInput.value = lat;
    longitudeInput.value = lng;
});

// Allow the user to click on the map to move the marker
map.on('click', function (e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);

    // Update marker position
    marker.setLatLng(e.latlng);

    // Update input values
    latitudeInput.value = lat;
    longitudeInput.value = lng;
});

// Validate latitude and longitude on form submission
const form = document.querySelector('form');
form.addEventListener('submit', function (e) {
    const latitude = latitudeInput.value;
    const longitude = longitudeInput.value;

    if (!latitude || !longitude) {
        e.preventDefault();
        alert('Пожалуйста, укажите местоположение на карте. Это обязательное поле!');
    }
});


    const dropdownAttributes = document.getElementById('dropdownAttributes');

    dropdownButton.addEventListener('click', function () {
        renderMainCategories();
        categoryModal.classList.remove('hidden');
    });
    closeModal.addEventListener('click', function () {
        categoryModal.classList.add('hidden');
    });
    categoryModal.addEventListener('click', function (e) {
        if (e.target === categoryModal) {
            categoryModal.classList.add('hidden');
        }
    });

    window.onload = function() {
    const selectedCategory = {!! json_encode($product->subcategory->category->name ?? '') !!};
    const selectedCategoryImage = {!! json_encode($product->subcategory->category->image_url ?? '') !!};
    const selectedSubcategory = {!! json_encode($product->subcategory->name ?? '') !!};
    const subcategoryId = {!! json_encode($product->subcategory->id ?? '') !!}

    if (selectedCategory) {
        selectedCategoryDiv.classList.remove('hidden');
        selectedCategoryName.textContent = selectedCategory;

        if (selectedCategoryImage) {
            selectedCategoryImg.src = 'http://localhost:8000/storage/' + selectedCategoryImage;
            selectedCategoryImg.classList.remove('hidden');
        }
    }
    if (selectedSubcategory) {
        selectedSubcategoryDiv.classList.remove('hidden');
        selectedSubcategoryName.textContent = selectedSubcategory;
        categoryModal.classList.add('hidden');
        fetchAndDisplayAttributes(subcategoryId);
        document.getElementById('subcategoryId').value = subcategoryId;
    }
    categoryModal.classList.add('hidden');
};


    window.renderMainCategories = function () {
        const allCategories = JSON.parse(document.getElementById('allCategories').value);
        const modalContent = document.querySelector('#categoryModal .grid');

        modalContent.innerHTML = ''; // Clear existing content
        mainHeading.textContent = 'Выберите категорию';

        allCategories.forEach(category => {
            const categoryCard = document.createElement('div');
            categoryCard.className = 'bg-gray-700 rounded-lg p-4 flex items-center gap-4 cursor-pointer hover:bg-gray-600';
            categoryCard.innerHTML = `
                <img src="${category.image_url}" alt="${category.name}" class="w-16 h-16 rounded-full">
                <span>${category.name}</span>
            `;
            categoryCard.addEventListener('click', function () {
                selectedCategoryDiv.classList.remove('hidden');
                selectedCategoryName.textContent = category.name;
                selectedCategoryImg.src = category.image_url;
                selectedCategoryImg.classList.remove('hidden');
                window.displaySubcategories(category.id);
            });
            modalContent.appendChild(categoryCard);
        });
    };

    
    // Display subcategories for the selected category
    window.displaySubcategories = function (categoryId) {
        const allSubcategories = JSON.parse(document.getElementById('allSubcategories').value);
        const filteredSubcategories = allSubcategories.filter(
            subcategory => subcategory.category_id == categoryId
        );
        const modalContent = document.querySelector('#categoryModal .grid');

        modalContent.innerHTML = ''; // Clear content
        mainHeading.textContent = 'Выберите подкатегорию';

        // Add back button
        const backButton = document.createElement('div');
        backButton.className = 'bg-gray-700 rounded-lg px-4 py-3 flex items-center justify-center cursor-pointer hover:bg-gray-600';
        backButton.innerHTML = '<span>Назад</span>';
        backButton.addEventListener('click', renderMainCategories);
        modalContent.appendChild(backButton);

        if (filteredSubcategories.length > 0) {
            filteredSubcategories.forEach(subcategory => {
                const subcategoryCard = document.createElement('div');
                subcategoryCard.className = 'bg-gray-700 rounded-lg px-4 py-3 flex items-center gap-4 cursor-pointer hover:bg-gray-600';
                subcategoryCard.innerHTML = `<span>${subcategory.name}</span>`;
                subcategoryCard.addEventListener('click', function () {
                    selectedSubcategoryDiv.classList.remove('hidden');
                    selectedSubcategoryName.textContent = subcategory.name;
                    categoryModal.classList.add('hidden');
                    fetchAndDisplayAttributes(subcategory.id);
                    document.getElementById('subcategoryId').value = subcategory.id;
                });
                modalContent.appendChild(subcategoryCard);
            });
        } else {
            modalContent.innerHTML = '<p class="text-gray-400">Нет доступных подкатегорий.</p>';
        }
    };

    

    function fetchAndDisplayAttributes(subcategoryId) {
    const productAttributes = {!! json_encode($productAttributes) !!};
    const attributesSection = document.getElementById('attributesSection');
    attributesSection.innerHTML = '<p class="text-gray-400">Загрузка атрибутов...</p>';

    // Fetch attributes for the given subcategory
    fetch(`/fetch-attributes?subcategory_id=${subcategoryId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Не удалось загрузить атрибуты');
            }
            return response.json();
        })
        .then(attributes => {
            attributesSection.innerHTML = ''; // Clear content

            if (attributes.length === 0) {
                attributesSection.innerHTML = '<p class="text-gray-400">Нет доступных параметров.</p>';
                return;
            }

            // Loop through the attributes array
            attributes.forEach(attribute => {
                const attributeContainer = document.createElement('div');
                attributeContainer.classList.add('mb-4');

                const dropdown = document.createElement('div');
                dropdown.classList.add('relative', 'w-full');

                const button = document.createElement('button');
                button.type = 'button';
                button.classList.add(
                    'w-full',
                    'bg-gray-800',
                    'text-white',
                    'p-3',
                    'rounded-lg',
                    'flex',
                    'justify-between',
                    'items-center',
                    'hover:bg-gray-700'
                );

                // Check if this attribute has a preselected value in productAttributes
                const preselectedValue = productAttributes[attribute.id]?.name || 'Выберите';
                const preselectedValueId = productAttributes[attribute.id]?.id || null;

                button.innerHTML = `
                    <span>${attribute.name}: ${preselectedValue}</span>
                    <i class="fas fa-chevron-down"></i>
                `;

                const dropdownMenu = document.createElement('div');
                dropdownMenu.classList.add(
                    'absolute',
                    'z-10',
                    'mt-2',
                    'w-full',
                    'rounded-lg',
                    'bg-gray-800',
                    'shadow-lg',
                    'max-h-60',
                    'overflow-y-auto',
                    'hidden'
                );

                // Create a hidden input for this attribute and pre-fill it if there's a preselected value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `attributes[${attribute.id}]`; // Use attribute ID as the key
                hiddenInput.value = preselectedValueId || ''; // Store preselected value ID or leave empty
                attributesSection.appendChild(hiddenInput);

                // Ensure `attribute.values` is an array
                if (Array.isArray(attribute.values)) {
                    attribute.values.forEach(value => {
                        const option = document.createElement('div');
                        option.classList.add(
                            'px-4',
                            'py-2',
                            'hover:bg-gray-700',
                            'text-white',
                            'cursor-pointer'
                        );
                        option.textContent = value.name;

                        // Highlight the preselected option
                        if (value.id === preselectedValueId) {
                            option.classList.add('bg-gray-600'); // Highlight class
                        }

                        option.addEventListener('click', () => {
                            button.querySelector('span').textContent = `${attribute.name}: ${value.name}`;
                            dropdownMenu.classList.add('hidden');

                            // Update the hidden input with the selected value
                            hiddenInput.value = value.id;
                        });

                        dropdownMenu.appendChild(option);
                    });
                }

                button.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });

                dropdown.appendChild(button);
                dropdown.appendChild(dropdownMenu);
                attributeContainer.appendChild(dropdown);
                attributesSection.appendChild(attributeContainer);
            });
        })
        .catch(error => {
            attributesSection.innerHTML = `<p class="text-red-500">${error.message}</p>`;
        });
}



const maxImages = 4; // Number of image upload slots
const productImages = {!! json_encode($productImages ?? []) !!}; // Images from the backend

// Populate existing images
productImages.forEach((image, index) => {
    const previewImage = document.getElementById(`previewImage${index + 1}`);
    const trashIcon = document.getElementById(`trashIcon${index + 1}`);

    if (previewImage && image.image_url) {
        previewImage.src = `http://localhost:8000/storage/${image.image_url}`;
        previewImage.classList.remove('hidden'); // Show the preview image
        trashIcon.classList.remove('hidden'); // Show the trash icon
    }
});

// Find the number of existing upload squares
const existingSquares = document.querySelectorAll('[id^="dropZone"]').length;

// Dynamically create remaining upload squares
const remainingSlots = maxImages - existingSquares; // Ensure no duplicate slots are added
for (let i = 1; i <= remainingSlots; i++) {
    const slotIndex = existingSquares + i; // Continue numbering from existing squares
    const uploadSquare = document.createElement('div');
    uploadSquare.className = 'relative w-44 h-44 bg-gray-800 border-2 border-dashed border-gray-600 rounded-lg flex items-center justify-center cursor-pointer';
    uploadSquare.id = `dropZone${slotIndex}`;
    uploadSquare.ondragover = (event) => event.preventDefault();
    uploadSquare.ondrop = (event) => handleDrop(event, `imageInput${slotIndex}`, `previewImage${slotIndex}`);

    uploadSquare.innerHTML = `
        <i class='bx bx-image-add text-4xl font-medium text-white'></i>
        <img id="previewImage${slotIndex}" src="" alt="" class="absolute inset-0 object-cover w-full h-full rounded-lg hidden">
        <input
            type="file"
            id="imageInput${slotIndex}"
            name="image${slotIndex}"
            accept="image/*"
            class="hidden"
            onchange="previewImage(event, 'previewImage${slotIndex}', 'trashIcon${slotIndex}')"
        />
        <button
            id="trashIcon${slotIndex}"
            class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-2 hidden"
            onclick="removeImage(${slotIndex})"
        >
            <i class='bx bx-trash text-lg'></i>
        </button>
    `;

    // Append the square to the container
    imageUploadContainer.appendChild(uploadSquare);
}
});

// Image preview function
function previewImage(event, previewId, trashIconId) {
    const input = event.target;
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    const trashIcon = document.getElementById(trashIconId);

    if (file) {
        if (!file.type.startsWith('image/')) {
            alert('Please upload a valid image file.');
            input.value = '';
            return;
        }

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            alert('File size must not exceed 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            trashIcon.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

// Handle image drop
function handleDrop(event, inputId, previewId) {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (file) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function removeImage(index, imageId) {
    const fileInput = document.getElementById(`imageInput${index}`);
    const previewImage = document.getElementById(`previewImage${index}`);
    const trashIcon = document.getElementById(`trashIcon${index}`);
    console.log('Image ID:', imageId); // Debugging step

    if (imageId) {
        fetch(`/product/image/${imageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Image deleted successfully.');

                // Clear the image preview and hide the trash icon
                fileInput.value = '';
                previewImage.src = '';
                previewImage.classList.add('hidden');
                trashIcon.classList.add('hidden');
            } else {
                alert(data.message || 'Failed to delete the image.');
            }
        })
        .catch(error => {
            console.error('Error deleting image:', error);
            alert('An error occurred while deleting the image.');
        });
    } else {
        // Clear the image preview and hide the trash icon for unsaved images
        fileInput.value = '';
        previewImage.src = '';
        previewImage.classList.add('hidden');
        trashIcon.classList.add('hidden');
    }
}



// Expose functions globally
window.previewImage = previewImage;
window.handleDrop = handleDrop;
window.removeImage = removeImage;

</script>
