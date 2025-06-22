<section class="relative py-4 px-12">
    <!-- Slider Container -->
    <div class="slider-container overflow-x-auto whitespace-nowrap scroll-smooth hide-scrollbar" id="sliderContainer">
        <div class="inline-flex gap-2">
            @foreach ($categories as $category)
                <a class="hover:bg-gray-100 rounded-lg p-2" href="{{ route('category.show', array_merge(['slug' => $category->slug], ['currency' => 'uzs'])) }}">
                    <div class="flex flex-col items-center space-y-2 min-w-[120px]">
                        <img 
                            src="{{ asset('storage/' . $category->image_url) }}" 
                            alt="{{ $category->name }}" 
                            class="object-fit:contain bg-white rounded-3xl flex items-center justify-center w-32 h-32"
                        />
                        <p class="text-center text-medium font-medium">{{ $category->name }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Navigation Arrows -->
    <button class="slider-prev absolute left-4 top-1/2 transform -translate-y-1/2 -mt-4 w-14 h-14 bg-gray-100 flex items-center justify-center rounded-full" id="prevBtn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
    </button>
    <button class="slider-next absolute right-4 top-1/2 transform -translate-y-1/2 -mt-4 w-14 h-14 bg-gray-100 flex items-center justify-center rounded-full" id="nextBtn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
    </button>
</section>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const slider = document.querySelector('#sliderContainer');
        const prevBtn = document.querySelector('#prevBtn');
        const nextBtn = document.querySelector('#nextBtn');

        const scrollAmount = 300;

        // Function to update arrow visibility
        function updateArrowVisibility() {
            const scrollLeft = slider.scrollLeft;
            const scrollWidth = slider.scrollWidth;
            const clientWidth = slider.clientWidth;

            // Hide prev button if at the start
            if (scrollLeft <= 0) {
                prevBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'block';
            }

            // Hide next button if at the end
            if (scrollLeft + clientWidth >= scrollWidth) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'block';
            }
        }

        // Initial check
        updateArrowVisibility();

        // Update visibility on scroll
        slider.addEventListener('scroll', updateArrowVisibility);

        // Update visibility on click
        prevBtn.addEventListener('click', () => {
            slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        nextBtn.addEventListener('click', () => {
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
    });
</script>