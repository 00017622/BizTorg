@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($category->name), 160) }}">
    <meta name="keywords" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta property="og:title" content="Категория: {{ $category->name }}">
    <meta property="og:description" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta property="og:image" content="{{ $category->image_url ? asset('storage/' . $category->image_url) : asset('default.png') }}">
    <meta property="og:url" content="{{ route('category.show', $category->slug) }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Категория: {{ $category->name }}">
    <meta name="twitter:description" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta name="twitter:image" content="{{ $category->image_url ? asset('storage/' . $category->image_url) : asset('default.png') }}">
@endsection

@section('title', 'Категория: ' . $category->name . (isset($selectedSubcategory) ? ' - ' . $selectedSubcategory->name : ''))
@extends('layouts.app')
@section('main')
    @include('components.filters')

    <section class="px-8" id="productSection">
        @include('components.card')
    </section>

    <div class="pagination flex justify-center mt-8" id="pagination">
        <button id="loadMore" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600" style="display: {{ $products->hasMorePages() ? 'block' : 'none' }}">
            Загрузить еще
        </button>
    </div>

    <script>
        // Initialize filter state
        let filters = {
            subcategory: '{{ request()->input("subcategory") ?? "" }}',
            region: '{{ request()->input("region") ?? "" }}',
            city: '{{ request()->input("city") ?? "" }}',
            price_from: '{{ request()->input("price_from") ?? "" }}',
            price_to: '{{ request()->input("price_to") ?? "" }}',
            currency: '{{ request()->input("currency") ?? "usd" }}',
            type: '{{ request()->input("type") ?? "" }}',
            date_filter: '{{ request()->input("date_filter") ?? "" }}',
            search: '{{ request()->input("search") ?? "" }}',
            attributes: {}
        };

        // Event listeners for filter selections
        document.querySelectorAll('[data-filter]').forEach(element => {
            element.addEventListener('click', (e) => {
                const filter = e.target.getAttribute('data-filter');
                const value = e.target.getAttribute('data-value');
                if (filter.startsWith('attribute_')) {
                    filters.attributes[filter] = value;
                } else {
                    filters[filter] = value;
                }
            });
        });

        // Apply filters button event
        document.getElementById('applyFilters').addEventListener('click', () => {
            fetchProducts(5);
        });

        // Load more button event
        document.getElementById('loadMore').addEventListener('click', () => {
            const nextPage = parseInt(document.getElementById('loadMore').getAttribute('data-next-page')) || 2;
            fetchProducts(nextPage);
        });

        // Fetch products function
        function fetchProducts(page) {
            const url = new URL(`{{ route("category.filter", $category->slug) }}?page=${page}`);
            Object.keys(filters).forEach(key => {
                if (key === 'attributes' && Object.keys(filters.attributes).length > 0) {
                    Object.entries(filters.attributes).forEach(([attrKey, attrValue]) => {
                        url.searchParams.append(attrKey, attrValue);
                    });
                } else if (filters[key] !== '' && key !== 'attributes') {
                    url.searchParams.append(key, filters[key]);
                }
            });

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                document.getElementById('productSection').innerHTML = data.products;
                document.getElementById('loadMore').style.display = data.has_more ? 'block' : 'none';
                document.getElementById('loadMore').setAttribute('data-next-page', data.next_page);
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
@endsection