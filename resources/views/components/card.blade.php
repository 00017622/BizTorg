<section class=" flex flex-col gap-4">
    @if($products->count() > 0)
    @foreach ($products as $product)
    <a href="{{ route('product.get', $product->slug) }}">
    <div class="flex items-center rounded-xl hover:bg-gray-700 transition-colors  shadow-md overflow-hidden p-4 border border-[#333]">
        
        <!-- Image Section -->
        <div class="flex-shrink-0 w-[215px] h-[145px] bg-gray-200 rounded-md overflow-hidden">
            @if ($product->images()->exists())
            <img src="{{ asset('storage/' . $product->images()->first()->image_url ) }}" alt="{{ $product->name }}" class="object-cover w-full h-full transition-transform duration-300 ease-in-out transform hover:scale-105">
        </div>
        @endif
        <!-- Product Details -->
        <div class="ml-4 flex-1 gap-4 flex flex-col">
            <div>
            <h3 class="text-lg font-semibold text-white">{{ $product->name }}</h3>
            <p class="text-sm text-white break-words whitespace-normal line-clamp-3 w-[50ch]">{{ \Illuminate\Support\Str::words($product->description, 20, '...') }}</p>
            </div>
            <div class="flex items-center gap-4">
            <p class="text-xs text-white">{{ $product->created_at->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
            <p class="text-xs text-white bg-gray-800 rounded-lg p-2">#{{$product->type}}продажа</p>
            </div>
        </div>

        <!-- Price and Favorite Icon -->
        <div class="ml-4 flex-shrink-0 text-right">
            @if(request()->input('currency') === 'uzs') 
                @if($product->currency === 'сум') 
                    <p class="text-xl font-bold text-white">{{ number_format((float)$product->price, 2, '.', ' ')}} сум</p>
                @else
                <p class="text-xl font-bold text-white">{{number_format((float)$product->price * (float)$usdRate, 2, '.', ' ')}} сум</p>
                @endif
            
            {{-- if the currency of the in the url is usd --}}
            @elseif (request()->input('currency') === 'usd') 
                @if ($product->currency === 'доллар')
                <p class="text-xl font-bold text-white">{{number_format((float)$product->price, 2, '.', ' ')}} $</p>
                @else
                <p class="text-xl font-bold text-white">{{number_format((float)$product->price / (float)$usdRate, 2, '.', ' ')}} $</p>
                @endif

            @elseif (!request()->has('currency'))
            @if ($product->currency === 'доллар')
                <p class="text-xl font-bold text-white">{{number_format((float)$product->price, 2, '.', ' ')}} $</p>
                @else
                <p class="text-xl font-bold text-white">{{number_format((float)$product->price, 2, '.', ' ')}} сум</p>
                @endif
            @endif
            <p class="text-sm text-gray-500">{{ $product->is_negotiable ? 'Договорная' : '' }}</p>
            
            <button 
                class="mt-2 text-gray-400 hover:text-red-500 text-xl favorite-btn" 
                data-product-id="{{ $product->id }}">
                @auth
                <i class="fas fa-heart text-xl {{ $user->favoriteProducts->contains($product->id) ? 'favorited' : '' }}"></i>
                @endauth
                @guest
                <i class="fas fa-heart text-xl"></i>
                @endguest
            </button>

        </div>
        
    </div>

    @if (Route::currentRouteName() === 'profile.products')
    <a href="{{route('product.get.edit', $product->id)}}">
        Изменить
    </a>
    <a href="">
        Удалить
    </a>
@endif
</a>
    @endforeach

    @else
    <div class="flex justify-center items-center ">
        <div class="flex flex-col items-center gap-2  p-2 rounded-2xl">
            <img src="{{ asset('nofoundproduct.webp') }}" alt="No Products Found" class="h-64 w-64 object-contain">
            <p class="text-lg text-white">Нет подходящих обьявлений</p>
            {{-- <div class="flex-shrink-0">
                <a href="{{route('category.show', ['slug' => $category->slug])}}" class="text-white border px-3 hover:bg-slate-900 border-slate-500 flex items-center gap-3 p-1.5 rounded-full">
                    <h3 class="text-lg leading-none">Сбросить фильтры</h3>
                    <span class="text-lg"><i class="fas fa-arrow-right"></i></span>
                </a>
            </div> --}}
        </div>
    </div>
    
    @endif

    
</section>
