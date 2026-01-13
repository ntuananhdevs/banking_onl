import './bootstrap';
import { copyToClipboard, showNotification } from './utils';

// Export globally for inline onclick handlers
window.copyToClipboard = copyToClipboard;
window.showNotification = showNotification;