<?php
use App\Helpers\Format;
require __DIR__ . '/../layouts/header.php';

$activeTab = $_GET['tab'] ?? 'profile';
$dynamicFields = $settings['dynamic_fields'] ?? [];
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-sliders me-2 text-primary"></i> Business Settings & Customization
            </h4>
            <small class="text-muted">Configure store identity, dynamic attributes for different business types, taxes, and Supabase database</small>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'profile' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/settings?tab=profile">
                <i class="bi bi-shop me-1"></i> Business Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'preset' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/settings?tab=preset">
                <i class="bi bi-magic me-1"></i> Dynamic Business Presets
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'fields' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/settings?tab=fields">
                <i class="bi bi-input-cursor me-1"></i> Custom Product Fields (<?= count($dynamicFields) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'supabase' ? 'active fw-bold text-primary' : 'text-muted' ?>" href="/settings?tab=supabase">
                <i class="bi bi-cloud-arrow-up me-1"></i> Supabase Cloud Database
            </a>
        </li>
    </ul>

    <!-- TAB 1: Business Profile & Tax Settings -->
    <?php if ($activeTab === 'profile'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
            <form action="/settings/profile" method="POST">
                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Store Identity & Receipts</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Business / Store Name <span class="text-danger">*</span></label>
                        <input type="text" name="business_name" class="form-control" required value="<?= Format::escape($settings['business_name'] ?? 'Nexus Store') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tagline / Slogan</label>
                        <input type="text" name="tagline" class="form-control" value="<?= Format::escape($settings['tagline'] ?? '') ?>" placeholder="e.g. Everyday Quality Essentials">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Business Type</label>
                        <select name="business_type" class="form-select">
                            <option value="retail" <?= ($settings['business_type'] ?? '') === 'retail' ? 'selected' : '' ?>>Retail & General Store</option>
                            <option value="apparel" <?= ($settings['business_type'] ?? '') === 'apparel' ? 'selected' : '' ?>>Apparel & Boutique</option>
                            <option value="grocery" <?= ($settings['business_type'] ?? '') === 'grocery' ? 'selected' : '' ?>>Grocery & Convenience</option>
                            <option value="cafe" <?= ($settings['business_type'] ?? '') === 'cafe' ? 'selected' : '' ?>>Cafe & Food/Beverage</option>
                            <option value="repair" <?= ($settings['business_type'] ?? '') === 'repair' ? 'selected' : '' ?>>Electronics & Repair Services</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tax Number / TIN / VAT ID</label>
                        <input type="text" name="tax_id" class="form-control font-monospace" value="<?= Format::escape($settings['tax_id'] ?? '') ?>" placeholder="e.g. TAX-889922-PH">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control fw-bold" style="max-width: 120px;" value="<?= Format::escape($settings['currency_symbol'] ?? '$') ?>" placeholder="$, ₱, €, £, ¥">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Store Address</label>
                        <input type="text" name="address" class="form-control" value="<?= Format::escape($settings['address'] ?? '') ?>" placeholder="e.g. 123 Commerce Avenue, City Center">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= Format::escape($settings['phone'] ?? '') ?>" placeholder="+1 (555) 0199">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= Format::escape($settings['email'] ?? '') ?>" placeholder="store@nexusbiz.local">
                    </div>
                </div>

                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Tax Configuration</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tax Name</label>
                        <input type="text" name="tax_name" class="form-control" value="<?= Format::escape($settings['tax_name'] ?? 'Sales Tax') ?>" placeholder="Sales Tax, VAT, GST">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tax Rate (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="form-control fw-bold" value="<?= (float)($settings['tax_rate'] ?? 0) ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex align-items-center mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tax_inclusive" id="tax_inclusive" value="1" <?= !empty($settings['tax_inclusive']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="tax_inclusive">Prices are Tax Inclusive</label>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Thermal Receipt Message</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Receipt Header Note</label>
                        <input type="text" name="receipt_header" class="form-control" value="<?= Format::escape($settings['receipt_header'] ?? '') ?>" placeholder="Thank you for shopping with us!">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Receipt Footer / Return Policy</label>
                        <input type="text" name="receipt_footer" class="form-control" value="<?= Format::escape($settings['receipt_footer'] ?? '') ?>" placeholder="Items in original condition can be returned within 7 days.">
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-bold px-4 py-2.5 shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- TAB 2: Dynamic Business Presets -->
    <?php if ($activeTab === 'preset'): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
            <h5 class="fw-bold mb-1 text-dark">Dynamic Business Presets</h5>
            <p class="text-muted small mb-4">Switch presets to instantly adapt product fields and workflow for different business types.</p>

            <div class="row g-3">
                <!-- Preset 1: Retail & General -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border p-3 shadow-sm rounded-3 <?= ($settings['business_type'] ?? '') === 'retail' ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shop text-primary me-1"></i> Retail & General</h6>
                            <?php if (($settings['business_type'] ?? '') === 'retail'): ?>
                                <span class="badge bg-primary">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">General merchandise, supermarkets, hardware, books, stationeries.</p>
                        <ul class="small text-secondary ps-3 mb-3">
                            <li>Brand</li>
                            <li>SKU / Model</li>
                            <li>Supplier</li>
                            <li>Warranty</li>
                        </ul>
                        <form action="/settings/preset" method="POST" class="mt-auto">
                            <input type="hidden" name="preset" value="retail">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Apply Retail Preset</button>
                        </form>
                    </div>
                </div>

                <!-- Preset 2: Apparel & Boutique -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border p-3 shadow-sm rounded-3 <?= ($settings['business_type'] ?? '') === 'apparel' ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bag-heart text-danger me-1"></i> Apparel & Boutique</h6>
                            <?php if (($settings['business_type'] ?? '') === 'apparel'): ?>
                                <span class="badge bg-primary">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">Clothing, shoes, accessories, and seasonal fashion items.</p>
                        <ul class="small text-secondary ps-3 mb-3">
                            <li>Size (XS, S, M, L, XL, XXL)</li>
                            <li>Color</li>
                            <li>Material / Fabric</li>
                            <li>Gender / Department</li>
                            <li>Season</li>
                        </ul>
                        <form action="/settings/preset" method="POST" class="mt-auto">
                            <input type="hidden" name="preset" value="apparel">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Apply Apparel Preset</button>
                        </form>
                    </div>
                </div>

                <!-- Preset 3: Grocery & Convenience -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border p-3 shadow-sm rounded-3 <?= ($settings['business_type'] ?? '') === 'grocery' ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-basket text-success me-1"></i> Grocery & Convenience</h6>
                            <?php if (($settings['business_type'] ?? '') === 'grocery'): ?>
                                <span class="badge bg-primary">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">Fresh produce, packed goods, perishable items, and batch tracking.</p>
                        <ul class="small text-secondary ps-3 mb-3">
                            <li>Unit of Measure (kg, pcs, box)</li>
                            <li>Batch / Lot Number</li>
                            <li>Expiration Date</li>
                            <li>Brand</li>
                        </ul>
                        <form action="/settings/preset" method="POST" class="mt-auto">
                            <input type="hidden" name="preset" value="grocery">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Apply Grocery Preset</button>
                        </form>
                    </div>
                </div>

                <!-- Preset 4: Cafe & F&B -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border p-3 shadow-sm rounded-3 <?= ($settings['business_type'] ?? '') === 'cafe' ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cup-hot text-warning me-1"></i> Cafe & Eatery</h6>
                            <?php if (($settings['business_type'] ?? '') === 'cafe'): ?>
                                <span class="badge bg-primary">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">Coffee shops, bakeries, quick service food stalls, and drink bars.</p>
                        <ul class="small text-secondary ps-3 mb-3">
                            <li>Serving Size (Regular / Large)</li>
                            <li>Sugar / Sweetness Level</li>
                            <li>Temperature (Hot / Iced)</li>
                            <li>Kitchen Station</li>
                        </ul>
                        <form action="/settings/preset" method="POST" class="mt-auto">
                            <input type="hidden" name="preset" value="cafe">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Apply Cafe Preset</button>
                        </form>
                    </div>
                </div>

                <!-- Preset 5: Electronics & Repair -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border p-3 shadow-sm rounded-3 <?= ($settings['business_type'] ?? '') === 'repair' ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tools text-secondary me-1"></i> Electronics & Repair</h6>
                            <?php if (($settings['business_type'] ?? '') === 'repair'): ?>
                                <span class="badge bg-primary">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">Mobile phones, computers, gadgets, warranty tracking, and repair labor.</p>
                        <ul class="small text-secondary ps-3 mb-3">
                            <li>Serial Number / IMEI</li>
                            <li>Device Model</li>
                            <li>Warranty Coverage</li>
                            <li>Technician</li>
                        </ul>
                        <form action="/settings/preset" method="POST" class="mt-auto">
                            <input type="hidden" name="preset" value="repair">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100 fw-semibold">Apply Electronics Preset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 3: Custom Product Fields Builder -->
    <?php if ($activeTab === 'fields'): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                    <h5 class="fw-bold mb-2 text-dark"><i class="bi bi-plus-circle me-1 text-primary"></i> Add Custom Field</h5>
                    <p class="text-muted small mb-3">Add any custom attribute required by your business without database migrations.</p>

                    <form action="/settings/fields/add" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Field Name</label>
                            <input type="text" name="field_name" class="form-control" required placeholder="e.g. Fabric, Storage Capacity, Flavor, Expiry">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-plus-lg me-1"></i> Add Custom Attribute
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                    <h5 class="fw-bold mb-2 text-dark"><i class="bi bi-list-check me-1 text-primary"></i> Active Custom Attributes</h5>
                    <p class="text-muted small mb-3">These fields will appear dynamically in your Product Form and Inventory Catalog.</p>

                    <div class="list-group">
                        <?php if (empty($dynamicFields)): ?>
                            <div class="p-3 text-muted text-center border rounded">No custom fields defined yet.</div>
                        <?php else: ?>
                            <?php foreach ($dynamicFields as $field): 
                                $fName = is_array($field) ? ($field['name'] ?? '') : $field;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-grip-vertical text-muted"></i>
                                        <span class="fw-semibold text-dark"><?= Format::escape($fName) ?></span>
                                    </div>
                                    <a href="/settings/fields/remove?field_name=<?= urlencode($fName) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove attribute <?= Format::escape(addslashes($fName)) ?>?')">
                                        <i class="bi bi-trash"></i> Remove
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 4: Supabase Cloud Database Configuration -->
    <?php if ($activeTab === 'supabase'): ?>
        <div class="row g-3 mb-4">
            
            <!-- Supabase Credentials Form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 bg-success-subtle text-success rounded-3">
                                <i class="bi bi-database-check fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Supabase Cloud Connection</h5>
                                <small class="text-muted">Direct PostgreSQL and REST API via Supabase</small>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <?php if ($supabaseStatus['success']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                <i class="bi bi-check-circle-fill me-1"></i> Connected
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill">
                                <i class="bi bi-info-circle-fill me-1"></i> Local Engine Mode
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($supabaseStatus['success']): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-check2-circle fs-5"></i>
                            <div><strong>Live Cloud Database Active:</strong> <?= Format::escape($supabaseStatus['message']) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-hdd-network text-info fs-5"></i>
                            <div class="small">The application is running with its high-speed <strong>Local Storage Engine</strong>. Enter your Supabase credentials below anytime to connect your cloud database.</div>
                        </div>
                    <?php endif; ?>

                    <form action="/settings/supabase" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Supabase Project URL</label>
                            <input type="url" name="supabase_url" id="supabaseUrlInput" class="form-control font-monospace" placeholder="https://xyzcompany.supabase.co" value="<?= Format::escape(getenv('SUPABASE_URL') ?: '') ?>">
                            <small class="text-muted" style="font-size: 0.72rem;">Found in Supabase Dashboard &rarr; Project Settings &rarr; API &rarr; Project URL</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Supabase API Key (anon or service_role)</label>
                            <input type="password" name="supabase_key" id="supabaseKeyInput" class="form-control font-monospace" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6..." value="<?= Format::escape(getenv('SUPABASE_KEY') ?: '') ?>">
                            <small class="text-muted" style="font-size: 0.72rem;">Found in Supabase Dashboard &rarr; Project Settings &rarr; API &rarr; Project API keys</small>
                        </div>

                        <div class="d-flex justify-content-between gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.testSupabaseConnection()">
                                <i class="bi bi-lightning-charge me-1"></i> Test Connection
                            </button>
                            <button type="submit" class="btn btn-primary fw-semibold px-4">
                                <i class="bi bi-save me-1"></i> Save Credentials
                            </button>
                        </div>
                    </form>

                    <!-- Sync local data button -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-1 text-dark small text-uppercase">Data Synchronization</h6>
                        <p class="text-muted small mb-2">Push all current local products, categories, and settings up to Supabase with one click.</p>
                        <form action="/settings/supabase/sync" method="POST">
                            <button type="submit" class="btn btn-outline-success btn-sm fw-semibold">
                                <i class="bi bi-cloud-upload me-1"></i> Sync Local Data to Supabase
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Supabase 2-Minute Quick Setup Guide -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                    <h5 class="fw-bold mb-2 text-dark"><i class="bi bi-file-code me-1 text-primary"></i> 2-Minute Supabase Setup Guide</h5>
                    <ol class="small text-secondary ps-3 mb-3" style="line-height: 1.7;">
                        <li>Go to <a href="https://supabase.com" target="_blank" class="fw-semibold">supabase.com</a> and sign in / create a free project.</li>
                        <li>In your Supabase dashboard, click <strong>SQL Editor</strong> on the left sidebar.</li>
                        <li>Open the file <code class="text-dark bg-light px-1 rounded">database/supabase_schema.sql</code> from this project.</li>
                        <li>Paste its content into the SQL Editor and click <strong>Run</strong>. (This creates all 8 tables, indexes, and RLS policies).</li>
                        <li>Go to <strong>Project Settings &rarr; API</strong>, copy your <strong>Project URL</strong> and <strong>anon public API key</strong>, and paste them into the form on the left.</li>
                    </ol>

                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark">Schema File Location:</span>
                            <span class="badge bg-secondary">PostgreSQL Ready</span>
                        </div>
                        <code class="d-block small text-primary font-monospace">database/supabase_schema.sql</code>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    window.testSupabaseConnection = function () {
        fetch('/api/test-supabase')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Success! ' + data.message);
                } else {
                    alert('Connection check: ' + data.message);
                }
            })
            .catch(() => alert('Could not reach test endpoint.'));
    };
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
