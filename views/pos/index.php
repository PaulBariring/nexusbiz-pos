<?php
use App\Helpers\Format;
require __DIR__ . '/../layouts/header.php';
?>

<!-- Pass Settings to POS JavaScript Engine -->
<script>
    window.POS_CONFIG = {
        currency: <?= json_encode($settings['currency_symbol'] ?? '$') ?>,
        taxRate: <?= json_encode((float)($settings['tax_rate'] ?? 0)) ?>,
        taxInclusive: <?= json_encode((bool)($settings['tax_inclusive'] ?? false)) ?>,
        taxName: <?= json_encode($settings['tax_name'] ?? 'Sales Tax') ?>,
        businessName: <?= json_encode($settings['business_name'] ?? 'Nexus Store') ?>,
        tagline: <?= json_encode($settings['tagline'] ?? '') ?>,
        address: <?= json_encode($settings['address'] ?? '') ?>,
        phone: <?= json_encode($settings['phone'] ?? '') ?>,
        taxId: <?= json_encode($settings['tax_id'] ?? '') ?>,
        receiptHeader: <?= json_encode($settings['receipt_header'] ?? '') ?>,
        receiptFooter: <?= json_encode($settings['receipt_footer'] ?? '') ?>
    };
</script>

<div class="row g-3 pos-container">

    <!-- LEFT: Product Catalog & Search (Cols: 8 on desktop, 12 on mobile) -->
    <div class="col-lg-8 col-xl-8 pos-catalog-panel">
        <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column p-3">
            
            <!-- Search & Barcode Input Bar -->
            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-8 col-12">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="bi bi-upc-scan fs-4"></i>
                        </span>
                        <input type="text" id="posProductSearch" class="form-control border-start-0 ps-0 fs-6" 
                               placeholder="Scan barcode or search product by name, SKU... [F2]" autofocus autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0" type="button" onclick="document.getElementById('posProductSearch').value=''; document.getElementById('posProductSearch').dispatchEvent(new Event('input'));">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Parked Orders & New Product Quick Link -->
                <div class="col-md-4 col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-dark d-flex align-items-center gap-1.5 px-3 position-relative" data-bs-toggle="modal" data-bs-target="#parkedOrdersModal" onclick="window.loadParkedOrders()">
                        <i class="bi bi-pause-circle fs-5"></i>
                        <span class="d-none d-sm-inline">Parked Orders</span>
                        <span id="parkedCountBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                    </button>
                    <a href="/inventory" class="btn btn-outline-primary d-flex align-items-center gap-1 px-3" title="Manage Inventory">
                        <i class="bi bi-plus-circle fs-5"></i>
                        <span class="d-none d-sm-inline">Items</span>
                    </a>
                </div>
            </div>

            <!-- Category Pills Filter Bar -->
            <div class="category-scroll-container mb-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary active cat-pill-btn rounded-pill px-3 py-1.5 fw-medium" data-category="">
                    <i class="bi bi-grid-fill me-1"></i> All Items
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button class="btn btn-sm btn-outline-secondary cat-pill-btn rounded-pill px-3 py-1.5 fw-medium" data-category="<?= Format::escape($cat['id']) ?>">
                        <i class="bi <?= Format::escape($cat['icon'] ?? 'bi-tag') ?> me-1"></i>
                        <?= Format::escape($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Products Grid Container -->
            <div class="product-grid-container">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-xl-4 g-2.5" id="posProductGrid">
                    <?php foreach ($products as $p): 
                        $stock = (int)($p['stock_quantity'] ?? 0);
                        $minAlert = (int)($p['min_stock_alert'] ?? 5);
                        $isLowStock = $stock > 0 && $stock <= $minAlert;
                        $isOut = $stock <= 0;
                    ?>
                        <div class="col product-card-col" 
                             data-id="<?= Format::escape($p['id']) ?>"
                             data-name="<?= Format::escape($p['name']) ?>"
                             data-barcode="<?= Format::escape($p['barcode'] ?? '') ?>"
                             data-sku="<?= Format::escape($p['sku'] ?? '') ?>"
                             data-category="<?= Format::escape($p['category_id'] ?? '') ?>">
                            
                            <div class="card h-100 border product-pos-card shadow-sm p-2.5 <?= $isOut ? 'out-of-stock bg-light' : 'bg-white' ?>"
                                 onclick='window.addToCart(<?= json_encode($p) ?>)'>
                                
                                <div class="d-flex justify-content-between align-items-start mb-1.5">
                                    <!-- Stock Badge -->
                                    <?php if ($isOut): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-1.5 text-xs">Out of Stock</span>
                                    <?php elseif ($isLowStock): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-1.5 text-xs"><?= $stock ?> left</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-1.5 text-xs"><?= $stock ?> in stock</span>
                                    <?php endif; ?>

                                    <!-- Barcode indicator -->
                                    <small class="text-muted font-monospace" style="font-size: 0.65rem;">
                                        <?= Format::escape($p['sku'] ?: $p['barcode']) ?>
                                    </small>
                                </div>

                                <div class="product-name fw-semibold text-dark mb-1 text-truncate-2" style="font-size: 0.88rem; min-height: 2.4em; line-height: 1.25;">
                                    <?= Format::escape($p['name']) ?>
                                </div>

                                <!-- Dynamic attributes quick preview if any -->
                                <?php if (!empty($p['dynamic_attributes']) && is_array($p['dynamic_attributes'])): ?>
                                    <div class="mb-2 text-muted text-truncate" style="font-size: 0.72rem;">
                                        <?php 
                                        $attrStrings = [];
                                        foreach (array_slice($p['dynamic_attributes'], 0, 2) as $k => $v) {
                                            if ($v) $attrStrings[] = Format::escape($k) . ': ' . Format::escape($v);
                                        }
                                        echo implode(' • ', $attrStrings);
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto d-flex justify-content-between align-items-center pt-1 border-top">
                                    <span class="fs-6 fw-bold text-primary">
                                        <?= Format::currency($p['selling_price'], $settings['currency_symbol'] ?? '$') ?>
                                    </span>
                                    <button class="btn btn-sm btn-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- RIGHT: POS Cart & Checkout Panel (Cols: 4 on desktop, 12 on mobile) -->
    <div class="col-lg-4 col-xl-4 pos-cart-panel">
        <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column bg-white">
            
            <!-- Cart Header: Customer Name & Actions -->
            <div class="card-header bg-white border-bottom p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 fs-5 text-primary"></i>
                        <h6 class="mb-0 fw-bold">Current Order</h6>
                    </div>
                    <button class="btn btn-sm btn-outline-danger border-0" id="cartClearBtn" title="Clear Cart">
                        <i class="bi bi-trash3 me-1"></i> Clear
                    </button>
                </div>
                
                <!-- Customer Name input -->
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" id="posCustomerName" class="form-control border-start-0" placeholder="Walk-in Customer" value="Walk-in Customer">
                </div>
            </div>

            <!-- Cart Items Scrollable List -->
            <div class="card-body p-2 d-flex flex-column flex-grow-1 overflow-hidden">
                <div id="cartItemsList" class="cart-items-container flex-grow-1"></div>

                <!-- Empty Cart State -->
                <div id="emptyCartMsg" class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                    <div class="p-3 bg-light rounded-circle mb-2 text-secondary">
                        <i class="bi bi-cart-x fs-1"></i>
                    </div>
                    <h6 class="fw-semibold text-secondary">Cart is empty</h6>
                    <small>Click products or scan barcodes to begin order</small>
                </div>
            </div>

            <!-- Cart Summary & Action Buttons (Sticky at bottom) -->
            <div id="cartSummary" class="card-footer bg-light border-top p-3 d-none">
                
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Subtotal:</span>
                    <span id="cartSubtotal" class="fw-semibold text-dark">$ 0.00</span>
                </div>

                <div class="d-flex justify-content-between text-muted small mb-1 align-items-center">
                    <div class="d-flex align-items-center gap-1">
                        <span>Discount:</span>
                        <button class="btn btn-link p-0 text-decoration-none text-xs text-primary" data-bs-toggle="modal" data-bs-target="#discountModal">
                            [Edit]
                        </button>
                    </div>
                    <span id="cartDiscount" class="text-danger fw-semibold">- $ 0.00</span>
                </div>

                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span><?= Format::escape($settings['tax_name'] ?? 'Tax') ?> (<?= (float)($settings['tax_rate'] ?? 0) ?>%<?= ($settings['tax_inclusive'] ?? false) ? ' incl.' : '' ?>):</span>
                    <span id="cartTax" class="fw-semibold text-dark">$ 0.00</span>
                </div>

                <div class="d-flex justify-content-between align-items-baseline pt-2 border-top border-2 mb-3">
                    <span class="fs-5 fw-bold text-dark">Total Due:</span>
                    <span id="cartGrandTotal" class="fs-3 fw-bold text-primary">$ 0.00</span>
                </div>

                <!-- POS Action Buttons -->
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-warning w-50 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-1.5" id="cartHoldBtn">
                        <i class="bi bi-pause-circle"></i>
                        <span>Hold Order</span>
                    </button>
                    <button class="btn btn-success w-100 py-2.5 fw-bold fs-6 shadow d-flex align-items-center justify-content-center gap-1.5" id="cartPayBtn">
                        <i class="bi bi-credit-card-2-front fs-5"></i>
                        <span>Pay Now</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- MODAL: Payment & Checkout -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="paymentModalLabel">
                    <i class="bi bi-cash-coin me-2 text-warning"></i> Complete Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Total Amount Banner -->
                <div class="text-center p-3 bg-light rounded-3 mb-3 border">
                    <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Amount Payable</small>
                    <div id="modalTotalDue" class="display-5 fw-bold text-primary">$ 0.00</div>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted text-uppercase text-xs">Payment Method</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked>
                            <label class="btn btn-outline-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" for="pay_cash">
                                <i class="bi bi-cash-stack fs-5"></i> Cash
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="card">
                            <label class="btn btn-outline-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" for="pay_card">
                                <i class="bi bi-credit-card fs-5"></i> Card / POS
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_qr" value="qr">
                            <label class="btn btn-outline-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" for="pay_qr">
                                <i class="bi bi-qr-code-scan fs-5"></i> QR / Wallet
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_credit" value="credit">
                            <label class="btn btn-outline-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2" for="pay_credit">
                                <i class="bi bi-journal-bookmark fs-5"></i> Store Tab
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Quick Cash Presets -->
                <div class="mb-3" id="quickCashSection">
                    <label class="form-label fw-bold text-muted text-uppercase text-xs">Quick Cash Shortcuts</label>
                    <div class="d-flex gap-1.5 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash('exact')">Exact</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash(10)">$10</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash(20)">$20</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash(50)">$50</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash(100)">$100</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn px-2.5 py-1" onclick="window.setQuickCash(500)">$500</button>
                    </div>
                </div>

                <!-- Cash Tendered Input & Live Change Display -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold text-sm">Tendered / Received</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= Format::escape($settings['currency_symbol'] ?? '$') ?></span>
                            <input type="number" step="0.01" id="amountTendered" class="form-control form-control-lg fw-bold text-end" value="0.00">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold text-sm">Change to Return</label>
                        <div class="p-2 border rounded-3 bg-light text-end">
                            <div id="changeDue" class="fs-4 fw-bold text-success">$ 0.00</div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmPaymentBtn" class="btn btn-success fw-bold px-4 py-2.5 d-flex align-items-center gap-1.5 shadow">
                    <i class="bi bi-check2-circle fs-5"></i> Complete & Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Order Discount -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2.5">
                <h6 class="modal-title fw-bold" id="discountModalLabel"><i class="bi bi-percent me-1 text-primary"></i> Apply Order Discount</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label text-sm fw-semibold">Discount Type</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discount_type" id="disc_flat" value="flat" checked>
                            <label class="form-check-label text-sm" for="disc_flat">Fixed Amount (<?= Format::escape($settings['currency_symbol'] ?? '$') ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discount_type" id="disc_pct" value="percent">
                            <label class="form-check-label text-sm" for="disc_pct">Percent (%)</label>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-sm fw-semibold">Amount / Percentage</label>
                    <input type="number" step="0.01" min="0" id="discountInputAmount" class="form-control" placeholder="0.00" value="0.00">
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold" id="applyDiscountModalBtn">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Parked Orders List -->
<div class="modal fade" id="parkedOrdersModal" tabindex="-1" aria-labelledby="parkedOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2.5 bg-light">
                <h6 class="modal-title fw-bold" id="parkedOrdersModalLabel"><i class="bi bi-pause-circle me-1 text-warning"></i> Parked / Held Orders</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="parkedOrdersList"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Thermal Receipt Preview & Print -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 no-print">
                <h6 class="modal-title fw-bold" id="receiptModalLabel"><i class="bi bi-receipt me-1"></i> Customer Receipt</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light overflow-auto" style="max-height: 75vh;">
                <div id="receiptContent"></div>
            </div>
            <div class="modal-footer bg-white p-2.5 justify-content-between no-print">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Done</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1 shadow-sm" onclick="window.printReceipt()">
                    <i class="bi bi-printer-fill"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include POS Specific Script -->
<script src="/js/pos.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
