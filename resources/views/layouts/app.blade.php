<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-white text-gray-900 min-h-screen">
    @auth
        <!-- Navigation Header -->
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <a href="{{ route('deposit.index') }}" class="text-xl font-bold text-gray-900">
                            {{ config('app.name', 'Banking') }}
                        </a>
                        <div class="hidden md:flex space-x-4">
                            <a href="{{ route('deposit.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                                Nạp tiền
                            </a>
                            <a href="{{ route('transactions.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                                Lịch sử giao dịch
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ number_format(auth()->user()->balance, 0, ',', '.') }} VNĐ
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
