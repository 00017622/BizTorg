@extends('layouts.app')

@section('main')

<section class="px-10">
    <nav class=" py-4">
        <ul class="flex items-center gap-8">
            <li class="flex items-center-2">
    <a href="{{ route('profile.view') }}" class="nav-link {{ $section == '' ? 'nav-link-active text-white bg-gray-900 font-semibold px-6 py-4 rounded-xl' : 'hover:text-gray-100 border-gray-900 border rounded-xl px-6 py-4' }} relative flex items-center gap-2">
        <i class='bx bx-user text-xl'></i>
        Профиль
    </a>
</li>
<li>
    <a href="{{ route('profile.products') }}" class="nav-link {{ $section == 'products' ? 'nav-link-active text-white bg-gray-900 font-semibold px-6 py-4 rounded-xl' : 'hover:text-gray-100 border-gray-900 border rounded-xl px-6 py-4' }} relative flex items-center gap-2">
        <i class='bx bx-package text-xl'></i>
        Мои обьявления
    </a>
</li>
<li>
    <a href="{{ route('profile.favorites') }}" class="nav-link {{ $section == 'favorites' ? 'nav-link-active text-white bg-gray-900 font-semibold px-6 py-4 rounded-xl' : 'hover:text-gray-100 border-gray-900 border rounded-xl px-6 py-4' }} relative flex items-center gap-2">
        <i class='bx bxs-heart text-xl text-white'></i>
        Избранные
    </a>
</li>
            <li>
                <a href="{{ route('product.fetch') }}" class="nav-link {{ $section == 'add' ? 'nav-link-active text-white bg-gray-900 font-semibold px-6 py-4 rounded-xl' : 'hover:text-gray-100 px-6 border-gray-900 border rounded-xl py-4' }} relative flex items-center gap-2">
                    <i class='bx bx-message-square-add text-xl text-white'></i>
                    Добавить обьявление
                </a>
            </li>
        </ul>
    </nav>

    <div class="content mt-10">
        @yield('content')
    </div>
</section>

@endsection
