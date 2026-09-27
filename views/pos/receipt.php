<?php
use App\Helpers\Format;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= Format::escape($sale['invoice_number'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-light py-4">

    <div class="container text-center mb-3 no-print">
        <a href="/pos" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back to POS
        </a>
        <button class="btn btn-sm btn-primary fw-bold" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Receipt
        </button>
    </div>

    <div class="receipt-paper printable-receipt-area">
        <div class="text-center mb-2">
            <h5 class="fw-bold mb-0"><?= Format::escape($settings['business_name'] ?? 'Nexus Store') ?></h5>
            <small class="d-block"><?= Format::escape($settings['tagline'] ?? '') ?></small>
            <small class="d-block"><?= Format::escape($settings['address'] ?? '') ?></small>
            <small class="d-block">Phone: <?= Format::escape($settings['phone'] ?? '') ?></small>
            <small class="d-block">Tax ID: <?= Format::escape($settings['tax_id'] ?? 'N/A') ?></small>
        </div>

        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between small">
            <span>Invoice: <?= Format::escape($sale['invoice_number']) ?></span>
            <span><?= Format::date($sale['created_at']) ?></span>
        </div>
        <div class="d-flex justify-content-between small">
            <span>Cashier: <?= Format::escape($sale['cashier_name'] ?? 'Cashier') ?></span>
            <span>Customer: <?= Format::escape($sale['customer_name'] ?? 'Walk-in') ?></span>
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
                <?php foreach (($sale['items'] ?? []) as $item): ?>
                    <tr>
                        <td style="padding: 3px 0;">
                            <?= Format::escape($item['product_name']) ?><br>
                            <small class="text-muted"><?= (int)$item['quantity'] ?> x <?= Format::currency($item['unit_price'], $settings['currency_symbol'] ?? '$') ?></small>
                        </td>
                        <td class="text-end" style="padding: 3px 0; vertical-align: top;">
                            <?= Format::currency($item['total'], $settings['currency_symbol'] ?? '$') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between small">
            <span>Subtotal:</span>
            <span><?= Format::currency($sale['subtotal'], $settings['currency_symbol'] ?? '$') ?></span>
        </div>

        <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
            <div class="d-flex justify-content-between small">
                <span>Discount:</span>
                <span>-<?= Format::currency($sale['discount_amount'], $settings['currency_symbol'] ?? '$') ?></span>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between small">
            <span><?= Format::escape($settings['tax_name'] ?? 'Tax') ?>:</span>
            <span><?= Format::currency($sale['tax_amount'], $settings['currency_symbol'] ?? '$') ?></span>
        </div>

        <div class="receipt-double-divider"></div>

        <div class="d-flex justify-content-between fw-bold fs-6">
            <span>TOTAL:</span>
            <span><?= Format::currency($sale['total_amount'], $settings['currency_symbol'] ?? '$') ?></span>
        </div>

        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between small">
            <span>Payment (<?= strtoupper(Format::escape($sale['payment_method'])) ?>):</span>
            <span><?= Format::currency($sale['amount_tendered'], $settings['currency_symbol'] ?? '$') ?></span>
        </div>
        <div class="d-flex justify-content-between small">
            <span>Change:</span>
            <span><?= Format::currency($sale['change_amount'], $settings['currency_symbol'] ?? '$') ?></span>
        </div>

        <div class="receipt-divider"></div>

        <div class="text-center small text-muted mt-2">
            <p class="mb-1"><?= Format::escape($settings['receipt_header'] ?? 'Thank you for your patronage!') ?></p>
            <small><?= Format::escape($settings['receipt_footer'] ?? 'Goods sold in good condition.') ?></small>
        </div>
    </div>

</body>
</html>
