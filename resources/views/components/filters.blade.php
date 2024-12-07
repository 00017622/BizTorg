<section class="flex flex-col justify-center gap-6 ">
<section class="grid grid-cols-1 md:grid-cols-5 gap-4 py-4 px-8 ">
<div x-data="{ open: false }" class="relative inline-block text-left w-full">
    <div class="flex items-center">
        <button 
            @click="open = !open" 
            class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span>{{ $selectedSubcategory->name ?? 'Выбрать подкатегорию' }}</span>
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->only('currency'))) }}" 
           class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Все подкатегории</a>

        @foreach ($category->subcategories as $subcategory)
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug, 'subcategory' => $subcategory->slug], request()->only('currency'))) }}" 
               class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
                {{ $subcategory->name }}
            </a>
        @endforeach
    </div>
</div>

{{-- REGION FILTERS ------------------------------------------------------------------------ --}}

<div x-data="{ open: false }" class="relative inline-block text-left w-full">
    <div class="flex items-center">
        <button 
            @click="open = !open" 
            class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span>{{$selectedRegion->name ?? 'Регион'}}</span>
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['region' => 'whole'])) }}" 
           class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Вся страна</a>

           @foreach ($mainRegions as $mainRegion)
           @if ($mainRegion)
           <a href="{{ route('category.show', array_merge(['slug' => $category->slug], Arr::except(request()->all(), 'city'), ['region' => $mainRegion->slug])) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
            {{ $mainRegion->name }}
        </a>
        
           @endif
       @endforeach
       
    </div>
</div>
{{-- ----------------------------- --}}


@if ($regionChildren->isNotEmpty())
<div x-data="{ open: false }" class="relative inline-block text-left w-full">
    <div class="flex items-center">
        <button 
            @click="open = !open" 
            class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span>{{$selectedCity->name ?? 'Город (Район)'}}</span>
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
           @foreach ($regionChildren as $kidRegion)
           @if ($kidRegion)
           <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['city' => $kidRegion->slug])) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
            {{ $kidRegion->name }}
        </a>
        @else
        <p>Bee</p>
           @endif
       @endforeach
       
    </div>
</div>
@endif

@if(request()->input('currency') === 'uzs')
<div x-data="{ open: false }" class="relative inline-block w-full">
    <button 
        @click="open = !open" 
        class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
        <span>{{ request('price_from') ?? 'Цена от' }} сум</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        @foreach([1000, 10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 10000000, 20000000, 30000000, 40000000, 50000000, 60000000, 70000000, 80000000, 90000000, 100000000, 120000000, 140000000, 160000000, 180000000, 200000000] as $amount)
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['price_from' => $amount])) }}" 
               class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
               От {{ number_format($amount, 0, '', ' ') }} сум
            </a>
        @endforeach
    </div>
</div>

@elseif(request()->input('currency') === 'usd')
<div x-data="{ open: false }" class="relative inline-block w-full">
    <button 
        @click="open = !open" 
        class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
        <span>{{ request('price_from') ?? 'Цена от' }} $</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 140000, 160000, 180000, 200000] as $amount)
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['price_from' => $amount])) }}" 
               class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
               От {{ number_format($amount, 0, '', ' ') }} $
            </a>
        @endforeach
    </div>
</div>
@endif

@if(request()->input('currency') === 'uzs')
<div x-data="{ open: false }" class="relative inline-block w-full">
    <button 
        @click="open = !open" 
        class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
        <span>{{ request('price_to') ?? 'Цена до' }} сум</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        @foreach([10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 6000000, 7000000, 8000000, 9000000, 10000000, 12000000, 14000000, 16000000, 18000000, 20000000, 30000000, 40000000, 50000000, 100000000, 200000000] as $amount)
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['price_to' => $amount])) }}" 
               class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
               До {{ number_format($amount, 0, '', ' ') }} сум
            </a>
        @endforeach
    </div>
</div>

@elseif(request()->input('currency') === 'usd')
<div x-data="{ open: false }" class="relative inline-block w-full">
    <button 
        @click="open = !open" 
        class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
        <span>{{ request('price_to') ?? 'Цена до' }} $</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 140000, 160000, 180000, 200000] as $amount)
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['price_to' => $amount])) }}" 
               class="block px-4 py-2 hover:bg-gray-700 rounded-lg">
               До {{ number_format($amount, 0, '', ' ') }} $
            </a>
        @endforeach
    </div>
</div>
@endif

<div x-data="{ open: false }" class="relative inline-block w-full sm:w-48">
    <button 
        @click="open = !open" 
        class="inline-flex justify-between items-center gap-4 w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
        <span>{{ request('currency') == 'usd' ? 'USD' : (request('currency') == 'uzs' ? 'UZS' : 'Выбрать валюту') }}</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div 
        x-show="open" 
        @click.away="open = false"
        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
        <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['currency' => 'uzs'])) }}" 
           class="block w-full text-left px-4 py-2 hover:bg-gray-700 rounded-lg">
            UZS
        </a>
        <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['currency' => 'usd'])) }}" 
           class="block w-full text-left px-4 py-2 hover:bg-gray-700 rounded-lg">
            USD
        </a>
    </div>
</div>




@if ($attributes->isNotEmpty())
    @foreach ($attributes as $attribute)
    <div x-data="{ open: false }" class="relative inline-block w-full ">
        <button 
            @click="open = !open" 
            class="inline-flex justify-between items-center w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span>{{ $attribute->name }}</span>
            <i class="fas fa-chevron-down"></i>
        </button>

        <div 
            x-show="open" 
            @click.away="open = false"
            class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
            @foreach ($attributeValues[$attribute->id] ?? [] as $value)
                <a href="{{route('category.show', array_merge(['slug' => $category->slug], request()->all(), [$attribute->slug => $value->id])) }}" 
                   class="block px-4 py-2 hover:bg-gray-700 rounded-lg">{{ $value->value }}</a>
            @endforeach
        </div>
    </div>
    @endforeach
@endif
</section>

<section class="py-4 px-8 flex flex-wrap items-center gap-4 mb-12 border-b border-[#292828]">
    <!-- Sort Button -->
    <div class="flex-shrink-0">
        <a class="text-white border px-4 hover:bg-slate-900 border-slate-500 flex items-center gap-3 p-2 rounded-full">
            <h3 class="text-lg leading-none">Сортировать по:</h3>
            <span class="text-xl"><i class="fas fa-arrow-right"></i></span>
        </a>
    </div>

    <!-- Вид обьявления Dropdown -->
    <div x-data="{ open: false, selectedType: '{{ request('type') == 'purchase' ? 'Покупка' : (request('type') == 'sale' ? 'Продажа' : 'Вид обьявления') }}' }" class="relative inline-block w-full sm:w-48">
        <button @click="open = !open" class="inline-flex justify-between items-center gap-4 w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span x-text="selectedType"></span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['type' => 'purchase'])) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Покупка</a>
            <a href="{{ route('category.show', array_merge(['slug' => $category->slug], request()->all(), ['type' => 'sale'])) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Продажа</a>
        </div>
    </div>

    <!-- Сортировка Dropdown -->
    <div x-data="{ open: false }" class="relative inline-block w-full sm:w-48">
        <button @click="open = !open" class="inline-flex justify-between items-center gap-4 w-full bg-gray-800 text-white p-2 rounded-lg hover:bg-gray-700">
            <span>
                {{ 
                    request('date_filter') == 'new' ? 'Самые новые' : 
                    (request('date_filter') == 'expensive' ? 'Самые дорогие' : 
                    (request('date_filter') == 'cheap' ? 'Самые дешевые' : 'Сортировка')) 
                }}
            </span>
            
            <i class="fas fa-chevron-down"></i>
        </button>
        <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-gray-800 text-white shadow-lg max-h-60 overflow-y-auto">
            <a href="{{ route('category.show', ['slug' => $category->slug, 'date_filter' => 'new']) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Новые</a>
            <a href="{{ route('category.show', ['slug' => $category->slug, 'date_filter' => 'expensive']) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Дорогие</a>
            <a href="{{ route('category.show', ['slug' => $category->slug, 'date_filter' => 'cheap']) }}" class="block px-4 py-2 hover:bg-gray-700 rounded-lg">Дешевые</a>
        </div>
    </div>

    <!-- Только с фото Checkbox -->
    <div class="flex items-center text-white">
        @php
            $withImages = request('with_images_only') ? null : 'yes';
            $url = request()->fullUrlWithQuery(['with_images_only' => $withImages]);
        @endphp
        <a href="{{ $url }}" class="flex items-center text-white">
            <div class="w-8 h-8 rounded-md bg-gray-800 flex items-center justify-center cursor-pointer border-2 border-gray-600 transition-all duration-200 hover:border-blue-500" aria-checked="{{ request('with_images_only') ? 'true' : 'false' }}">
                @if(request('with_images_only'))
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
            <label for="with_images" class="ml-2">Только с фото</label>
        </a>
    </div>

    <!-- Search Component -->
    <div class="relative inline-block w-full sm:w-80 md:w-96 md:col-span-2">
        <form action="{{ route('category.show', ['slug' => $category->slug]) }}" method="GET" class="flex items-center">
            @foreach(request()->except('search') as $param => $value)
                <input type="hidden" name="{{ $param }}" value="{{ $value }}">
            @endforeach
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск..." class="w-full bg-gray-800 text-white p-2 pl-4 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-700">
            <button type="submit" class="absolute right-2 text-gray-400 hover:text-white">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
</section>
</section>