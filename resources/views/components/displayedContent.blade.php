<section class="flex flex-col gap-4 py-4 px-8">
    @foreach ($displayedCategories as $each)
    <section class="border-t pb-2 border-gray-900">
        <div class="flex justify-between items-center mt-6 mb-4">
            <h2 class="text-lg font-semibold">{{ $each->name }}</h2>
            <a href="#" class="text-white border px-4 hover:bg-slate-900 border-slate-500 flex items-center gap-3 p-2 rounded-full">
                <h3 class="text-lg leading-none">Перейти</h3>
                <span class="text-xl"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>

        <!-- Subcategories section with horizontal scrolling -->
        <section class="pl-4 mb-4 flex items-center gap-3 overflow-x-auto">
            @foreach ($each->subcategories as $subcategory)
                @if ($subcategory->products->isNotEmpty())
                    @php
                        $product = $subcategory->products->first();
                    @endphp
                    <div class="flex w-[480px] h-[310px] gap-3 flex-col mb-2 border p-2 rounded-lg border-[#252525]">
                        <div class="overflow-hidden rounded-lg">
                            <img src="{{ asset('storage/' . $product->images->first()->image_url) }}" 
                                 alt="{{ $product->name }}" 
                                 class="object-cover w-full transition-transform duration-300 ease-in-out transform hover:scale-105 h-[200px]">
                        </div>
                        <p class="text-white">{{ $product->name }}</p>
                    </div>
                @else
                    <p class="text-gray-500">No products available in this subcategory.</p>
                @endif
            @endforeach
        </section>
    </section>
    @endforeach
</section>

