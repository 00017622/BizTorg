<section class="flex flex-col justify-center gap-6">
    <form id="filter-form" action="{{ route('category.show', ['slug' => $category->slug]) }}" method="GET" class="flex flex-col gap-6">
        <section class="grid grid-cols-1 md:grid-cols-5 gap-4 py-4 px-8">
            <!-- Subcategory Dropdown -->
            @php
                $subcategoryText = $selectedSubcategory->name ?? 'Выбрать подкатегорию';
            @endphp
            <div x-data="{ open: false, selectedValue: '{{ $selectedSubcategory->slug ?? '' }}', selectedText: {{ json_encode($subcategoryText) }} }" class="relative inline-block text-left w-full">
                <div class="flex items-center">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                    style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <button type="button" 
                            data-value=""
                            data-text="Все подкатегории"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.subcategory.value = selectedValue; open = false">Все подкатегории</button>
                    @foreach ($category->subcategories as $subcategory)
                        <button type="button" 
                                data-value="{{ $subcategory->slug }}"
                                data-text="{{ json_encode($subcategory->name) }}"
                                class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.subcategory.value = selectedValue; open = false">
                            {{ $subcategory->name }}
                        </button>
                    @endforeach
                    <input type="hidden" name="subcategory" x-ref="subcategory" value="{{ $selectedSubcategory->slug ?? '' }}">
                </div>
            </div>

            <!-- Region Dropdown -->
            @php
                $regionText = $selectedRegion->name ?? 'Регион';
            @endphp
            <div x-data="{ open: false, selectedValue: '{{ $selectedRegion->slug ?? 'whole' }}', selectedText: {{ json_encode($regionText) }} }" class="relative inline-block text-left w-full">
                <div class="flex items-center">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                    style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <button type="button" 
                            data-value="whole"
                            data-text="Вся страна"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.region.value = selectedValue; open = false">Вся страна</button>
                    @foreach ($mainRegions as $mainRegion)
                        @if ($mainRegion)
                            <button type="button" 
                                    data-value="{{ $mainRegion->slug }}"
                                    data-text="{{ json_encode($mainRegion->name) }}"
                                    class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                    x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.region.value = selectedValue; open = false">
                                {{ $mainRegion->name }}
                            </button>
                        @endif
                    @endforeach
                    <input type="hidden" name="region" x-ref="region" value="{{ $selectedRegion->slug ?? 'whole' }}">
                </div>
            </div>

            <!-- City Dropdown -->
            @if ($regionChildren->isNotEmpty())
                @php
                    $cityText = $selectedCity->name ?? 'Город (Район)';
                @endphp
                <div x-data="{ open: false, selectedValue: '{{ $selectedCity->slug ?? '' }}', selectedText: {{ json_encode($cityText) }} }" class="relative inline-block text-left w-full">
                    <div class="flex items-center">
                        <button 
                            @click="open = !open" 
                            type="button"
                            class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                            x-on:click.away="open = false">
                            <span x-text="selectedText"></span>
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
                                <button type="button" 
                                        data-value="{{ $kidRegion->slug }}"
                                        data-text="{{ json_encode($kidRegion->name) }}"
                                        class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                        x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.city.value = selectedValue; open = false">
                                    {{ $kidRegion->name }}
                                </button>
                            @endif
                        @endforeach
                        <input type="hidden" name="city" x-ref="city" value="{{ $selectedCity->slug ?? '' }}">
                    </div>
                </div>
            @endif

            <!-- Price From Dropdown (UZS) -->
            @if(request()->input('currency') === 'uzs')
                @php
                    $priceFromText = request('price_from') ? 'От ' . number_format(request('price_from'), 0, '', ' ') . ' сум' : 'Цена от';
                @endphp
                <div x-data="{ open: false, selectedValue: '{{ request('price_from') ?? '' }}', selectedText: {{ json_encode($priceFromText) }} }" class="relative inline-block w-full">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                        style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        @foreach([1000, 10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 10000000, 20000000, 30000000, 40000000, 50000000] as $amount)
                            <button type="button" 
                                    data-value="{{ $amount }}"
                                    data-text="От {{ number_format($amount, 0, '', ' ') }} сум"
                                    class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                    x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.price_from.value = selectedValue; open = false">
                                От {{ number_format($amount, 0, '', ' ') }} сум
                            </button>
                        @endforeach
                        <input type="hidden" name="price_from" x-ref="price_from" value="{{ request('price_from') ?? '' }}">
                    </div>
                </div>
            @elseif(request()->input('currency') === 'usd')
                @php
                    $priceFromText = request('price_from') ? 'От ' . number_format(request('price_from'), 0, '', ' ') . ' $' : 'Цена от';
                @endphp
                <div x-data="{ open: false, selectedValue: '{{ request('price_from') ?? '' }}', selectedText: {{ json_encode($priceFromText) }} }" class="relative inline-block w-full">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                        style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000] as $amount)
                            <button type="button" 
                                    data-value="{{ $amount }}"
                                    data-text="От {{ number_format($amount, 0, '', ' ') }} $"
                                    class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                    x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.price_from.value = selectedValue; open = false">
                                От {{ number_format($amount, 0, '', ' ') }} $
                            </button>
                        @endforeach
                        <input type="hidden" name="price_from" x-ref="price_from" value="{{ request('price_from') ?? '' }}">
                    </div>
                </div>
            @endif

            <!-- Price To Dropdown (UZS) -->
            @if(request()->input('currency') === 'uzs')
                @php
                    $priceToText = request('price_to') ? 'До ' . number_format(request('price_to'), 0, '', ' ') . ' сум' : 'Цена до';
                @endphp
                <div x-data="{ open: false, selectedValue: '{{ request('price_to') ?? '' }}', selectedText: {{ json_encode($priceToText) }} }" class="relative inline-block w-full">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                        style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        @foreach([10000, 50000, 100000, 500000, 1000000, 3000000, 5000000, 10000000, 20000000, 30000000, 40000000, 50000000] as $amount)
                            <button type="button" 
                                    data-value="{{ $amount }}"
                                    data-text="До {{ number_format($amount, 0, '', ' ') }} сум"
                                    class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                    x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.price_to.value = selectedValue; open = false">
                                До {{ number_format($amount, 0, '', ' ') }} сум
                            </button>
                        @endforeach
                        <input type="hidden" name="price_to" x-ref="price_to" value="{{ request('price_to') ?? '' }}">
                    </div>
                </div>
            @elseif(request()->input('currency') === 'usd')
                @php
                    $priceToText = request('price_to') ? 'До ' . number_format(request('price_to'), 0, '', ' ') . ' $' : 'Цена до';
                @endphp
                <div x-data="{ open: false, selectedValue: '{{ request('price_to') ?? '' }}', selectedText: {{ json_encode($priceToText) }} }" class="relative inline-block w-full">
                    <button 
                        @click="open = !open" 
                        type="button"
                        class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                        x-on:click.away="open = false">
                        <span x-text="selectedText"></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                        style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        @foreach([1, 5, 25, 50, 100, 500, 2000, 5000, 10000, 20000, 30000, 40000, 50000] as $amount)
                            <button type="button" 
                                    data-value="{{ $amount }}"
                                    data-text="До {{ number_format($amount, 0, '', ' ') }} $"
                                    class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                    x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.price_to.value = selectedValue; open = false">
                                До {{ number_format($amount, 0, '', ' ') }} $
                            </button>
                        @endforeach
                        <input type="hidden" name="price_to" x-ref="price_to" value="{{ request('price_to') ?? '' }}">
                    </div>
                </div>
            @endif

            <!-- Currency Dropdown -->
            @php
                $currencyText = request('currency') == 'usd' ? 'USD' : (request('currency') == 'uzs' ? 'UZS' : 'Выбрать валюту');
            @endphp
            <div x-data="{ open: false, selectedValue: '{{ request('currency') ?? '' }}', selectedText: {{ json_encode($currencyText) }} }" class="relative inline-block w-full sm:w-48">
                <button 
                    @click="open = !open" 
                    type="button"
                    class="inline-flex justify-between items-center gap-4 w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                    x-on:click.away="open = false">
                    <span x-text="selectedText"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                    style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <button type="button" 
                            data-value="uzs"
                            data-text="UZS"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.currency.value = selectedValue; open = false">UZS</button>
                    <button type="button" 
                            data-value="usd"
                            data-text="USD"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.currency.value = selectedValue; open = false">USD</button>
                    <input type="hidden" name="currency" x-ref="currency" value="{{ request('currency') ?? '' }}">
                </div>
            </div>

            <!-- Attributes Dropdowns -->
            @if ($attributes->isNotEmpty())
                @foreach ($attributes as $attribute)
                    @php
                        $selectedAttributeValue = collect($attributeValues[$attribute->id] ?? [])->firstWhere('id', request($attribute->slug));
                        $attributeText = $selectedAttributeValue ? $selectedAttributeValue->value : 'Выберите ' . $attribute->name;
                    @endphp
                    <div x-data="{ open: false, selectedValue: '{{ request($attribute->slug) ?? '' }}', selectedText: {{ json_encode($attributeText) }} }" class="relative inline-block w-full">
                        <button 
                            @click="open = !open" 
                            type="button"
                            class="inline-flex justify-between items-center w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200"
                            x-on:click.away="open = false">
                            <span x-text="selectedText"></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto"
                            style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                            @foreach ($attributeValues[$attribute->id] ?? [] as $value)
                                <button type="button" 
                                        data-value="{{ $value->id }}"
                                        data-text="{{ json_encode($value->value) }}"
                                        class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                                        x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.attribute_{{ $attribute->slug }}.value = selectedValue; open = false">
                                    {{ $value->value }}
                                </button>
                            @endforeach
                            <input type="hidden" name="{{ $attribute->slug }}" x-ref="attribute_{{ $attribute->slug }}" value="{{ request($attribute->slug) ?? '' }}">
                        </div>
                    </div>
                @endforeach
            @endif
        </section>

        <!-- Apply Button -->
        <div class="px-8 py-2">
            <button type="submit" id="apply-filters" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                Применить
            </button>
        </div>

        <section class="py-4 px-8 flex flex-wrap items-center gap-4 mb-12 border-b border-gray-200">
            <!-- Sort Button -->
            <div class="flex-shrink-0">
                <button type="button" class="text-black border px-4 hover:bg-gray-200 border-gray-300 flex items-center gap-3 p-2 rounded-full">
                    <h3 class="text-lg leading-none">Сортировать по:</h3>
                    <span class="text-xl"><i class="fas fa-arrow-right"></i></span>
                </button>
            </div>

            <!-- Type Dropdown -->
            @php
                $typeText = request('type') == 'purchase' ? 'Покупка' : (request('type') == 'sale' ? 'Продажа' : 'Вид объявления');
            @endphp
            <div x-data="{ open: false, selectedValue: '{{ request('type') ?? '' }}', selectedText: {{ json_encode($typeText) }} }" class="relative inline-block w-full sm:w-48">
                <button @click="open = !open" type="button" class="inline-flex justify-between items-center gap-4 w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200" x-on:click.away="open = false">
                    <span x-text="selectedText"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto" style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <button type="button" 
                            data-value="purchase"
                            data-text="Покупка"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.type.value = selectedValue; open = false">Покупка</button>
                    <button type="button" 
                            data-value="sale"
                            data-text="Продажа"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.type.value = selectedValue; open = false">Продажа</button>
                    <input type="hidden" name="type" x-ref="type" value="{{ request('type') ?? '' }}">
                </div>
            </div>

            <!-- Sort Dropdown -->
            @php
                $dateFilter = request('date_filter');
                $sortText = match ($dateFilter) {
                    'new' => 'Самые новые',
                    'expensive' => 'Самые дорогие',
                    'cheap' => 'Самые дешевые',
                    default => 'Сортировка',
                };
            @endphp
            <div x-data="{ open: false, selectedValue: '{{ request('date_filter') ?? '' }}', selectedText: {{ json_encode($sortText) }} }" class="relative inline-block w-full sm:w-48">
                <button @click="open = !open" type="button" class="inline-flex justify-between items-center gap-4 w-full bg-white text-black border border-gray-300 p-2 rounded-lg hover:bg-gray-200" x-on:click.away="open = false">
                    <span x-text="selectedText"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-full px-2 py-2 rounded-lg bg-white text-black border border-gray-300 shadow-md max-h-60 overflow-y-auto" style="box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <button type="button" 
                            data-value="new"
                            data-text="Самые новые"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.date_filter.value = selectedValue; open = false">Новые</button>
                    <button type="button" 
                            data-value="expensive"
                            data-text="Самые дорогие"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.date_filter.value = selectedValue; open = false">Дорогие</button>
                    <button type="button" 
                            data-value="cheap"
                            data-text="Самые дешевые"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-200 rounded-lg"
                            x-on:click="selectedValue = $el.dataset.value; selectedText = $el.dataset.text; $refs.date_filter.value = selectedValue; open = false">Дешевые</button>
                    <input type="hidden" name="date_filter" x-ref="date_filter" value="{{ request('date_filter') ?? '' }}">
                </div>
            </div>

            <!-- Only with Images Checkbox -->
            <div class="flex items-center text-black">
                <div x-data="{ checked: {{ request('with_images_only') == 'yes' ? 'true' : 'false' }} }" class="flex items-center">
                    <button type="button" 
                            class="w-8 h-8 rounded-md bg-white flex items-center justify-center cursor-pointer border-2 border-gray-300 transition-all duration-200 hover:border-blue-500"
                            x-bind:class="{ 'border-blue-500': checked }"
                            x-on:click="checked = !checked; $refs.with_images_only.value = checked ? 'yes' : ''">
                        <svg x-show="checked" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <label class="ml-2">Только с фото</label>
                    <input type="hidden" name="with_images_only" x-ref="with_images_only" value="{{ request('with_images_only') ?? '' }}">
                </div>
            </div>

            <!-- Search Component -->
            <div class="relative inline-block w-full sm:w-80 md:w-96 md:col-span-2">
                <form id="search-form" class="flex items-center">
                    @foreach(request()->except(['search', 'currency']) as $param => $value)
                        @if($value !== '' && $value !== null)
                            <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <input type="text" id="search-input" name="search" value="{{ request('search') ?? '' }}" placeholder="Поиск..." class="w-full bg-white text-black p-2 pl-4 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <button type="submit" class="absolute right-2 text-gray-400 hover:text-black">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </section>

        <!-- Product Grid -->
        <div id="product-grid" class="px-8 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @if ($products->isEmpty())
                <p>Нет продуктов, соответствующих фильтрам. Попробуйте изменить фильтры или добавить тестовые данные для подкатегории "{{ $selectedSubcategory->name ?? 'Все подкатегории' }}".</p>
            @else
                @foreach ($products as $product)
                    @include('components.card', ['product' => $product, 'usdRate' => $usdRate])
                @endforeach
            @endif
        </div>

        <!-- Load More -->
        @if ($products->hasMorePages())
            <div class="px-8 py-4">
                <a href="{{ $products->nextPageUrl() }}" class="bg-white text-black px-4 py-2 rounded-lg hover:bg-gray-200 border border-gray-300">
                    Загрузить еще
                </a>
            </div>
        @endif
    </form>
</section>

<script>
    // Ensure script runs after all resources are loaded
    window.addEventListener('load', function () {
        const form = document.getElementById('filter-form');
        const applyButton = document.getElementById('apply-filters');
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');

        // Debug element existence
        console.log('Filter Form:', form ? 'Found' : 'Not Found');
        console.log('Apply Button:', applyButton ? 'Found' : 'Not Found');
        console.log('Search Form:', searchForm ? 'Found' : 'Not Found');
        console.log('Search Input:', searchInput ? 'Found' : 'Not Found');

        if (form && applyButton) {
            applyButton.addEventListener('click', function (e) {
                e.preventDefault();
                const params = new URLSearchParams();
                Array.from(form.elements).forEach(input => {
                    if (input.name && input.value && input.value !== '' && input.value !== null && input.value !== undefined) {
                        params.set(input.name, input.value);
                    }
                });
                form.action = form.action.split('?')[0] + (params.toString() ? '?' + params.toString() : '');
                console.log('Submitting filter form with URL:', form.action);
                form.submit();
            });
        } else {
            console.error('Cannot attach applyButton listener: form or applyButton is null');
        }

        if (searchForm && searchInput) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const params = new URLSearchParams();
                Array.from(form.elements).forEach(input => {
                    if (input.name && input.value && input.value !== '' && input.value !== null && input.value !== undefined) {
                        params.set(input.name, input.value);
                    }
                });
                if (searchInput.value) {
                    params.set('search', searchInput.value);
                }
                form.action = form.action.split('?')[0] + (params.toString() ? '?' + params.toString() : '');
                console.log('Submitting search form with URL:', form.action);
                form.submit();
            });
        } else {
            console.error('Cannot attach searchForm listener: searchForm or searchInput is null');
        }

        // Debug product grid
        const productGrid = document.getElementById('product-grid');
        console.log('Products in grid:', productGrid ? productGrid.children.length : 'Grid not found');
    });

    // Number formatting function
    const usdRate = {{ $usdRate }};
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        let n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) { let k = Math.pow(10, prec); return '' + Math.round(n * k) / k; };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        if ((s[1] || '').length < prec) s[1] = s[1] || '', s[1] += new Array(prec - s[1].length + 1).join('0');
        return s.join(dec);
    }
</script>