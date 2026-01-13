<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-4">
        <div class="bg-white p-8 rounded-lg shadow-lg border border-gray-200">
            <h1 class="text-2xl font-bold mb-2 text-gray-900">Đăng ký</h1>
            <p class="text-sm text-gray-600 mb-6">Tạo tài khoản mới để bắt đầu</p>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium mb-2 text-gray-700">
                        Họ và tên
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        required
                        autofocus
                        value="{{ old('name') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Nguyễn Văn A"
                    >
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-2 text-gray-700">
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
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
                    <p class="mt-1 text-xs text-gray-500">
                        Mật khẩu tối thiểu 8 ký tự
                    </p>
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium mb-2 text-gray-700">
                        Xác nhận mật khẩu
                    </label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="••••••••"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full px-5 py-2.5 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors mb-4"
                >
                    Đăng ký
                </button>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Đã có tài khoản? 
                    <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">
                        Đăng nhập ngay
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
