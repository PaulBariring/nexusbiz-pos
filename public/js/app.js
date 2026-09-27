// Global Application Utilities for NexusBiz POS & Inventory

window.showToast = function (message, type = 'info') {
    const toastEl = document.getElementById('appToast');
    const toastMsgEl = document.getElementById('toastMessage');
    if (!toastEl || !toastMsgEl) return;

    let icon = 'bi-info-circle-fill';
    let bgClass = 'bg-dark';

    if (type === 'success') {
        icon = 'bi-check-circle-fill text-success';
        bgClass = 'bg-dark';
    } else if (type === 'error' || type === 'danger') {
        icon = 'bi-exclamation-triangle-fill text-danger';
        bgClass = 'bg-danger text-white';
    } else if (type === 'warning') {
        icon = 'bi-exclamation-circle-fill text-warning';
        bgClass = 'bg-dark';
    }

    toastEl.className = `toast align-items-center ${bgClass} border-0 shadow-lg`;
    toastMsgEl.innerHTML = `<i class="bi ${icon} fs-5"></i> <span>${message}</span>`;

    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
};

// Keyboard Hotkey handler
document.addEventListener('keydown', function (e) {
    // F2: Focus Barcode / Search on POS
    if (e.key === 'F2') {
        e.preventDefault();
        const searchInput = document.getElementById('posProductSearch');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
});
