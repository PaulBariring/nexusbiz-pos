// Point of Sale (POS) Engine for NexusBiz

(function () {
    const currency = window.POS_CONFIG?.currency || '$';
    const taxRate = parseFloat(window.POS_CONFIG?.taxRate || 0);
    const taxInclusive = Boolean(window.POS_CONFIG?.taxInclusive);

    let cart = [];
    let discount = { type: 'flat', amount: 0 };
    let parkedId = null;

    // DOM Elements
    const searchInput = document.getElementById('posProductSearch');
    const categoryTabs = document.querySelectorAll('.cat-pill-btn');
    const productGrid = document.getElementById('posProductGrid');
    const cartItemsList = document.getElementById('cartItemsList');
    const emptyCartMsg = document.getElementById('emptyCartMsg');
    const cartSummary = document.getElementById('cartSummary');
    const subtotalEl = document.getElementById('cartSubtotal');
    const taxEl = document.getElementById('cartTax');
    const discountEl = document.getElementById('cartDiscount');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const payBtn = document.getElementById('cartPayBtn');
    const holdBtn = document.getElementById('cartHoldBtn');
    const clearBtn = document.getElementById('cartClearBtn');

    // Payment Modal Elements
    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = paymentModalEl ? new bootstrap.Modal(paymentModalEl) : null;
    const modalTotalDue = document.getElementById('modalTotalDue');
    const amountTenderedInput = document.getElementById('amountTendered');
    const changeDueEl = document.getElementById('changeDue');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

    // Receipt Modal Elements
    const receiptModalEl = document.getElementById('receiptModal');
    const receiptModal = receiptModalEl ? new bootstrap.Modal(receiptModalEl) : null;
    const receiptContentEl = document.getElementById('receiptContent');

    // Parked Orders Modal
    const parkedModalEl = document.getElementById('parkedOrdersModal');
    const parkedModal = parkedModalEl ? new bootstrap.Modal(parkedModalEl) : null;

    // Format helper
    function fmt(num) {
        return currency + ' ' + (parseFloat(num) || 0).toFixed(2);
    }

    // Add item to cart
    window.addToCart = function (product) {
        const stock = parseInt(product.stock_quantity || 0);
        const existing = cart.find(item => item.product_id === product.id);

        if (existing) {
            if (existing.quantity >= stock && stock > 0) {
                window.showToast('Maximum available stock reached for ' + product.name, 'warning');
            }
            existing.quantity += 1;
        } else {
            if (stock <= 0) {
                window.showToast('Item is out of stock!', 'error');
                return;
            }
            cart.push({
                product_id: product.id,
                name: product.name,
                barcode: product.barcode || '',
                unit_price: parseFloat(product.selling_price || 0),
                quantity: 1,
                max_stock: stock
            });
        }
        renderCart();
    };

    // Update item quantity
    window.updateCartQty = function (productId, delta) {
        const item = cart.find(i => i.product_id === productId);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.product_id !== productId);
        } else if (item.max_stock > 0 && item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
            window.showToast('Max available stock reached (' + item.max_stock + ')', 'warning');
        }
        renderCart();
    };

    // Remove single item
    window.removeFromCart = function (productId) {
        cart = cart.filter(i => i.product_id !== productId);
        renderCart();
    };

    // Clear entire cart
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (cart.length === 0) return;
            if (confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                discount = { type: 'flat', amount: 0 };
                parkedId = null;
                renderCart();
            }
        });
    }

    // Calculate totals
    function calculateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.unit_price * item.quantity;
        });

        let discountVal = 0;
        if (discount.type === 'percent') {
            discountVal = subtotal * (discount.amount / 100);
        } else {
            discountVal = Math.min(subtotal, discount.amount);
        }

        const discountedSubtotal = Math.max(0, subtotal - discountVal);

        let taxVal = 0;
        let grandTotal = 0;

        if (taxInclusive) {
            taxVal = discountedSubtotal - (discountedSubtotal / (1 + (taxRate / 100)));
            grandTotal = discountedSubtotal;
        } else {
            taxVal = discountedSubtotal * (taxRate / 100);
            grandTotal = discountedSubtotal + taxVal;
        }

        return {
            subtotal: subtotal,
            discount: discountVal,
            tax: taxVal,
            total: grandTotal
        };
    }

    // Render cart items & summary
    function renderCart() {
        if (!cartItemsList) return;

        if (cart.length === 0) {
            cartItemsList.innerHTML = '';
            if (emptyCartMsg) emptyCartMsg.classList.remove('d-none');
            if (cartSummary) cartSummary.classList.add('d-none');
            if (payBtn) payBtn.disabled = true;
            if (holdBtn) holdBtn.disabled = true;
            return;
        }

        if (emptyCartMsg) emptyCartMsg.classList.add('d-none');
        if (cartSummary) cartSummary.classList.remove('d-none');
        if (payBtn) payBtn.disabled = false;
        if (holdBtn) holdBtn.disabled = false;

        const totals = calculateTotals();

        cartItemsList.innerHTML = cart.map(item => {
            const lineTotal = item.unit_price * item.quantity;
            return `
                <div class="cart-item-row p-2.5 d-flex align-items-center justify-content-between">
                    <div class="me-2" style="max-width: 55%;">
                        <div class="fw-semibold text-truncate text-dark mb-0.5" style="font-size: 0.9rem;">${item.name}</div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            ${fmt(item.unit_price)} × ${item.quantity} = <strong class="text-dark">${fmt(lineTotal)}</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <div class="btn-group btn-group-sm border rounded">
                            <button class="btn btn-sm btn-light px-2" onclick="window.updateCartQty('${item.product_id}', -1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="btn btn-sm btn-light bg-white px-2.5 fw-bold">${item.quantity}</span>
                            <button class="btn btn-sm btn-light px-2" onclick="window.updateCartQty('${item.product_id}', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-danger border-0 p-1" onclick="window.removeFromCart('${item.product_id}')" title="Remove">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        if (subtotalEl) subtotalEl.textContent = fmt(totals.subtotal);
        if (taxEl) taxEl.textContent = fmt(totals.tax);
        if (discountEl) discountEl.textContent = '- ' + fmt(totals.discount);
        if (grandTotalEl) grandTotalEl.textContent = fmt(totals.total);
    }

    // Barcode & Product Live Search
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            performSearch(searchInput.value);
        }, 200));

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = searchInput.value.trim();
                if (!code) return;

                // First check if barcode exactly matches
                fetch(`/api/barcode?code=${encodeURIComponent(code)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.product) {
                            window.addToCart(data.product);
                            window.showToast(`Added ${data.product.name} to cart`, 'success');
                            searchInput.value = '';
                            performSearch('');
                        } else {
                            // If barcode not exact, search name
                            performSearch(code);
                        }
                    })
                    .catch(() => performSearch(code));
            }
        });
    }

    // Category Tabs filter
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            categoryTabs.forEach(t => t.classList.remove('active', 'btn-primary'));
            categoryTabs.forEach(t => t.classList.add('btn-outline-secondary'));
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-primary');

            const categoryId = this.dataset.category || '';
            filterByCategory(categoryId);
        });
    });

    function filterByCategory(catId) {
        const cards = productGrid.querySelectorAll('.product-card-col');
        cards.forEach(card => {
            if (!catId || card.dataset.category === catId) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    }

    function performSearch(query) {
        const q = query.toLowerCase().trim();
        const cards = productGrid.querySelectorAll('.product-card-col');

        cards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const barcode = (card.dataset.barcode || '').toLowerCase();
            const sku = (card.dataset.sku || '').toLowerCase();

            if (!q || name.includes(q) || barcode.includes(q) || sku.includes(q)) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Discount Modal Trigger
    const applyDiscountBtn = document.getElementById('applyDiscountModalBtn');
    if (applyDiscountBtn) {
        applyDiscountBtn.addEventListener('click', function () {
            const amountInput = document.getElementById('discountInputAmount');
            const typeInput = document.querySelector('input[name="discount_type"]:checked');
            const val = parseFloat(amountInput?.value || 0);

            discount = {
                type: typeInput ? typeInput.value : 'flat',
                amount: isNaN(val) ? 0 : val
            };

            const modal = bootstrap.Modal.getInstance(document.getElementById('discountModal'));
            if (modal) modal.hide();
            renderCart();
        });
    }

    // Hold / Park Sale
    if (holdBtn) {
        holdBtn.addEventListener('click', function () {
            if (cart.length === 0) return;
            const note = prompt('Enter a label or customer name for this held order:', 'Order #' + Math.floor(1000 + Math.random() * 9000));
            if (note === null) return; // cancelled

            const totals = calculateTotals();
            fetch('/api/park', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_note: note,
                    items: cart,
                    subtotal: totals.total
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.showToast('Order parked successfully!', 'success');
                        cart = [];
                        discount = { type: 'flat', amount: 0 };
                        parkedId = null;
                        renderCart();
                        updateParkedCountBadge();
                    } else {
                        window.showToast(data.message, 'error');
                    }
                });
        });
    }

    // View Parked Orders
    window.loadParkedOrders = function () {
        fetch('/api/parked')
            .then(res => res.json())
            .then(orders => {
                const list = document.getElementById('parkedOrdersList');
                if (!list) return;

                if (!orders || orders.length === 0) {
                    list.innerHTML = `<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-2 d-block mb-1"></i> No parked orders.</div>`;
                    return;
                }

                list.innerHTML = orders.map(o => `
                    <div class="card mb-2 shadow-sm border">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 fw-bold">${o.customer_note || 'Held Order'}</h6>
                                <div class="text-muted small">
                                    <span>${(o.items || []).length} items</span> • 
                                    <strong class="text-primary">${fmt(o.subtotal || 0)}</strong> • 
                                    <span>${new Date(o.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                </div>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-primary" onclick="window.resumeParked('${o.id}')">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Resume
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="window.discardParked('${o.id}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            });
    };

    window.resumeParked = function (id) {
        if (cart.length > 0 && !confirm('Resuming this order will overwrite current cart. Continue?')) {
            return;
        }

        fetch(`/api/parked/resume?id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.parked) {
                    cart = data.parked.items || [];
                    parkedId = id;
                    renderCart();
                    if (parkedModal) parkedModal.hide();
                    updateParkedCountBadge();
                    window.showToast('Order resumed into cart!', 'success');
                }
            });
    };

    window.discardParked = function (id) {
        if (confirm('Discard this held order?')) {
            fetch(`/api/parked/delete?id=${encodeURIComponent(id)}`)
                .then(res => res.json())
                .then(() => {
                    window.loadParkedOrders();
                    updateParkedCountBadge();
                });
        }
    };

    function updateParkedCountBadge() {
        fetch('/api/parked')
            .then(res => res.json())
            .then(orders => {
                const badge = document.getElementById('parkedCountBadge');
                if (badge) {
                    const count = orders ? orders.length : 0;
                    badge.textContent = count;
                    badge.classList.toggle('d-none', count === 0);
                }
            });
    }

    // Checkout Flow
    if (payBtn) {
        payBtn.addEventListener('click', function () {
            if (cart.length === 0) return;
            const totals = calculateTotals();

            if (modalTotalDue) modalTotalDue.textContent = fmt(totals.total);
            if (amountTenderedInput) {
                amountTenderedInput.value = totals.total.toFixed(2);
                amountTenderedInput.select();
            }
            updateChangeCalculation(totals.total);

            if (paymentModal) paymentModal.show();
        });
    }

    // Tendered Cash Input & Change Calculation
    if (amountTenderedInput) {
        amountTenderedInput.addEventListener('input', function () {
            const totals = calculateTotals();
            updateChangeCalculation(totals.total);
        });
    }

    function updateChangeCalculation(totalDue) {
        if (!amountTenderedInput || !changeDueEl) return;
        const tendered = parseFloat(amountTenderedInput.value) || 0;
        const change = Math.max(0, tendered - totalDue);
        changeDueEl.textContent = fmt(change);

        if (tendered < totalDue) {
            changeDueEl.className = 'fw-bold text-danger';
            changeDueEl.textContent = 'Insufficient (' + fmt(tendered) + ')';
        } else {
            changeDueEl.className = 'fw-bold text-success';
        }
    }

    // Quick cash buttons ($10, $20, $50, $100, exact)
    window.setQuickCash = function (amount) {
        const totals = calculateTotals();
        let val = amount;
        if (amount === 'exact') {
            val = totals.total;
        }
        if (amountTenderedInput) {
            amountTenderedInput.value = (parseFloat(val) || 0).toFixed(2);
            updateChangeCalculation(totals.total);
        }
    };

    // Confirm Payment
    if (confirmPaymentBtn) {
        confirmPaymentBtn.addEventListener('click', function () {
            const totals = calculateTotals();
            const tendered = parseFloat(amountTenderedInput?.value || totals.total);

            let selectedMethod = 'cash';
            paymentMethodRadios.forEach(r => {
                if (r.checked) selectedMethod = r.value;
            });

            if (selectedMethod === 'cash' && tendered < totals.total) {
                alert('Tendered cash is less than total amount due.');
                return;
            }

            confirmPaymentBtn.disabled = true;
            confirmPaymentBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

            const payload = {
                items: cart,
                discount_amount: discount.amount,
                discount_type: discount.type,
                payment_method: selectedMethod,
                amount_tendered: tendered,
                customer_name: (document.getElementById('posCustomerName')?.value || 'Walk-in Customer'),
                parked_id: parkedId
            };

            fetch('/api/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    confirmPaymentBtn.disabled = false;
                    confirmPaymentBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Complete & Print Receipt';

                    if (data.success && data.sale) {
                        if (paymentModal) paymentModal.hide();
                        renderReceiptModal(data.sale);

                        // Reset Cart
                        cart = [];
                        discount = { type: 'flat', amount: 0 };
                        parkedId = null;
                        renderCart();
                        updateParkedCountBadge();

                        window.showToast('Transaction completed successfully!', 'success');
                    } else {
                        alert(data.message || 'Payment processing failed.');
                    }
                })
                .catch(err => {
                    confirmPaymentBtn.disabled = false;
                    confirmPaymentBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Complete & Print Receipt';
                    alert('Network error while processing payment.');
                });
        });
    }

    // Render receipt inside modal for printing
    function renderReceiptModal(sale) {
        if (!receiptContentEl) return;

        const dateStr = new Date(sale.created_at).toLocaleString();
        const settings = window.POS_CONFIG || {};

        let itemsHtml = (sale.items || []).map(item => `
            <tr>
                <td style="padding: 3px 0;">
                    ${item.product_name}<br>
                    <small class="text-muted">${item.quantity} x ${fmt(item.unit_price)}</small>
                </td>
                <td class="text-end" style="padding: 3px 0; vertical-align: top;">
                    ${fmt(item.total)}
                </td>
            </tr>
        `).join('');

        receiptContentEl.innerHTML = `
            <div class="receipt-paper printable-receipt-area">
                <div class="text-center mb-2">
                    <h5 class="fw-bold mb-0">${settings.businessName || 'Nexus Store'}</h5>
                    <small class="d-block">${settings.tagline || ''}</small>
                    <small class="d-block">${settings.address || ''}</small>
                    <small class="d-block">Phone: ${settings.phone || ''}</small>
                    <small class="d-block">Tax ID: ${settings.taxId || 'N/A'}</small>
                </div>
                <div class="receipt-divider"></div>
                <div class="d-flex justify-content-between small">
                    <span>Invoice: ${sale.invoice_number}</span>
                    <span>${dateStr}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Cashier: ${sale.cashier_name || 'Cashier'}</span>
                    <span>Customer: ${sale.customer_name || 'Walk-in'}</span>
                </div>
                <div class="receipt-divider"></div>
                <table class="w-100 small mb-2">
                    <thead>
                        <tr class="border-bottom border-dark">
                            <th class="text-start pb-1">Item</th>
                            <th class="text-end pb-1">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>
                <div class="receipt-divider"></div>
                <div class="d-flex justify-content-between small">
                    <span>Subtotal:</span>
                    <span>${fmt(sale.subtotal)}</span>
                </div>
                ${sale.discount_amount > 0 ? `
                <div class="d-flex justify-content-between small">
                    <span>Discount:</span>
                    <span>-${fmt(sale.discount_amount)}</span>
                </div>` : ''}
                <div class="d-flex justify-content-between small">
                    <span>${settings.taxName || 'Tax'}:</span>
                    <span>${fmt(sale.tax_amount)}</span>
                </div>
                <div class="receipt-double-divider"></div>
                <div class="d-flex justify-content-between fw-bold fs-6">
                    <span>TOTAL:</span>
                    <span>${fmt(sale.total_amount)}</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="d-flex justify-content-between small">
                    <span>Payment (${sale.payment_method.toUpperCase()}):</span>
                    <span>${fmt(sale.amount_tendered)}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Change:</span>
                    <span>${fmt(sale.change_amount)}</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="text-center small text-muted mt-2">
                    <p class="mb-1">${settings.receiptHeader || 'Thank you for your patronage!'}</p>
                    <small>${settings.receiptFooter || 'Goods sold in good condition.'}</small>
                </div>
            </div>
        `;

        if (receiptModal) receiptModal.show();
    }

    // Print receipt
    window.printReceipt = function () {
        window.print();
    };

    // Initialize
    renderCart();
    updateParkedCountBadge();
})();
