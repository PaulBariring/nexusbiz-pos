<?php
use App\Helpers\Format;
require __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-cash-stack me-2 text-primary"></i> Shift & Cash Drawer Management
            </h4>
            <small class="text-muted">Track register floats, cash-in/out drawer movements, and closing reconciliation (Z-Report)</small>
        </div>
    </div>

    <!-- Active Shift Status Panel -->
    <?php if ($activeShift): 
        $startingFloat = (float)($activeShift['starting_cash'] ?? 0);
        $cashSales = (float)($activeShift['cash_sales'] ?? 0);
        $cardSales = (float)($activeShift['card_sales'] ?? 0);
        $qrSales = (float)($activeShift['qr_sales'] ?? 0);
        $cashIn = (float)($activeShift['cash_in'] ?? 0);
        $cashOut = (float)($activeShift['cash_out'] ?? 0);
        $expectedCash = $startingFloat + $cashSales + $cashIn - $cashOut;
    ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4 border-top border-success border-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1">
                            <span class="status-dot bg-white"></span> REGISTER OPEN
                        </span>
                        <h5 class="fw-bold mb-0 text-dark"><?= Format::escape($activeShift['cashier_name']) ?></h5>
                    </div>
                    <small class="text-muted">
                        Opened on <strong><?= Format::date($activeShift['opened_at']) ?></strong>
                    </small>
                </div>

                <!-- Action Buttons: Cash In, Cash Out, Close Shift -->
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#cashInModal">
                        <i class="bi bi-box-arrow-in-down"></i>
                        <span>Cash In (Deposit)</span>
                    </button>
                    <button class="btn btn-outline-danger d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#cashOutModal">
                        <i class="bi bi-box-arrow-up"></i>
                        <span>Cash Out (Payout)</span>
                    </button>
                    <button class="btn btn-danger fw-semibold d-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                        <i class="bi bi-lock-fill"></i>
                        <span>Close Shift & Reconcile</span>
                    </button>
                </div>
            </div>

            <!-- Shift Metric Cards -->
            <div class="row g-3 text-center">
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">Starting Float</small>
                        <h5 class="fw-bold mb-0 text-dark"><?= Format::currency($startingFloat, $settings['currency_symbol'] ?? '$') ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">Cash Sales</small>
                        <h5 class="fw-bold mb-0 text-success"><?= Format::currency($cashSales, $settings['currency_symbol'] ?? '$') ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">Card / POS</small>
                        <h5 class="fw-bold mb-0 text-primary"><?= Format::currency($cardSales, $settings['currency_symbol'] ?? '$') ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">QR / Digital</small>
                        <h5 class="fw-bold mb-0 text-info"><?= Format::currency($qrSales, $settings['currency_symbol'] ?? '$') ?></h5>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block mb-1">Cash In / Out</small>
                        <h6 class="fw-bold mb-0">
                            <span class="text-success">+<?= Format::currency($cashIn, $settings['currency_symbol'] ?? '$') ?></span> / 
                            <span class="text-danger">-<?= Format::currency($cashOut, $settings['currency_symbol'] ?? '$') ?></span>
                        </h6>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle">
                        <small class="text-success-emphasis fw-semibold d-block mb-1">Expected In Drawer</small>
                        <h5 class="fw-bold mb-0 text-success-emphasis"><?= Format::currency($expectedCash, $settings['currency_symbol'] ?? '$') ?></h5>
                    </div>
                </div>
            </div>

            <!-- Drawer Movement Audit Log -->
            <?php if (!empty($movements)): ?>
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-2 small text-muted text-uppercase">Petty Cash & Payout Movements for this Shift</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Reason / Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $m): ?>
                                    <tr>
                                        <td><?= Format::date($m['created_at'], 'h:i A') ?></td>
                                        <td>
                                            <span class="badge <?= $m['type'] === 'in' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= strtoupper($m['type']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= Format::currency($m['amount'], $settings['currency_symbol'] ?? '$') ?></td>
                                        <td><?= Format::escape($m['reason']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php else: ?>
        <!-- Register Closed - Open Shift Card -->
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4 border-top border-warning border-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-circle">
                            <i class="bi bi-lock-fill fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Register Shift is Currently Closed</h5>
                            <p class="text-muted mb-0 small">Open a shift to start recording sales, drawer cash, and cashier tracking.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <form action="/shifts/open" method="POST" class="row g-2 align-items-end justify-content-md-end">
                        <div class="col-sm-5">
                            <label class="form-label small fw-semibold">Cashier Name</label>
                            <input type="text" name="cashier_name" class="form-control" required placeholder="e.g. Alex, Sarah" value="Main Cashier">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold">Starting Float</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Format::escape($settings['currency_symbol'] ?? '$') ?></span>
                                <input type="number" step="0.01" min="0" name="starting_cash" class="form-control fw-bold" required value="100.00">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-unlock-fill me-1"></i> Open
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Previous Shifts History Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
        <h5 class="fw-bold mb-3 text-dark">
            <i class="bi bi-clock-history me-1 text-primary"></i> Shift History & Z-Reports
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                    <tr>
                        <th>Opened At</th>
                        <th>Closed At</th>
                        <th>Cashier</th>
                        <th>Starting Float</th>
                        <th>Total Cash Sales</th>
                        <th>Card & Digital</th>
                        <th>Actual Counted</th>
                        <th>Over / Short</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentShifts)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No shifts logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentShifts as $s): 
                            $diff = (float)($s['difference'] ?? 0);
                        ?>
                            <tr>
                                <td><?= Format::date($s['opened_at']) ?></td>
                                <td><?= Format::date($s['closed_at']) ?></td>
                                <td class="fw-semibold"><?= Format::escape($s['cashier_name']) ?></td>
                                <td><?= Format::currency($s['starting_cash'], $settings['currency_symbol'] ?? '$') ?></td>
                                <td class="text-success fw-semibold"><?= Format::currency($s['cash_sales'] ?? 0, $settings['currency_symbol'] ?? '$') ?></td>
                                <td class="text-muted"><?= Format::currency(($s['card_sales'] ?? 0) + ($s['qr_sales'] ?? 0), $settings['currency_symbol'] ?? '$') ?></td>
                                <td class="fw-bold"><?= Format::currency($s['ending_cash'] ?? 0, $settings['currency_symbol'] ?? '$') ?></td>
                                <td>
                                    <?php if ($s['status'] === 'open'): ?>
                                        <span class="text-muted">—</span>
                                    <?php elseif ($diff == 0): ?>
                                        <span class="badge bg-success-subtle text-success">Balanced ($0.00)</span>
                                    <?php elseif ($diff > 0): ?>
                                        <span class="badge bg-primary-subtle text-primary">+<?= Format::currency($diff, $settings['currency_symbol'] ?? '$') ?> (Over)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= Format::currency($diff, $settings['currency_symbol'] ?? '$') ?> (Short)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $s['status'] === 'open' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= strtoupper($s['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL: Cash In (Deposit) -->
<?php if ($activeShift): ?>
<div class="modal fade" id="cashInModal" tabindex="-1" aria-labelledby="cashInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <form action="/shifts/movement" method="POST">
                <input type="hidden" name="shift_id" value="<?= Format::escape($activeShift['id']) ?>">
                <input type="hidden" name="type" value="in">

                <div class="modal-header bg-success text-white py-2.5">
                    <h6 class="modal-title fw-bold" id="cashInModalLabel"><i class="bi bi-box-arrow-in-down me-1"></i> Cash In (Deposit)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Amount to Add</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= Format::escape($settings['currency_symbol'] ?? '$') ?></span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Reason</label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Added change coins, Petty cash">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">Record Cash In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Cash Out (Payout) -->
<div class="modal fade" id="cashOutModal" tabindex="-1" aria-labelledby="cashOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <form action="/shifts/movement" method="POST">
                <input type="hidden" name="shift_id" value="<?= Format::escape($activeShift['id']) ?>">
                <input type="hidden" name="type" value="out">

                <div class="modal-header bg-danger text-white py-2.5">
                    <h6 class="modal-title fw-bold" id="cashOutModalLabel"><i class="bi bi-box-arrow-up me-1"></i> Cash Out (Payout)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Amount to Withdraw</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= Format::escape($settings['currency_symbol'] ?? '$') ?></span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Reason</label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Bought ice, Courier delivery, Safe drop">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">Record Cash Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Close Shift (Z-Report) -->
<div class="modal fade" id="closeShiftModal" tabindex="-1" aria-labelledby="closeShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="/shifts/close" method="POST">
                <input type="hidden" name="shift_id" value="<?= Format::escape($activeShift['id']) ?>">

                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="closeShiftModalLabel">
                        <i class="bi bi-file-earmark-spreadsheet me-2 text-warning"></i> End Shift & Reconcile (Z-Report)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Starting Float:</span>
                            <span class="fw-semibold text-dark"><?= Format::currency($startingFloat, $settings['currency_symbol'] ?? '$') ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Cash Sales:</span>
                            <span class="fw-semibold text-success">+<?= Format::currency($cashSales, $settings['currency_symbol'] ?? '$') ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Cash In / Out:</span>
                            <span class="fw-semibold">+<?= Format::currency($cashIn) ?> / -<?= Format::currency($cashOut) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold pt-2 border-top fs-6">
                            <span>Expected Cash in Drawer:</span>
                            <span class="text-primary"><?= Format::currency($expectedCash, $settings['currency_symbol'] ?? '$') ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Actual Counted Physical Cash <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><?= Format::escape($settings['currency_symbol'] ?? '$') ?></span>
                            <input type="number" step="0.01" min="0" name="actual_ending_cash" id="actualEndingCashInput" class="form-control fw-bold" required value="<?= number_format($expectedCash, 2, '.', '') ?>">
                        </div>
                        <small class="text-muted">Count all physical currency notes and coins currently in the cash drawer.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Closing Notes (Optional)</label>
                        <textarea name="closing_notes" class="form-control" rows="2" placeholder="e.g. Register balanced, drawer dropped in safe"></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">Confirm & Close Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
