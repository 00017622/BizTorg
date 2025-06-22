<nav class="bg-white px-12 py-6 text-gray-800 flex items-center justify-between">
    <div class="flex items-center space-x-4">
        <a href="/" class="flex items-center space-x-2">
            <h1 class="text-4xl">BizTorgUz</h1>
        </a>
    </div>

    <div class="flex items-center space-x-2">
        <a href="{{ route('profile.favorites') }}">
        <div class="text-gray-700 px-4 hover:bg-gray-200 rounded-md  transition-colors flex items-center gap-2 p-2 cursor-pointer">
            <i class="far fa-heart text-lg"></i>
            <span class="text-lg leading-none">Избранное</span>
        </div>
        </a>
        
        @if (auth()->check())
        <a href="{{route('profile.view')}}">
            <div class="text-gray-700 px-4 hover:bg-gray-200 rounded-md  flex  items-center gap-3 p-2 cursor-pointer">
                <i class="far fa-user text-lg"></i>
                <span class="text-lg leading-none">Профиль</span>
            </div>
            </a>
        @else
        <a href="{{route('login')}}">
            <div class="text-gray-700 px-4 hover:bg-gray-200 rounded-md  flex   items-center gap-3 p-2 cursor-pointer">
                <i class="far fa-user text-lg"></i>
                <span class="text-lg leading-none">Войти</span>
            </div>
            </a>
        @endif
        
        <a href="{{ route('product.fetch') }}">
        <div class="text-gray-700 px-4 hover:bg-gray-200 rounded-2xl bg-gray-200 flex items-center gap-3 p-2 border cursor-pointer">
            <i class="fas fa-plus-circle text-xl"></i>
            <span class="text-lg leading-none">Подать обьявление</span>
        </div>
        </a>
    </div>
</nav>
