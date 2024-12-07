@extends('layouts.app')

@section('main')
<section class="flex justify-center items-center h-screen bg-black">
    <div class="w-full max-w-md bg-black border border-gray-800 p-8 rounded-lg shadow-lg">
        <!-- Social Login Buttons -->
        <div class="mb-6">
            <a href="{{ route('google.redirect') }}" class="w-full flex justify-center items-center bg-black border border-gray-800 text-white py-2 px-4 rounded-lg mb-2 hover:bg-gray-800 transition">
                <i class="fab fa-google mr-3 text-2xl"></i> Продолжить через Google
            </a>
            <a href="{{ route('facebook.redirect') }}" class="w-full flex justify-center items-center bg-black border border-gray-800 text-white py-2 px-4 rounded-lg mb-2 hover:bg-gray-800 transition">
                <i class="fab fa-facebook mr-3 text-2xl"></i> Продолжить через Facebook
            </a>

            <div class="telegram-btn-wrapper">
                {!! Socialite::driver('telegram')->getButton(['style' => 'font-size: 1.25rem; font-family: Arial, sans-serif;']) !!}
            </div>            

        </div>

        <!-- Tabs -->
        <div class="flex justify-center mb-6 bg-black border border-gray-800 border-opacity-50 rounded-full">
            <button id="login-tab" class="w-1/2 py-2 text-center text-white font-bold border-b-2 border-white mx-6" onclick="showLoginForm()">Войти</button>
            <button id="register-tab" class="w-1/2 py-2 text-center text-gray-400 hover:text-white border-b-2 border-transparent mx-6" onclick="showRegisterForm()">Зарегистрироваться</button>
        </div>

        <!-- Login Form -->
        <div id="login-form">
            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Gmail почта</label>
                    <input type="email" name="email" id="email" required class="w-full bg-black px-4 py-2 rounded border border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900">
                </div>
                <div class="relative">
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Пароль</label>
                    <input type="password" name="password" id="password" required class="w-full px-4 py-2 bg-black rounded border border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900">
                </div>
                <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 transition text-white py-2 px-4 rounded">Войти</button>
            </form>
        </div>

        <!-- Registration Form -->
        <div id="registration-form" class="hidden">
            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Gmail почта</label>
                    <input type="email" name="email" id="email" required class="w-full bg-black px-4 py-2 rounded border border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Пароль</label>
                    <input type="password" name="password" id="password" required class="w-full bg-black px-4 py-2 rounded border border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900">
                </div>
                <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded">Зарегистрироваться</button>
            </form>
        </div>
    </div>

    <script>
        function showLoginForm() {
            document.getElementById('login-form').classList.remove('hidden');
            document.getElementById('registration-form').classList.add('hidden');

            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');

            loginTab.classList.add('border-white', 'font-bold');
            loginTab.classList.remove('text-gray-400');

            registerTab.classList.remove('border-white', 'font-bold');
            registerTab.classList.add('text-gray-400', 'border-transparent');
        }

        function showRegisterForm() {
            document.getElementById('login-form').classList.add('hidden');
            document.getElementById('registration-form').classList.remove('hidden');

            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');

            registerTab.classList.add('border-white', 'font-bold');
            registerTab.classList.remove('text-gray-400');

            loginTab.classList.remove('border-white', 'font-bold');
            loginTab.classList.add('text-gray-400', 'border-transparent');
        }
    </script>
</section>
@endsection
