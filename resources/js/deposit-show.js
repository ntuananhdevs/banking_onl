
import { formatAmount, showNotification } from './utils.js';

export function initDepositShowPage() {
    const depositCode = window.depositCode || '';
    const transactionId = window.transactionId || 0;
    const currentStatus = window.currentStatus || '';
    const transactionAmount = window.transactionAmount || 0;
    const transferContent = window.transferContent || '';
    const checkStatusUrlTemplate = window.checkStatusUrlTemplate || '';
    
    console.log('Transaction status checker initialized for deposit_code:', depositCode, 'Current status:', currentStatus);
    
    // Nếu đã completed hoặc failed, không cần check
    if (currentStatus !== 'pending') {
        console.log('Transaction is not pending, stopping checker');
        return;
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
        if (!checkStatusUrlTemplate) {
            console.error('checkStatusUrlTemplate not found');
            return;
        }
        
        const url = checkStatusUrlTemplate.replace(':code', depositCode);
        console.log('Checking status for deposit_code:', depositCode, 'URL:', url);
        
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
        
        console.log('Interval tick - checking status for deposit_code:', depositCode);
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
}

// Auto-init khi DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDepositShowPage);
} else {
    initDepositShowPage();
}
