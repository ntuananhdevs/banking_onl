<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white p-8 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-2xl font-bold mb-2 text-gray-900">Đăng nhập</h1>
            <p class="text-sm text-gray-600 mb-6">Vui lòng đăng nhập để tiếp tục</p>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-2 text-gray-700">
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        autofocus
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="your@email.com"
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium mb-2 text-gray-700">
                        Mật khẩu
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="••••••••"
                    >
                </div>

                <div class="mb-6 flex items-center">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember" 
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <label for="remember" class="ml-2 text-sm text-gray-600">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <button 
                    type="submit"
                    class="w-full px-5 py-2.5 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors mb-4"
                >
                    Đăng nhập
                </button>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Chưa có tài khoản? 
                    <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-medium">
                        Đăng ký ngay
                    </a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-gray-500">
            <p>Demo accounts:</p>
            <p class="mt-1">admin@example.com / password</p>
            <p>user1@example.com / password</p>
        </div>
    </div>
</body>
</html>
