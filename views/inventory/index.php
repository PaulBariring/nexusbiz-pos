<?php
use App\Helpers\Format;
require __DIR__ . '/../layouts/header.php';

$activeTab = $_GET['tab'] ?? 'products';
$dynamicFields = $settings['dynamic_fields'] ?? [];
?>

<div class="container-fluid">

    <!-- Header & Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-box-seam me-2 text-primary"></i> Inventory Management
            </h4>
            <small class="text-muted">Track items, variants, dynamic business attributes, and stock audit logs</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#stockAdjustModal">
                <i class="bi bi-arrow-left-right"></i>
                <span>Adjust Stock</span>
            </button>
            <button class="btn btn-primary d-flex align-items-center gap-1.5 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="window.resetProductForm()">
                <i class="bi bi-plus-lg"></i>
                <span>Add Product</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-medium">Total Products</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($products) ?></h4>
                    </div>
                    <div class="p-2.5 bg-primary-subtle text-primary rounded-3">
                        <i class="bi bi-grid fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-medium">Units in Stock</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalStockCount) ?></h4>
                    </div>
                    <div class="p-2.5 bg-success-subtle text-success rounded-3">
                        <i class="bi bi-boxes fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-medium">Inventory Valuation</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= Format::currency($totalStockValue, $settings['currency_symbol'] ?? '$') ?></h4>
                    </div>
                    <div class="p-2.5 bg-info-subtle text-info rounded-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-medium">Low Stock Items</div>
                        <h4 class="fw-bold mb-0 <?= $lowStockCount > 0 ? 'text-warning-emphasis' : 'text-dark' ?>"><?= $lowStockCount ?></h4>
                    </div>
                    <div class="p-2.5 bg-warning-subtle text-warning-emphasis rounded-3">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-3 border-bottom">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'products' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/inventory?tab=products">
                <i class="bi bi-table me-1"></i> Products Catalog (<?= count($products) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'adjustments' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/inventory?tab=adjustments">
                <i class="bi bi-clock-history me-1"></i> Stock Audit Log
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'categories' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/inventory?tab=categories">
                <i class="bi bi-tags me-1"></i> Categories (<?= count($categories) ?>)
            </a>
        </li>
    </ul>

    <!-- TAB 1: Products Catalog -->
    <?php if ($activeTab === 'products'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
            
            <!-- Filters & Search -->
            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-5 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="inventoryTableSearch" class="form-control border-start-0" placeholder="Filter by product name, barcode, SKU...">
                    </div>
                </div>
                <div class="col-md-4 col-8">
                    <select id="inventoryCategoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= Format::escape($cat['id']) ?>"><?= Format::escape($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-4 text-end">
                    <div class="form-check form-switch d-inline-block text-start">
                        <input class="form-check-input" type="checkbox" id="filterLowStockOnly">
                        <label class="form-check-label small fw-medium" for="filterLowStockOnly">Low Stock Only</label>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="inventoryTable">
                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-3">Product Name</th>
                            <th>Category</th>
                            <th>Barcode / SKU</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th>Margin</th>
                            <th>Stock</th>
                            <th>Dynamic Details</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No products found. Click "Add Product" to create your first item!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): 
                                $stock = (int)($p['stock_quantity'] ?? 0);
                                $minAlert = (int)($p['min_stock_alert'] ?? 5);
                                $cost = (float)($p['cost_price'] ?? 0);
                                $price = (float)($p['selling_price'] ?? 0);
                                $marginPct = $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0;
                                $isLow = $stock <= $minAlert;

                                // Category lookup
                                $catName = 'Uncategorized';
                                $catColor = '#6c757d';
                                foreach ($categories as $c) {
                                    if ($c['id'] === ($p['category_id'] ?? '')) {
                                        $catName = $c['name'];
                                        $catColor = $c['color'] ?? '#0d6efd';
                                        break;
                                    }
                                }
                            ?>
                                <tr class="inv-row" 
                                    data-name="<?= Format::escape(strtolower($p['name'])) ?>"
                                    data-barcode="<?= Format::escape(strtolower($p['barcode'] ?? '')) ?>"
                                    data-sku="<?= Format::escape(strtolower($p['sku'] ?? '')) ?>"
                                    data-category="<?= Format::escape($p['category_id'] ?? '') ?>"
                                    data-is-low="<?= $isLow ? '1' : '0' ?>">
                                    
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark"><?= Format::escape($p['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill" style="background-color: <?= Format::escape($catColor) ?>; color: #fff; font-size: 0.75rem;">
                                            <?= Format::escape($catName) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-monospace small text-dark d-block"><?= Format::escape($p['barcode'] ?: 'N/A') ?></span>
                                        <small class="text-muted font-monospace"><?= Format::escape($p['sku'] ?? '') ?></small>
                                    </td>
                                    <td><?= Format::currency($cost, $settings['currency_symbol'] ?? '$') ?></td>
                                    <td class="fw-bold text-dark"><?= Format::currency($price, $settings['currency_symbol'] ?? '$') ?></td>
                                    <td>
                                        <span class="badge <?= $marginPct >= 30 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> px-2 py-1">
                                            <?= $marginPct ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($stock <= 0): ?>
                                            <span class="badge bg-danger text-white px-2 py-1">Out of Stock (0)</span>
                                        <?php elseif ($isLow): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" title="Minimum alert threshold: <?= $minAlert ?>">
                                                <?= $stock ?> <?= Format::escape($p['unit'] ?? 'pcs') ?> (Low)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <?= $stock ?> <?= Format::escape($p['unit'] ?? 'pcs') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['dynamic_attributes']) && is_array($p['dynamic_attributes'])): ?>
                                            <div class="d-flex flex-wrap gap-1" style="max-width: 240px;">
                                                <?php foreach ($p['dynamic_attributes'] as $attrKey => $attrVal): if ($attrVal): ?>
                                                    <span class="badge bg-light text-secondary border text-xs" style="font-size:0.7rem;">
                                                        <strong><?= Format::escape($attrKey) ?>:</strong> <?= Format::escape($attrVal) ?>
                                                    </span>
                                                <?php endif; endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary" onclick='window.editProduct(<?= json_encode($p) ?>)' title="Edit Product">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="window.openQuickAdjust('<?= Format::escape($p['id']) ?>', '<?= Format::escape(addslashes($p['name'])) ?>')" title="Adjust Stock">
                                                <i class="bi bi-plus-slash-minus"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="if(confirm('Delete product <?= Format::escape(addslashes($p['name'])) ?>?')) { window.location.href='/inventory/delete?id=<?= Format::escape($p['id']) ?>'; }" title="Delete Product">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    <?php endif; ?>

    <!-- TAB 2: Stock Audit Log -->
    <?php if ($activeTab === 'adjustments'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-1 text-primary"></i> Stock Adjustments & Movement Audit</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                        <tr>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>Adjustment Type</th>
                            <th>Change</th>
                            <th>Previous & New</th>
                            <th>Reason / Reference</th>
                            <th>Adjusted By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stockAdjustments)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No stock adjustment entries logged yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stockAdjustments as $adj): 
                                $change = (int)($adj['quantity_change'] ?? 0);
                            ?>
                                <tr>
                                    <td><?= Format::date($adj['created_at']) ?></td>
                                    <td class="fw-semibold text-dark"><?= Format::escape($adj['product_name'] ?? $adj['product_id']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border text-capitalize">
                                            <?= Format::escape(str_replace('_', ' ', $adj['type'] ?? 'adjustment')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= $change > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $change > 0 ? ('+' . $change) : $change ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= (int)($adj['previous_stock'] ?? 0) ?> &rarr; <strong class="text-dark"><?= (int)($adj['new_stock'] ?? 0) ?></strong>
                                    </td>
                                    <td><?= Format::escape($adj['reason'] ?: 'Manual count adjustment') ?></td>
                                    <td class="small text-muted"><?= Format::escape($adj['adjusted_by'] ?? 'Staff') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 3: Categories -->
    <?php if ($activeTab === 'categories'): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-1 text-primary"></i> Add / Edit Category</h6>
                    <form action="/inventory/category/save" method="POST">
                        <input type="hidden" name="category_id" id="catFormId">
                        
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Category Name</label>
                            <input type="text" name="name" id="catFormName" class="form-control" required placeholder="e.g. Beverages, Bakery, Electronics">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Badge Color</label>
                            <input type="color" name="color" id="catFormColor" class="form-control form-control-color w-100" value="#0d6efd">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Bootstrap Icon</label>
                            <input type="text" name="icon" id="catFormIcon" class="form-control" value="bi-tag" placeholder="bi-cup-straw, bi-bag, bi-tag">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Description (Optional)</label>
                            <textarea name="description" id="catFormDesc" class="form-control" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold">Save Category</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-tags me-1 text-primary"></i> Existing Categories</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase text-muted" style="font-size: 0.75rem;">
                                <tr>
                                    <th>Color</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge rounded-pill" style="background-color: <?= Format::escape($cat['color'] ?? '#0d6efd') ?>; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="bi <?= Format::escape($cat['icon'] ?? 'bi-tag') ?>"></i>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= Format::escape($cat['name']) ?></td>
                                        <td class="small text-muted"><?= Format::escape($cat['description'] ?? '—') ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary me-1" onclick="window.editCategory(<?= json_encode($cat) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="/inventory/category/delete?id=<?= Format::escape($cat['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL: Add / Edit Product with Dynamic Business Attributes -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form action="/inventory/save" method="POST" id="productForm">
                <input type="hidden" name="id" id="prodFormId">

                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="productModalLabel">
                        <i class="bi bi-box me-2 text-primary"></i> Product Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Product Name -->
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="prodFormName" class="form-control" required placeholder="e.g. Arabica Coffee Beans, Cotton T-Shirt">
                        </div>

                        <!-- Category -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category_id" id="prodFormCategory" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= Format::escape($cat['id']) ?>"><?= Format::escape($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Barcode & Auto-Generator -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Barcode (UPC / EAN)</label>
                            <div class="input-group">
                                <input type="text" name="barcode" id="prodFormBarcode" class="form-control font-monospace" placeholder="e.g. 890123450001">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('prodFormBarcode').value = '89' + Math.floor(1000000000 + Math.random() * 9000000000);">
                                    <i class="bi bi-magic me-1"></i> Generate
                                </button>
                            </div>
                        </div>

                        <!-- SKU -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">SKU / Item Code</label>
                            <input type="text" name="sku" id="prodFormSku" class="form-control font-monospace" placeholder="e.g. COF-ARA-250">
                        </div>

                        <!-- Pricing & Margin Calculator -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cost Price (<?= Format::escape($settings['currency_symbol'] ?? '$') ?>)</label>
                            <input type="number" step="0.01" min="0" name="cost_price" id="prodFormCost" class="form-control" value="0.00" oninput="window.calcMargin()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Selling Price (<?= Format::escape($settings['currency_symbol'] ?? '$') ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="selling_price" id="prodFormPrice" class="form-control fw-bold" required value="0.00" oninput="window.calcMargin()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Estimated Margin</label>
                            <div class="form-control bg-light text-muted fw-bold" id="prodFormMarginDisplay">0.0%</div>
                        </div>

                        <!-- Stock & Alert Levels -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Initial Stock Quantity</label>
                            <input type="number" min="0" name="stock_quantity" id="prodFormStock" class="form-control" value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Low Stock Warning Alert</label>
                            <input type="number" min="0" name="min_stock_alert" id="prodFormAlert" class="form-control" value="5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Unit of Measure</label>
                            <input type="text" name="unit" id="prodFormUnit" class="form-control" placeholder="pcs, kg, pack, box" value="pcs">
                        </div>

                        <!-- DYNAMIC BUSINESS ATTRIBUTES SECTION -->
                        <div class="col-12 mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-sliders2 me-1 text-primary"></i> Dynamic Attributes for <?= Format::escape(ucfirst($settings['business_type'] ?? 'Retail')) ?>
                                </h6>
                                <a href="/settings?tab=fields" class="btn btn-link btn-sm text-decoration-none p-0">
                                    <i class="bi bi-gear me-1"></i> Configure Fields
                                </a>
                            </div>
                            <small class="text-muted d-block mb-3">Custom fields configured for this business type.</small>

                            <div class="row g-2" id="dynamicFieldsContainer">
                                <?php if (empty($dynamicFields)): ?>
                                    <div class="col-12 text-muted small p-2 bg-light rounded">
                                        No custom fields configured. You can define custom fields like Size, Color, Expiry, or Brand in Settings &rarr; Custom Fields.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($dynamicFields as $field): 
                                        $fName = is_array($field) ? ($field['name'] ?? '') : $field;
                                        $fKey = 'dyn_' . md5($fName);
                                    ?>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-secondary"><?= Format::escape($fName) ?></label>
                                            <input type="text" name="<?= $fKey ?>" id="field_<?= $fKey ?>" class="form-control dyn-input" placeholder="Enter <?= Format::escape($fName) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Quick Stock Adjustment -->
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-labelledby="stockAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="/inventory/adjust" method="POST">
                <div class="modal-header bg-light py-2.5">
                    <h6 class="modal-title fw-bold" id="stockAdjustModalLabel"><i class="bi bi-arrow-left-right me-1 text-primary"></i> Stock Quantity Adjustment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Select Product</label>
                        <select name="product_id" id="adjustModalProductId" class="form-select" required>
                            <option value="">Choose Product...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= Format::escape($p['id']) ?>">
                                    <?= Format::escape($p['name']) ?> (Current: <?= (int)($p['stock_quantity'] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Adjustment Type</label>
                        <select name="type" class="form-select" id="adjustTypeSelect">
                            <option value="restock">+ Stock Shipment / Restock</option>
                            <option value="count_audit">+/- Physical Count Correction</option>
                            <option value="return">+ Customer Return to Stock</option>
                            <option value="damage">- Damaged / Broken Goods</option>
                            <option value="expired">- Expired / Spoiled Stock</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Quantity to Change</label>
                        <input type="number" name="quantity_change" class="form-control" required placeholder="e.g. 10 or -5">
                        <small class="text-muted" style="font-size: 0.72rem;">Enter positive number to add stock, negative number to remove stock.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Reason / Reference Note</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Supplier PO #1089, Damaged packaging">
                    </div>

                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Record Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Live Margin calculation
    window.calcMargin = function () {
        const cost = parseFloat(document.getElementById('prodFormCost')?.value || 0);
        const price = parseFloat(document.getElementById('prodFormPrice')?.value || 0);
        const marginEl = document.getElementById('prodFormMarginDisplay');
        if (!marginEl) return;

        if (price > 0) {
            const margin = ((price - cost) / price) * 100;
            marginEl.textContent = margin.toFixed(1) + '%';
            marginEl.className = 'form-control fw-bold ' + (margin >= 30 ? 'text-success' : (margin > 0 ? 'text-primary' : 'text-danger'));
        } else {
            marginEl.textContent = '0.0%';
        }
    };

    // Reset Product Form for Create
    window.resetProductForm = function () {
        document.getElementById('productForm').reset();
        document.getElementById('prodFormId').value = '';
        document.getElementById('productModalLabel').innerHTML = '<i class="bi bi-box me-2 text-primary"></i> Add New Product';
        window.calcMargin();
    };

    // Populate Product Form for Edit
    window.editProduct = function (p) {
        document.getElementById('prodFormId').value = p.id;
        document.getElementById('prodFormName').value = p.name || '';
        document.getElementById('prodFormCategory').value = p.category_id || '';
        document.getElementById('prodFormBarcode').value = p.barcode || '';
        document.getElementById('prodFormSku').value = p.sku || '';
        document.getElementById('prodFormCost').value = p.cost_price || '0.00';
        document.getElementById('prodFormPrice').value = p.selling_price || '0.00';
        document.getElementById('prodFormStock').value = p.stock_quantity || '0';
        document.getElementById('prodFormAlert').value = p.min_stock_alert || '5';
        document.getElementById('prodFormUnit').value = p.unit || 'pcs';

        // Populate dynamic attributes
        const dyn = p.dynamic_attributes || {};
        document.querySelectorAll('.dyn-input').forEach(input => {
            input.value = '';
        });

        <?php if (!empty($dynamicFields)): ?>
            <?php foreach ($dynamicFields as $field): 
                $fName = is_array($field) ? ($field['name'] ?? '') : $field;
                $fKey = 'dyn_' . md5($fName);
            ?>
                if (dyn[<?= json_encode($fName) ?>] !== undefined) {
                    const el = document.getElementById('field_<?= $fKey ?>');
                    if (el) el.value = dyn[<?= json_encode($fName) ?>];
                }
            <?php endforeach; ?>
        <?php endif; ?>

        document.getElementById('productModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2 text-primary"></i> Edit Product';
        window.calcMargin();

        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    };

    // Quick open adjust modal for specific product
    window.openQuickAdjust = function (id, name) {
        const select = document.getElementById('adjustModalProductId');
        if (select) select.value = id;
        const modal = new bootstrap.Modal(document.getElementById('stockAdjustModal'));
        modal.show();
    };

    // Edit Category helper
    window.editCategory = function (cat) {
        document.getElementById('catFormId').value = cat.id;
        document.getElementById('catFormName').value = cat.name;
        document.getElementById('catFormColor').value = cat.color || '#0d6efd';
        document.getElementById('catFormIcon').value = cat.icon || 'bi-tag';
        document.getElementById('catFormDesc').value = cat.description || '';
    };

    // Live table search & category filter
    const tableSearch = document.getElementById('inventoryTableSearch');
    const catFilter = document.getElementById('inventoryCategoryFilter');
    const lowStockToggle = document.getElementById('filterLowStockOnly');

    function filterTable() {
        const q = (tableSearch?.value || '').toLowerCase().trim();
        const cat = catFilter?.value || '';
        const lowOnly = lowStockToggle?.checked || false;

        document.querySelectorAll('.inv-row').forEach(row => {
            const name = row.dataset.name || '';
            const barcode = row.dataset.barcode || '';
            const sku = row.dataset.sku || '';
            const rowCat = row.dataset.category || '';
            const isLow = row.dataset.isLow === '1';

            const matchesSearch = !q || name.includes(q) || barcode.includes(q) || sku.includes(q);
            const matchesCat = !cat || rowCat === cat;
            const matchesLow = !lowOnly || isLow;

            if (matchesSearch && matchesCat && matchesLow) {
                row.classList.remove('d-none');
            } else {
                row.classList.add('d-none');
            }
        });
    }

    if (tableSearch) tableSearch.addEventListener('input', filterTable);
    if (catFilter) catFilter.addEventListener('change', filterTable);
    if (lowStockToggle) lowStockToggle.addEventListener('change', filterTable);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
