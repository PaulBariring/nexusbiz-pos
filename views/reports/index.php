<?php
use App\Helpers\Format;
require __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">

    <!-- Header & Range Filter -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-bar-chart-line me-2 text-primary"></i> Sales & Analytics Reports
            </h4>
            <small class="text-muted">Review revenue, gross profits, top selling items, and payment channels</small>
        </div>

        <!-- Date Range Filter Pills -->
        <div class="btn-group shadow-sm">
            <a href="/reports?range=today" class="btn btn-sm <?= ($filter === 'today') ? 'btn-primary active fw-semibold' : 'btn-outline-secondary' ?>">Today</a>
            <a href="/reports?range=week" class="btn btn-sm <?= ($filter === 'week') ? 'btn-primary active fw-semibold' : 'btn-outline-secondary' ?>">Past 7 Days</a>
            <a href="/reports?range=month" class="btn btn-sm <?= ($filter === 'month') ? 'btn-primary active fw-semibold' : 'btn-outline-secondary' ?>">This Month</a>
            <a href="/reports?range=all" class="btn btn-sm <?= ($filter === 'all') ? 'btn-primary active fw-semibold' : 'btn-outline-secondary' ?>">All Time</a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                <div class="text-muted small fw-medium">Total Sales Revenue</div>
                <h3 class="fw-bold mb-0 text-primary mt-1"><?= Format::currency($totalRevenue, $settings['currency_symbol'] ?? '$') ?></h3>
                <small class="text-muted" style="font-size: 0.75rem;">From <?= $salesCount ?> orders</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <div class="text-muted small fw-medium">Gross Profit</div>
                <h3 class="fw-bold mb-0 text-success mt-1"><?= Format::currency($totalProfit, $settings['currency_symbol'] ?? '$') ?></h3>
                <small class="text-muted" style="font-size: 0.75rem;">Revenue minus item cost</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                <div class="text-muted small fw-medium">Average Ticket Size</div>
                <h3 class="fw-bold mb-0 text-dark mt-1"><?= Format::currency($avgOrderValue, $settings['currency_symbol'] ?? '$') ?></h3>
                <small class="text-muted" style="font-size: 0.75rem;">Avg spend per sale</small>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <div class="text-muted small fw-medium">Discounts & Tax</div>
                <h4 class="fw-bold mb-0 text-dark mt-1">
                    <span class="text-danger">-<?= Format::currency($totalDiscounts, $settings['currency_symbol'] ?? '$') ?></span> / 
                    <span class="text-secondary">+<?= Format::currency($totalTax, $settings['currency_symbol'] ?? '$') ?></span>
                </h4>
                <small class="text-muted" style="font-size: 0.75rem;">Discounts given / Tax collected</small>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Payment Channels Breakdown (Cols: 4) -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-wallet2 me-1 text-primary"></i> Payment Methods</h6>
                
                <?php
                $cashPct = $totalRevenue > 0 ? round(($paymentMethods['cash'] / $totalRevenue) * 100) : 0;
                $cardPct = $totalRevenue > 0 ? round(($paymentMethods['card'] / $totalRevenue) * 100) : 0;
                $qrPct = $totalRevenue > 0 ? round(($paymentMethods['qr'] / $totalRevenue) * 100) : 0;
                $otherPct = $totalRevenue > 0 ? round(($paymentMethods['other'] / $totalRevenue) * 100) : 0;
                ?>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-medium"><i class="bi bi-cash-stack text-success me-1"></i> Cash</span>
                        <span><?= Format::currency($paymentMethods['cash'], $settings['currency_symbol'] ?? '$') ?> (<?= $cashPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 7px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $cashPct ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-medium"><i class="bi bi-credit-card text-primary me-1"></i> Card / POS</span>
                        <span><?= Format::currency($paymentMethods['card'], $settings['currency_symbol'] ?? '$') ?> (<?= $cardPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 7px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $cardPct ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-medium"><i class="bi bi-qr-code text-info me-1"></i> QR / Mobile Wallet</span>
                        <span><?= Format::currency($paymentMethods['qr'], $settings['currency_symbol'] ?? '$') ?> (<?= $qrPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 7px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $qrPct ?>%"></div>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-medium"><i class="bi bi-journal-bookmark text-secondary me-1"></i> Tab / Store Credit</span>
                        <span><?= Format::currency($paymentMethods['other'], $settings['currency_symbol'] ?? '$') ?> (<?= $otherPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 7px;">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: <?= $otherPct ?>%"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Top Selling Products (Cols: 8) -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-trophy me-1 text-warning"></i> Best-Selling Products</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No sales items recorded for this timeframe.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $idx => $tp): ?>
                                    <tr>
                                        <td class="fw-bold text-muted" style="width: 30px;"><?= $idx + 1 ?></td>
                                        <td class="fw-semibold text-dark"><?= Format::escape($tp['name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary px-2.5 py-1">
                                                <?= (int)$tp['qty'] ?> units
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            <?= Format::currency($tp['revenue'], $settings['currency_symbol'] ?? '$') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Invoices Log -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-receipt me-1 text-primary"></i> Recent Transactions & Invoices</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                    <tr>
                        <th>Date & Time</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Payment</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th class="text-end">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredSales)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No sales transactions found in this period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($filteredSales, 0, 30) as $sale): ?>
                            <tr>
                                <td><?= Format::date($sale['created_at']) ?></td>
                                <td class="font-monospace fw-bold text-primary"><?= Format::escape($sale['invoice_number']) ?></td>
                                <td><?= Format::escape($sale['customer_name'] ?? 'Walk-in') ?></td>
                                <td class="small text-muted"><?= Format::escape($sale['cashier_name'] ?? 'Cashier') ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase">
                                        <?= Format::escape($sale['payment_method']) ?>
                                    </span>
                                </td>
                                <td><?= count($sale['items'] ?? []) ?> items</td>
                                <td class="fw-bold text-dark"><?= Format::currency($sale['total_amount'], $settings['currency_symbol'] ?? '$') ?></td>
                                <td class="text-end">
                                    <a href="/receipt?id=<?= Format::escape($sale['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-printer me-1"></i> Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
