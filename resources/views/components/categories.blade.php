<section class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-9  py-4 gap-4 px-8">
@foreach ($categories as $category)
<a href="{{ route('category.show', array_merge(['slug' => $category->slug], ['currency' => 'uzs'])) }}">
<div class="flex flex-col items-center space-y-2">
        <img 
            src="{{ asset('storage/' . $category->image_url) }}" 
            alt="{{ $category->name }}" 
            class="object-contain bg-white rounded-full flex items-center justify-center w-28 h-28"
        />
    <p class="text-center text-sm font-medium">{{$category->name}}</p>
</div>
</a>
@endforeach
</section>