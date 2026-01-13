@extends('layouts.app')

@section('title', 'Chi tiết nạp tiền - ' . config('app.name', 'Laravel'))

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('deposit.index') }}" class="text-blue-600 hover:underline mb-4 inline-block">
                ← Quay lại trang nạp tiền
            </a>
            <h1 class="text-3xl font-bold mb-2 text-gray-900">Chi tiết giao dịch</h1>
            <p class="text-gray-600">
                Mã giao dịch: #{{ $transaction->id }}
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Thông tin giao dịch -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">Thông tin giao dịch</h2>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Số tiền</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($transaction->amount, 0, ',', '.') }} VNĐ
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">Trạng thái</p>
                        <div>
                            @if($transaction->status === 'completed')
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm font-medium status-badge" data-status="completed">
                                    Hoàn thành
                                </span>
                            @elseif($transaction->status === 'pending')
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm font-medium status-badge" data-status="pending">
                                    Đang chờ thanh toán
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm font-medium status-badge" data-status="failed">
                                    Thất bại
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">Nội dung chuyển khoản</p>
                        <p class="font-mono text-sm bg-gray-100 p-2 rounded text-gray-900">
                            {{ $transaction->transfer_content }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Quan trọng: Bạn phải nhập chính xác nội dung này khi chuyển khoản
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">Thời gian tạo</p>
                        <p class="text-gray-900">
                            {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Thông tin chuyển khoản -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">Thông tin chuyển khoản</h2>
                
                @if($transaction->status === 'completed')
                    <!-- Thông báo đã hoàn thành -->
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-green-800 font-semibold">Giao dịch đã hoàn thành</p>
                        </div>
                        <p class="text-sm text-green-700">
                            Số tiền đã được cập nhật vào tài khoản của bạn. Cảm ơn bạn đã sử dụng dịch vụ!
                        </p>
                        @if(isset($transaction->metadata['completed_at']))
                            <p class="text-xs text-green-600 mt-2">
                                Hoàn thành lúc: {{ \Carbon\Carbon::parse($transaction->metadata['completed_at'])->format('d/m/Y H:i:s') }}
                            </p>
                        @endif
                    </div>
                @endif
                
                @if(($bankInfo || $qrCodeUrl) && $transaction->status === 'pending')
                    <!-- Hiển thị QR Code -->
                    @if($qrCodeUrl)
                        <div class="mb-6 text-center">
                            <p class="text-sm text-gray-600 mb-3 font-medium">Quét mã QR để chuyển khoản nhanh</p>
                            <div class="bg-white p-4 rounded-lg border-2 border-gray-300 inline-block shadow-sm">
                                <img 
                                    src="{{ $qrCodeUrl }}" 
                                    alt="QR Code" 
                                    class="w-64 h-64 mx-auto" 
                                    id="qr-code-image"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'256\' height=\'256\'%3E%3Crect width=\'256\' height=\'256\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\'%3EQR Code%3C/text%3E%3Ctext x=\'50%25\' y=\'60%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENot Available%3C/text%3E%3C/svg%3E'; this.style.border='2px dashed #d1d5db';"
                                    loading="lazy"
                                >
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Mở app ngân hàng và quét mã QR để tự động điền thông tin
                            </p>
                            @if($bankInfo && config('app.debug'))
                                <p class="text-xs text-gray-400 mt-1">
                                    QR Code URL: <a href="{{ $qrCodeUrl }}" target="_blank" class="text-blue-500 hover:underline break-all">{{ substr($qrCodeUrl, 0, 60) }}...</a>
                                </p>
                            @endif
                        </div>
                    @endif

                    <!-- Thông tin tài khoản ngân hàng -->
                    @if($bankInfo)
                        <div class="space-y-4 mb-6">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Số tài khoản</p>
                                <p class="font-mono text-xl font-bold text-gray-900">
                                    {{ $bankInfo['account_number'] ?? '-' }}
                                </p>
                                <button 
                                    onclick="copyToClipboard('{{ $bankInfo['account_number'] ?? '' }}')" 
                                    class="mt-2 text-xs text-blue-600 hover:text-blue-800 underline"
                                >
                                    Sao chép số tài khoản
                                </button>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Tên chủ tài khoản</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $bankInfo['account_name'] ?? '-' }}
                                </p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Ngân hàng</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $bankInfo['bank_name'] ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif
                @elseif($transaction->status === 'pending')
                    <!-- Thông tin mặc định hoặc đang tải -->
                    <div class="text-center py-8">
                            <div class="mb-4">
                                <svg class="w-16 h-16 mx-auto text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-600 mb-2 font-medium">
                                Đang tải thông tin chuyển khoản...
                            </p>
                            <p class="text-xs text-gray-500 mb-4">
                                Vui lòng chờ trong giây lát hoặc liên hệ hỗ trợ nếu thông tin không hiển thị.
                            </p>
                            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-left">
                                <p class="text-xs text-yellow-800 font-medium mb-1">Lưu ý:</p>
                                <p class="text-xs text-yellow-700">
                                    Nếu thông tin chuyển khoản không hiển thị, vui lòng kiểm tra cấu hình SePay API hoặc liên hệ bộ phận hỗ trợ.
                                </p>
                            </div>
                    </div>
                @else
                    <!-- Thông báo khi đã completed hoặc failed -->
                    <div class="text-center py-8">
                        @if($transaction->status === 'completed')
                            <div class="mb-4">
                                <svg class="w-12 h-12 mx-auto text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-600 mb-2 font-medium">
                                Giao dịch đã hoàn thành
                            </p>
                            <p class="text-xs text-gray-500">
                                Thông tin chuyển khoản không còn cần thiết vì giao dịch đã được xử lý.
                            </p>
                        @else
                            <div class="mb-4">
                                <svg class="w-12 h-12 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-600 mb-2 font-medium">
                                Thông tin chuyển khoản chưa có sẵn
                            </p>
                            <p class="text-xs text-gray-500">
                                Vui lòng liên hệ bộ phận hỗ trợ để được hướng dẫn chuyển khoản.
                            </p>
                        @endif
                    </div>
                @endif

                <!-- Hướng dẫn -->
                @if($transaction->status === 'pending')
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                            <span class="mr-2"></span>
                            Lưu ý quan trọng:
                        </p>
                        <ul class="text-sm text-gray-700 space-y-2 list-disc list-inside">
                            <li>Chuyển đúng số tiền: <strong class="text-blue-700">{{ number_format($transaction->amount, 0, ',', '.') }} VNĐ</strong></li>
                            <li>Nhập chính xác nội dung chuyển khoản: 
                                <strong class="font-mono bg-white px-2 py-1 rounded border border-gray-300 text-blue-700">
                                    {{ $transaction->transfer_content }}
                                </strong>
                                <button 
                                    onclick="copyToClipboard('{{ $transaction->transfer_content }}')" 
                                    class="ml-2 text-xs text-blue-600 hover:text-blue-800 underline"
                                >
                                    Sao chép
                                </button>
                            </li>
                            <li>Số tiền sẽ được cập nhật tự động sau khi chuyển khoản thành công (thường trong vòng 1-5 phút)</li>
                        </ul>
                    </div>
                @elseif($transaction->status === 'completed')
                    <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Giao dịch đã hoàn thành
                        </p>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li>✓ Số tiền: <strong class="text-green-700">{{ number_format($transaction->amount, 0, ',', '.') }} VNĐ</strong> đã được cập nhật vào tài khoản</li>
                            <li>✓ Nội dung chuyển khoản: <strong class="font-mono text-green-700">{{ $transaction->transfer_content }}</strong></li>
                            @if(isset($transaction->metadata['sepay_reference_code']))
                                <li>✓ Mã tham chiếu SePay: <strong class="font-mono text-green-700">{{ $transaction->metadata['sepay_reference_code'] }}</strong></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh status của transaction này
        document.addEventListener('DOMContentLoaded', function() {
            const transactionId = {{ $transaction->id }};
            const currentStatus = '{{ $transaction->status }}';
            const transactionAmount = {{ $transaction->amount }};
            const transferContent = '{{ addslashes($transaction->transfer_content) }}';
            
            console.log('Transaction status checker initialized for transaction:', transactionId, 'Current status:', currentStatus);
            
            // Nếu đã completed hoặc failed, không cần check
            if (currentStatus !== 'pending') {
                console.log('Transaction is not pending, stopping checker');
                return;
            }
            
            // Function để format số tiền
            function formatAmount(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount);
            }
            
            // Function để update status badge
            function updateStatusBadge(newStatus) {
                const badge = document.querySelector('.status-badge');
                if (!badge) return;
                
                badge.setAttribute('data-status', newStatus);
                
                if (newStatus === 'completed') {
                    badge.className = 'px-3 py-1 bg-green-100 text-green-700 rounded text-sm font-medium status-badge';
                    badge.textContent = 'Hoàn thành';
                } else if (newStatus === 'pending') {
                    badge.className = 'px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm font-medium status-badge';
                    badge.textContent = 'Đang chờ thanh toán';
                } else {
                    badge.className = 'px-3 py-1 bg-red-100 text-red-700 rounded text-sm font-medium status-badge';
                    badge.textContent = 'Thất bại';
                }
            }
            
            // Function để update toàn bộ UI khi status thay đổi
            function updateUI(newStatus, transactionData) {
                if (newStatus === 'completed') {
                    console.log('Transaction completed, updating UI...');
                    
                    // Cập nhật phần thông tin chuyển khoản
                    const transferInfoSection = document.querySelector('.bg-white.p-6.rounded-lg.shadow-md.border.border-gray-200:last-child');
                    if (transferInfoSection) {
                        // Lấy reference code từ metadata nếu có
                        const referenceCode = transactionData?.metadata?.sepay_reference_code || '';
                        const completedAt = transactionData?.metadata?.completed_at || new Date().toISOString();
                        const completedAtFormatted = new Date(completedAt).toLocaleString('vi-VN', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                        
                        // Thay thế nội dung bằng thông báo completed
                        transferInfoSection.innerHTML = `
                            <h2 class="text-xl font-semibold mb-4 text-gray-900">Thông tin chuyển khoản</h2>
                            
                            <!-- Thông báo đã hoàn thành -->
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-green-800 font-semibold">Giao dịch đã hoàn thành</p>
                                </div>
                                <p class="text-sm text-green-700">
                                    Số tiền đã được cập nhật vào tài khoản của bạn. Cảm ơn bạn đã sử dụng dịch vụ!
                                </p>
                                <p class="text-xs text-green-600 mt-2">
                                    Hoàn thành lúc: ${completedAtFormatted}
                                </p>
                            </div>
                            
                            <!-- Thông báo khi đã completed -->
                            <div class="text-center py-8">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 mx-auto text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 mb-2 font-medium">
                                    Giao dịch đã hoàn thành
                                </p>
                                <p class="text-xs text-gray-500">
                                    Thông tin chuyển khoản không còn cần thiết vì giao dịch đã được xử lý.
                                </p>
                            </div>
                            
                            <!-- Hướng dẫn completed -->
                            <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Giao dịch đã hoàn thành
                                </p>
                                <ul class="text-sm text-gray-700 space-y-2">
                                    <li>✓ Số tiền: <strong class="text-green-700">${formatAmount(transactionAmount)} VNĐ</strong> đã được cập nhật vào tài khoản</li>
                                    <li>✓ Nội dung chuyển khoản: <strong class="font-mono text-green-700">${transferContent}</strong></li>
                                    ${referenceCode ? `<li>✓ Mã tham chiếu SePay: <strong class="font-mono text-green-700">${referenceCode}</strong></li>` : ''}
                                </ul>
                            </div>
                        `;
                    }
                    
                    // Thêm animation cho toàn bộ section
                    if (transferInfoSection) {
                        transferInfoSection.classList.add('bg-green-50', 'transition-colors', 'duration-500');
                        setTimeout(() => {
                            transferInfoSection.classList.remove('bg-green-50');
                        }, 3000);
                    }
                    
                    // Hiển thị thông báo thành công
                    showNotification('Giao dịch đã hoàn thành! Số tiền đã được cập nhật vào tài khoản.', 'success');
                }
            }
            
            // Function để check status từ server
            function checkStatus() {
                const url = '{{ route("transactions.check-status", ["id" => ":id"]) }}'.replace(':id', transactionId);
                console.log('Checking status for transaction:', transactionId, 'URL:', url);
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success && data.transactions && data.transactions.length > 0) {
                        const transaction = data.transactions[0];
                        console.log('Transaction status:', transaction.status);
                        
                        // Lấy status hiện tại từ badge
                        const currentBadge = document.querySelector('.status-badge');
                        const currentBadgeStatus = currentBadge ? currentBadge.getAttribute('data-status') : currentStatus;
                        
                        if (transaction.status !== currentBadgeStatus) {
                            console.log('Status changed from', currentBadgeStatus, 'to', transaction.status);
                            updateStatusBadge(transaction.status);
                            updateUI(transaction.status, transaction);
                            
                            // Cập nhật currentStatus để so sánh lần sau
                            if (transaction.status === 'completed') {
                                // Dừng polling khi đã completed
                                const intervalId = window.transactionCheckIntervalId;
                                if (intervalId) {
                                    clearInterval(intervalId);
                                    console.log('Transaction completed, stopped polling');
                                }
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking transaction status:', error);
                });
            }
            
            // Check status mỗi 1 giây (1 req/s)
            console.log('Setting up interval to check status every 1 second');
            const intervalId = setInterval(() => {
                // Kiểm tra lại status hiện tại
                const currentBadge = document.querySelector('.status-badge');
                if (currentBadge && currentBadge.getAttribute('data-status') !== 'pending') {
                    // Không còn pending, dừng polling
                    console.log('Transaction is no longer pending, clearing interval');
                    clearInterval(intervalId);
                    window.transactionCheckIntervalId = null;
                    return;
                }
                
                console.log('Interval tick - checking status for transaction:', transactionId);
                checkStatus();
            }, 1000); // 1 giây = 1000ms
            
            // Lưu intervalId vào window để có thể clear từ function khác
            window.transactionCheckIntervalId = intervalId;
            console.log('Interval ID:', intervalId);
            
            // Cleanup khi user rời khỏi trang
            window.addEventListener('beforeunload', () => {
                if (intervalId) {
                    clearInterval(intervalId);
                }
            });
        });
        
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showNotification('Đã sao chép vào clipboard!');
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('Đã sao chép vào clipboard!');
            } catch (err) {
                console.error('Fallback: Failed to copy', err);
                showNotification('Không thể sao chép. Vui lòng sao chép thủ công.', 'error');
            }
            document.body.removeChild(textArea);
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 2000);
        }
    </script>
    @endpush
@endsection
