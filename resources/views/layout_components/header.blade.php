<nav class="bg-black px-16 py-6 text-white flex items-center justify-between border-b border-[#333]">
    <div class="flex items-center space-x-4">
        <a href="/" class="flex items-center space-x-2">
            <h1 class="text-4xl">BizTorgUz</h1>
        </a>
    </div>

    <div class="flex items-center space-x-4">
        <div class="text-white border px-4 border-slate-500 hover:bg-slate-900 transition-colors flex items-center gap-3 p-2 rounded-lg cursor-pointer">
            <i class="far fa-heart text-lg"></i>
            <span class="text-lg leading-none">Избранное</span>
        </div>
        
        @if (auth()->check())
        <a href="{{route('profile.view')}}">
            <div class="text-white border px-4 border-slate-500 flex  hover:bg-slate-900 items-center gap-3 p-2 rounded-lg cursor-pointer">
                <i class="far fa-user text-lg"></i>
                <span class="text-lg leading-none">Профиль</span>
            </div>
            </a>
        @else
        <a href="{{route('login')}}">
            <div class="text-white border px-4 border-slate-500 flex  hover:bg-slate-900 items-center gap-3 p-2 rounded-lg cursor-pointer">
                <i class="far fa-user text-lg"></i>
                <span class="text-lg leading-none">Войти</span>
            </div>
            </a>
        @endif
        

        <div class="text-white border px-4 border-slate-500 hover:bg-slate-900 flex items-center gap-3 p-2 rounded-lg cursor-pointer">
            <i class="fas fa-plus-circle text-xl"></i>
            <span class="text-lg leading-none">Подать обьявление</span>
        </div>
    </div>
</nav>
