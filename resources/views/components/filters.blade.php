<section class="flex flex-col justify-center ">
    <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 py-4 px-8 ">
        <div x-data="{ open: false, selected: '{{ $selectedSubcategory->name ?? 'Выбрать подкатегорию' }}' }" class="relative inline-block text-left w-full">
            <div class="flex items-center">
                <button 
                    @click="open = !open" 
                    class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                    <span x-text="selected"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                <a data-filter="subcategory" data-value="" 
                   @click="selected = 'Все подкатегории'; open = false" 
                   class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Все подкатегории</a>

                @foreach ($category->subcategories as $subcategory)
                    <a data-filter="subcategory" data-value="{{ $subcategory->slug }}" 
                       @click="selected = '{{ $subcategory->name }}'; open = false" 
                       class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                        {{ $subcategory->name }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- REGION FILTERS ------------------------------------------------------------------------ --}}

        <div x-data="{ open: false, selected: '{{ $selectedRegion->name ?? 'Регион' }}' }" class="relative inline-block text-left w-full">
            <div class="flex items-center">
                <button 
                    @click="open = !open" 
                    class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                    <span x-text="selected"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                <a data-filter="region" data-value="whole" 
                   @click="selected = 'Вся страна'; open = false" 
                   class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Вся страна</a>

                @foreach ($mainRegions as $mainRegion)
                    @if ($mainRegion)
                        <a data-filter="region" data-value="{{ $mainRegion->slug }}" 
                           @click="selected = '{{ $mainRegion->name }}'; open = false" 
                           class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                            {{ $mainRegion->name }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        {{-- ----------------------------- --}}

        @if ($regionChildren->isNotEmpty())
        <div x-data="{ open: false, selected: '{{ $selectedCity->name ?? 'Город (Район)' }}' }" class="relative inline-block text-left w-full">
            <div class="flex items-center">
                <button 
                    @click="open = !open" 
                    class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                    <span x-text="selected"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                @foreach ($regionChildren as $kidRegion)
                    @if ($kidRegion)
                        <a data-filter="city" data-value="{{ $kidRegion->slug }}" 
                           @click="selected = '{{ $kidRegion->name }}'; open = false" 
                           class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
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
        <div x-data="{ open: false, selected: '{{ request('price_from') ?? 'Цена от' }} сум' }" class="relative inline-block w-full">
            <button 
                @click="open = !open" 
                class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                <span x-text="selected"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                @foreach([1000, 10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 10000000, 20000000, 30000000, 40000000, 50000000, 60000000, 70000000, 80000000, 90000000, 100000000, 120000000, 140000000, 160000000, 180000000, 200000000] as $amount)
                    <a data-filter="price_from" data-value="{{ $amount }}" 
                       @click="selected = 'От {{ number_format($amount, 0, '', ' ') }} сум'; open = false" 
                       class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                       От {{ number_format($amount, 0, '', ' ') }} сум
                    </a>
                @endforeach
            </div>
        </div>

        @elseif(request()->input('currency') === 'usd')
        <div x-data="{ open: false, selected: '{{ request('price_from') ?? 'Цена от' }} $' }" class="relative inline-block w-full">
            <button 
                @click="open = !open" 
                class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                <span x-text="selected"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 140000, 160000, 180000, 200000] as $amount)
                    <a data-filter="price_from" data-value="{{ $amount }}" 
                       @click="selected = 'От {{ number_format($amount, 0, '', ' ') }} $'; open = false" 
                       class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                       От {{ number_format($amount, 0, '', ' ') }} $
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(request()->input('currency') === 'uzs')
        <div x-data="{ open: false, selected: '{{ request('price_to') ?? 'Цена до' }} сум' }" class="relative inline-block w-full">
            <button 
                @click="open = !open" 
                class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                <span x-text="selected"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                @foreach([10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 6000000, 7000000, 8000000, 9000000, 10000000, 12000000, 14000000, 16000000, 18000000, 20000000, 30000000, 40000000, 50000000, 100000000, 200000000] as $amount)
                    <a data-filter="price_to" data-value="{{ $amount }}" 
                       @click="selected = 'До {{ number_format($amount, 0, '', ' ') }} сум'; open = false" 
                       class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                       До {{ number_format($amount, 0, '', ' ') }} сум
                    </a>
                @endforeach
            </div>
        </div>

        @elseif(request()->input('currency') === 'usd')
        <div x-data="{ open: false, selected: '{{ request('price_to') ?? 'Цена до' }} $' }" class="relative inline-block w-full">
            <button 
                @click="open = !open" 
                class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                <span x-text="selected"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 120000, 140000, 160000, 180000, 200000] as $amount)
                    <a data-filter="price_to" data-value="{{ $amount }}" 
                       @click="selected = 'До {{ number_format($amount, 0, '', ' ') }} $'; open = false" 
                       class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                       До {{ number_format($amount, 0, '', ' ') }} $
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <div x-data="{ open: false, selected: '{{ request('currency') == 'usd' ? 'USD' : (request('currency') == 'uzs' ? 'UZS' : 'Выбрать валюту') }}' }" class="relative inline-block w-full sm:w-48">
            <button 
                @click="open = !open" 
                class="inline-flex justify-between items-center gap-4 w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                <span x-text="selected"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                <a data-filter="currency" data-value="uzs" 
                   @click="selected = 'UZS'; open = false" 
                   class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg">
                    UZS
                </a>
                <a data-filter="currency" data-value="usd" 
                   @click="selected = 'USD'; open = false" 
                   class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg">
                    USD
                </a>
            </div>
        </div>

        @if ($attributes->isNotEmpty())
            @foreach ($attributes as $attribute)
            <div x-data="{ open: false, selected: '{{ $attribute->name }}' }" class="relative inline-block w-full ">
                <button 
                    @click="open = !open" 
                    class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200">
                    <span x-text="selected"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div 
                    x-show="open" 
                    @click.away="open = false"
                    class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                    style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    @foreach ($attributeValues[$attribute->id] ?? [] as $value)
                        <a data-filter="{{ $attribute->slug }}" data-value="{{ $value->id }}" 
                           @click="selected = '{{ $value->value }}'; open = false" 
                           class="block px-4 py-2 hover:bg-gray-200 rounded-lg">{{ $value->value }}</a>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif
    </section>
    <div class="px-8 py-2">
        <button id="applyFilters" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            Применить
        </button>
    </div>
</section>

