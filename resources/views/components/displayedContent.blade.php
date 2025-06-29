<section class="flex flex-col gap-4 py-4 px-2 lg:px-10">
    <section class="pl-4 mb-4 grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="product-grid">
        @forelse ($products as $product)
            <a class="hover:bg-gray-200 rounded-xl" href="{{ route('product.get', $product->slug) }}">
                <div class="flex flex-col mb-2 p-2 rounded-lg h-[450px]">
                    <!-- Image with Heart Icon and Shop Capsule -->
                    <div class="overflow-hidden rounded-2xl h-[310px] relative">
                        <img src="{{ $product->images->isNotEmpty() ? asset('storage/' . $product->images->first()->image_url) : asset('default.png') }}" 
                             alt="{{ $product->name }}" 
                             class="object-cover w-full h-full transition-transform duration-300 ease-in-out transform hover:scale-105">
                        <!-- Shop Capsule -->
                        @if ($product->user->isShop)
                        <span class="absolute top-2 left-2 flex items-center text-white bg-green-600 bg-opacity-80 px-3 py-1 rounded-full text-sm font-semibold">
                            <i class="fas fa-store mr-1"></i> Магазин
                        </span>
                        @endif
                        <!-- Heart Icon -->
                        <span class="absolute bottom-2 right-2 w-9 h-9 flex items-center justify-center text-white bg-gray-800 bg-opacity-60 rounded-full heart-icon" data-product-id="{{ $product->id }}">
                            <i class="fas fa-heart text-xl"></i>
                        </span>
                    </div>
                    <!-- Price -->
                    @if(request()->input('currency') === 'uzs') 
                        @if($product->currency === 'сум') 
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price, 2, '.', ' ')}} сум</p>
                        @else
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price * (float)$usdRate, 2, '.', ' ')}} сум</p>
                        @endif
                    @elseif (request()->input('currency') === 'usd') 
                        @if ($product->currency === 'доллар')
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price, 2, '.', ' ')}} $</p>
                        @else
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price / (float)$usdRate, 2, '.', ' ')}} у.е</p>
                        @endif
                    @elseif (!request()->has('currency'))
                        @if ($product->currency === 'доллар')
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price, 2, '.', ' ')}} у.е</p>
                        @else
                            <p class="text-xl mt-4 font-bold text-gray-800">{{ number_format((float)$product->price, 2, '.', ' ')}} сум</p>
                        @endif
                    @endif
                    <!-- Title with fixed height -->
                    <div class="h-14 mb-2 overflow-hidden">
                        <p class="text-gray-700 text-lg font-semibold line-clamp-2">{{ $product->name }}</p>
                    </div>
                    <!-- Region and Date Container (fixed at bottom) -->
                    <div class="mt-auto">
                        <!-- Region (assuming a region field; adjust if different) -->
                        <p class="text-gray-700 text-sm">
                            {{ $product->region ? ($product->region->parent ? $product->region->parent->name . ', ' . $product->region->name : $product->region->name) : 'Ташкент' }}
                        </p>
                        <!-- Date (assuming a created_at or similar field) -->
                        <p class="text-gray-700 mt-1 text-sm">{{ $product->created_at->locale('ru')->isoFormat('D MMMM YYYY') }}</p>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-gray-500">Нет объявлений</p>
        @endforelse
    </section>

    <!-- Load More Button (always present initially) -->
    <div class="text-center">
        <button id="load-more" class="bg-[#3A78FF] text-white px-8 py-2 rounded-xl flex items-center justify-center mx-auto min-w-[200px]">
            <span class="button-text">Показать еще</span>
            <span class="ml-2 loading-spinner hidden w-5 h-5 border-4 border-t-transparent border-white rounded-full animate-spin"></span>
        </button>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const heartIcons = document.querySelectorAll('.heart-icon');
        heartIcons.forEach(icon => {
            icon.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Heart clicked for product ID:', this.getAttribute('data-product-id'));
            });
        });

        const loadMoreButton = document.getElementById('load-more');
        let page = parseInt({{ $products->currentPage() }} || 1); // Start with the current page
        let lastPage = {{ $products->lastPage() }}; // Initial last page from Blade

        if (loadMoreButton) {
            console.log('Load More button found');
            loadMoreButton.addEventListener('click', function () {
                console.log('Load More button clicked');
                const spinner = loadMoreButton.querySelector('.loading-spinner');
                const buttonText = loadMoreButton.querySelector('.button-text');
                if (!spinner || !buttonText) {
                    console.error('Spinner or button text not found in loadMoreButton');
                    return;
                }
                spinner.classList.remove('hidden');
                buttonText.classList.add('hidden');

                page++;
                console.log(`Fetching page ${page}`);
                fetch(`https://biztorg.uz/get-paginated-products?page=${page}&per_page=24`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Fetch response:', response);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched data:', data);
                    if (data.products && data.products.length > 0) {
                        const productGrid = document.getElementById('product-grid');
                        data.products.forEach(product => {
                            const currency = '{{ request()->input('currency') ?: 'default' }}';
                            const priceHtml = currency === 'uzs' ? 
                                (product.currency === 'сум' ? `${number_format(product.price, 2, '.', ' ')} сум` : `${number_format(product.price * usdRate, 2, '.', ' ')} сум`) :
                                (currency === 'usd' ? 
                                    (product.currency === 'доллар' ? `${number_format(product.price, 2, '.', ' ')} $` : `${number_format(product.price / usdRate, 2, '.', ' ')} у.е`) :
                                    (product.currency === 'доллар' ? `${number_format(product.price, 2, '.', ' ')} у.е` : `${number_format(product.price, 2, '.', ' ')} сум`));
                            const productHtml = `
                                <a class="hover:bg-gray-200 rounded-xl" href="/obyavlenie/${product.slug}">
                                    <div class="flex flex-col mb-2 p-2 rounded-lg h-[450px]">
                                        <div class="overflow-hidden rounded-2xl h-[310px] relative">
                                            <img src="${product.images.length > 0 ? '/storage/' + product.images[0].image_url : '/default.png'}" 
                                                 alt="${product.name}" 
                                                 class="object-cover w-full h-full transition-transform duration-300 ease-in-out transform hover:scale-105">
                                            ${product.user && product.user.isShop ? `
                                                <span class="absolute top-2 left-2 flex items-center text-white bg-green-600 bg-opacity-80 px-3 py-1 rounded-full text-sm font-semibold">
                                                    <i class="fas fa-store mr-1"></i> Магазин
                                                </span>` : ''}
                                            <span class="absolute bottom-2 right-2 w-9 h-9 flex items-center justify-center text-white bg-gray-800 bg-opacity-60 rounded-full heart-icon" data-product-id="${product.id}">
                                                <i class="fas fa-heart text-xl"></i>
                                            </span>
                                        </div>
                                        <p class="text-xl mt-4 font-bold text-gray-800">${priceHtml}</p>
                                        <div class="h-14 mb-2 overflow-hidden">
                                            <p class="text-gray-700 text-lg font-semibold line-clamp-2">${product.name}</p>
                                        </div>
                                        <div class="mt-auto">
                                            <p class="text-gray-700 text-sm">${product.region ? (product.region.parent ? product.region.parent.name + ', ' + product.region.name : product.region.name) : 'Ташкент'}</p>
                                            <p class="text-gray-700 mt-1 text-sm">${new Date(product.created_at).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                        </div>
                                    </div>
                                </a>
                            `;
                            productGrid.insertAdjacentHTML('beforeend', productHtml);
                        });
                        // Update last_page and page from the API response
                        lastPage = data.last_page;
                        page = data.current_page; // Sync with server’s current page
                    }
                    if (page >= lastPage || (data.products && data.products.length === 0)) {
                        loadMoreButton.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching products:', error))
                .finally(() => {
                    spinner.classList.add('hidden');
                    buttonText.classList.remove('hidden');
                });
            });
        } else {
            console.log('Load More button not found');
        }
    });
</script>

<script>
    // Pass $usdRate to JavaScript
    const usdRate = {{ $usdRate }};
    // Define number_format function for JavaScript (simplified version)
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        let n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                let k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }
</script>