
export function initDepositPage() {
    console.log('Transaction status checker initialized on deposit page');
    
    function getPendingTransactionCodes() {
        const rows = document.querySelectorAll('tr.transaction-row[data-status="pending"]');
        console.log('Found pending transaction rows:', rows.length);
        
        const codes = Array.from(rows)
            .map(row => {
                const code = row.getAttribute('data-deposit-code');
                console.log('Deposit code:', code, 'Status:', row.getAttribute('data-status'));
                return code;
            })
            .filter(code => code && code.trim() !== '');
        
        console.log('Pending transaction codes:', codes);
        return codes;
    }
    
    let pendingTransactionCodes = getPendingTransactionCodes();
    console.log('Pending transaction codes:', pendingTransactionCodes);
    
    if (pendingTransactionCodes.length === 0) {
        console.log('No pending transactions found, stopping checker');
        return; // Không có transaction nào đang pending
    }
    
    console.log('Starting status checker for', pendingTransactionCodes.length, 'transactions');
    
    // Function để update status badge
    function updateStatusBadge(badge, newStatus) {
        badge.setAttribute('data-status', newStatus);
        
        if (newStatus === 'completed') {
            badge.className = 'px-2 py-1 bg-green-100 text-green-700 rounded text-xs status-badge';
            badge.textContent = 'Hoàn thành';
        } else if (newStatus === 'pending') {
            badge.className = 'px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs status-badge';
            badge.textContent = 'Đang chờ';
        } else {
            badge.className = 'px-2 py-1 bg-red-100 text-red-700 rounded text-xs status-badge';
            badge.textContent = 'Thất bại';
        }
    }
    
    // Function để update transaction row
    function updateTransactionRow(depositCode, newStatus) {
        const row = document.querySelector(`tr.transaction-row[data-deposit-code="${depositCode}"]`);
        if (!row) return;
        
        const currentStatus = row.getAttribute('data-status');
        if (currentStatus === newStatus) return; // Không có thay đổi
        
        // Update row status
        row.setAttribute('data-status', newStatus);
        
        // Update status badge
        const statusBadge = row.querySelector('.status-badge');
        if (statusBadge) {
            updateStatusBadge(statusBadge, newStatus);
        }
        
        // Nếu đã completed, thêm animation
        if (newStatus === 'completed') {
            row.classList.add('bg-green-50', 'transition-colors', 'duration-500');
            setTimeout(() => {
                row.classList.remove('bg-green-50');
            }, 3000);
        }
    }
    
    // Function để check status từ server
    function checkStatus() {
        // Lấy lại danh sách pending codes (có thể đã thay đổi)
        pendingTransactionCodes = getPendingTransactionCodes();
        
        if (pendingTransactionCodes.length === 0) {
            console.log('No more pending transactions, stopping checker');
            return; // Không còn transaction nào pending
        }
        
        // Lấy checkStatusUrl từ window object (được set từ Blade template)
        const checkStatusUrlTemplate = window.checkStatusUrlTemplate;
        if (!checkStatusUrlTemplate) {
            console.error('checkStatusUrlTemplate not found');
            return;
        }
        
        // Check từng transaction riêng biệt
        pendingTransactionCodes.forEach(depositCode => {
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
                console.log('Response status:', response.status, 'for deposit_code:', depositCode);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data for deposit_code', depositCode, ':', data);
                if (data.success && data.transactions && data.transactions.length > 0) {
                    const transaction = data.transactions[0];
                    console.log('Updating transaction:', transaction.deposit_code, 'Status:', transaction.status);
                    updateTransactionRow(transaction.deposit_code, transaction.status);
                }
            })
            .catch(error => {
                console.error('Error checking transaction status for', depositCode, ':', error);
            });
        });
    }
    
    // Check status mỗi 1 giây (1 req/s)
    console.log('Setting up interval to check status every 1 second');
    const intervalId = setInterval(() => {
        // Kiểm tra lại xem còn transaction nào pending không
        pendingTransactionCodes = getPendingTransactionCodes();
        
        if (pendingTransactionCodes.length === 0) {
            // Không còn transaction nào pending, dừng polling
            console.log('No pending transactions, clearing interval');
            clearInterval(intervalId);
            return;
        }
        
        console.log('Interval tick - checking status for', pendingTransactionCodes.length, 'transactions');
        checkStatus();
    }, 1000); // 1 giây = 1000ms
    
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
    document.addEventListener('DOMContentLoaded', initDepositPage);
} else {
    initDepositPage();
}
