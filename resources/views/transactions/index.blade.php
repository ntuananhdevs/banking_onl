@extends('layouts.app')

@section('title', 'Lịch sử giao dịch - ' . config('app.name', 'Laravel'))

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold mb-2 text-gray-900">Lịch sử giao dịch</h1>
            <p class="text-gray-600">Xem tất cả các giao dịch nạp tiền và rút tiền của bạn</p>
        </div>

        <!-- Filter form -->
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mb-6">
            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Mã giao dịch, nội dung..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Type filter -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Loại giao dịch</label>
                    <select 
                        id="type" 
                        name="type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Tất cả</option>
                        <option value="deposit" {{ ($filters['type'] ?? '') === 'deposit' ? 'selected' : '' }}>Nạp tiền</option>
                        <option value="withdrawal" {{ ($filters['type'] ?? '') === 'withdrawal' ? 'selected' : '' }}>Rút tiền</option>
                        <option value="transfer" {{ ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                    </select>
                </div>

                <!-- Status filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select 
                        id="status" 
                        name="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Tất cả</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Đang chờ</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>

                <!-- Date from -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input 
                        type="date" 
                        id="date_from" 
                        name="date_from" 
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Date to -->
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input 
                        type="date" 
                        id="date_to" 
                        name="date_to" 
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Buttons -->
                <div class="md:col-span-5 flex gap-2">
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Lọc
                    </button>
                    <a 
                        href="{{ route('transactions.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
                    >
                        Xóa bộ lọc
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions table -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Ngày giờ</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Loại</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Số tiền</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Trạng thái</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Mã giao dịch</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Nội dung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-4 text-gray-600">
                                    {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($transaction->type === 'deposit')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                            Nạp tiền
                                        </span>
                                    @elseif($transaction->type === 'withdrawal')
                                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium">
                                            Rút tiền
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                            Chuyển khoản
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    @if($transaction->type === 'deposit')
                                        <span class="text-green-600">+{{ number_format($transaction->amount, 0, ',', '.') }} VNĐ</span>
                                    @else
                                        <span class="text-red-600">-{{ number_format($transaction->amount, 0, ',', '.') }} VNĐ</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($transaction->status === 'completed')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                            Hoàn thành
                                        </span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">
                                            Đang chờ
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                            Thất bại
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600 font-mono text-xs">
                                    {{ $transaction->transaction_id ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-xs">
                                    <div class="max-w-xs truncate" title="{{ $transaction->transfer_content ?? '-' }}">
                                        {{ $transaction->transfer_content ?? '-' }}
                                    </div>
                                    @if($transaction->type === 'deposit' && $transaction->status === 'pending')
                                        <a 
                                            href="{{ route('deposit.show', $transaction->id) }}"
                                            class="text-blue-600 hover:underline text-xs mt-1 inline-block"
                                        >
                                            Xem chi tiết
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                    Không có giao dịch nào
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
