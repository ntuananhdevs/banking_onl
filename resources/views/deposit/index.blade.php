@extends('layouts.app')

@section('title', 'Nạp tiền - ' . config('app.name', 'Laravel'))

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold mb-2 text-gray-900">Nạp tiền</h1>
            <p class="text-gray-600">Số dư hiện tại: <span class="font-semibold text-gray-900">{{ number_format($user->balance, 0, ',', '.') }} VNĐ</span></p>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Form nạp tiền -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">Thông tin nạp tiền</h2>
                
                <form action="{{ route('deposit.create') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium mb-2 text-gray-700">
                            Số tiền nạp (VNĐ)
                        </label>
                        <input 
                            type="number" 
                            id="amount" 
                            name="amount" 
                            min="1000" 
                            max="100000000" 
                            step="1000"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Nhập số tiền (tối thiểu 1,000 VNĐ)"
                            value="{{ old('amount') }}"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            Số tiền tối thiểu: 1,000 VNĐ
                        </p>
                    </div>

                    <button 
                        type="submit"
                        class="w-full px-5 py-2.5 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors"
                    >
                        Tạo yêu cầu nạp tiền
                    </button>
                </form>
            </div>

            <!-- Hướng dẫn -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">Hướng dẫn nạp tiền</h2>
                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                    <li>Nhập số tiền bạn muốn nạp vào ô bên cạnh</li>
                    <li>Nhấn "Tạo yêu cầu nạp tiền"</li>
                    <li>Quét QR Code hoặc chuyển khoản theo thông tin được cung cấp</li>
                    <li>Nội dung chuyển khoản phải chứa mã định danh của bạn</li>
                    <li>Số tiền sẽ được cập nhật tự động sau khi chuyển khoản thành công</li>
                </ol>
            </div>
        </div>

        <!-- Lịch sử giao dịch -->
        @if($recentTransactions->count() > 0)
            <div class="mt-8 bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">Lịch sử giao dịch gần đây</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-4 font-medium text-gray-900">Ngày</th>
                                <th class="text-left py-2 px-4 font-medium text-gray-900">Số tiền</th>
                                <th class="text-left py-2 px-4 font-medium text-gray-900">Trạng thái</th>
                                <th class="text-left py-2 px-4 font-medium text-gray-900">Nội dung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $transaction)
                                <tr 
                                    class="border-b border-gray-200 transaction-row"
                                    data-transaction-id="{{ $transaction->id }}"
                                    data-deposit-code="{{ $transaction->deposit_code ?? '' }}"
                                    data-status="{{ $transaction->status }}"
                                >
                                    <td class="py-2 px-4 text-gray-600">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-2 px-4 font-medium text-gray-900">
                                        {{ number_format($transaction->amount, 0, ',', '.') }} VNĐ
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($transaction->status === 'completed')
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs status-badge" data-status="completed">
                                                Hoàn thành
                                            </span>
                                        @elseif($transaction->status === 'pending')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs status-badge" data-status="pending">
                                                Đang chờ
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs status-badge" data-status="failed">
                                                Thất bại
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 text-gray-600 text-xs">
                                        {{ $transaction->transfer_content ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Set data cho JavaScript
        window.checkStatusUrlTemplate = '{{ route("transactions.check-status", ["deposit_code" => ":code"]) }}';
    </script>
    @vite(['resources/js/deposit.js'])
    @endpush
@endsection
