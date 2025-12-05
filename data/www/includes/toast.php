<?php
// Toast notification system
function showToast($message, $type = 'info') {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = [
        'message' => $message,
        'type' => $type
    ];
}

function getToasts() {
    if (isset($_SESSION['toasts']) && !empty($_SESSION['toasts'])) {
        $toasts = $_SESSION['toasts'];
        $_SESSION['toasts'] = [];
        return $toasts;
    }
    return [];
}
?>

<div id="toast-container"></div>

<style>
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
    max-width: 400px;
}

.toast {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 320px;
    transform: translateX(450px);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    pointer-events: auto;
    position: relative;
    overflow: hidden;
}

.toast::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: currentColor;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-success {
    color: #10b981;
}

.toast-error {
    color: #ef4444;
}

.toast-warning {
    color: #f59e0b;
}

.toast-info {
    color: #3b82f6;
}

.toast-icon {
    font-size: 1.75rem;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,0.05);
}

.toast-success .toast-icon {
    background: rgba(16, 185, 129, 0.1);
}

.toast-error .toast-icon {
    background: rgba(239, 68, 68, 0.1);
}

.toast-warning .toast-icon {
    background: rgba(245, 158, 11, 0.1);
}

.toast-info .toast-icon {
    background: rgba(59, 130, 246, 0.1);
}

.toast-message {
    flex: 1;
    color: #1f2937;
    font-weight: 500;
    font-size: 0.95rem;
    line-height: 1.5;
}

.toast-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    flex-shrink: 0;
}

.toast-close:hover {
    background: rgba(0,0,0,0.05);
    color: #374151;
}

@media (max-width: 768px) {
    #toast-container {
        left: 16px;
        right: 16px;
        top: 80px;
        max-width: none;
    }
    
    .toast {
        min-width: auto;
        transform: translateY(-100px);
    }
    
    .toast.show {
        transform: translateY(0);
    }
}
</style>

<script>
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Show toasts from PHP session
<?php 
$toasts = getToasts();
if (!empty($toasts)): 
?>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($toasts as $toast): ?>
    setTimeout(() => {
        showToast(<?php echo json_encode($toast['message'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>, <?php echo json_encode($toast['type']); ?>);
    }, 100);
    <?php endforeach; ?>
});
<?php endif; ?>
</script>
