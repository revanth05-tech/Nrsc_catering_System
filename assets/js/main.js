/**
 * NRSC Catering System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileSidebar();
    initAlertDismiss();
    initConfirmActions();
});

function initMobileSidebar() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        });
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    }
}

function initAlertDismiss() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

function initConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function calculateOrderTotal() {
    let total = 0;
    document.querySelectorAll('.selected-item').forEach(item => {
        const price = parseFloat(item.dataset.price) || 0;
        const qty = parseInt(item.querySelector('input[type="number"]').value) || 0;
        total += price * qty;
        const subtotal = item.querySelector('.selected-item-price');
        if (subtotal) subtotal.textContent = formatCurrency(price * qty);
    });
    
    const display = document.querySelector('.order-total-value');
    if (display) display.textContent = formatCurrency(total);
    
    const input = document.querySelector('input[name="total_amount"]');
    if (input) input.value = total;
    return total;
}

function addItemToOrder(id, name, price) {
    const container = document.querySelector('.selected-items');
    if (!container || document.querySelector(`.selected-item[data-id="${id}"]`)) return;
    
    container.insertAdjacentHTML('beforeend', `
        <div class="selected-item" data-id="${id}" data-price="${price}">
            <span class="selected-item-name">${name}</span>
            <input type="hidden" name="items[]" value="${id}">
            <input type="number" name="quantities[]" value="1" min="1" onchange="calculateOrderTotal()">
            <span class="selected-item-price">${formatCurrency(price)}</span>
            <button type="button" class="remove-item-btn" onclick="this.closest('.selected-item').remove();calculateOrderTotal();">✕</button>
        </div>
    `);
    calculateOrderTotal();
}

window.formatCurrency = formatCurrency;
window.calculateOrderTotal = calculateOrderTotal;
window.addItemToOrder = addItemToOrder;
