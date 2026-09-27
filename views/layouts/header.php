<?php
use App\Helpers\Format;
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($currentUri, PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Format::escape($pageTitle ?? 'POS & Inventory') ?> — <?= Format::escape($settings['business_name'] ?? 'NexusBiz') ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Bootstrap 5.3 JS Bundle (loaded in head so all page scripts have access to bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm px-3 py-2">
        <div class="container-fluid">
            <!-- Brand Logo & Business Name -->
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
                <span class="badge bg-primary p-2 rounded-3">
                    <i class="bi bi-shop fs-5"></i>
                </span>
                <div>
                    <span class="fs-5 tracking-tight"><?= Format::escape($settings['business_name'] ?? 'NexusBiz') ?></span>
                    <small class="d-block text-white-50 fs-xs text-uppercase fw-normal" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        <?= Format::escape($settings['business_type'] ?? 'retail') ?> POS & Inventory
                    </small>
                </div>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-2 <?= in_array($path, ['/', '/pos']) ? 'active bg-primary text-white fw-semibold shadow-sm' : 'text-light' ?>" href="/pos">
                            <i class="bi bi-calculator me-1"></i> Point of Sale
                            <span class="badge bg-light text-dark ms-1 d-none d-xl-inline-block text-xs" style="font-size:0.65rem;">F2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-2 <?= str_starts_with($path, '/inventory') ? 'active bg-primary text-white fw-semibold shadow-sm' : 'text-light' ?>" href="/inventory">
                            <i class="bi bi-box-seam me-1"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-2 <?= str_starts_with($path, '/shifts') ? 'active bg-primary text-white fw-semibold shadow-sm' : 'text-light' ?>" href="/shifts">
                            <i class="bi bi-cash-stack me-1"></i> Shifts & Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-2 <?= str_starts_with($path, '/reports') ? 'active bg-primary text-white fw-semibold shadow-sm' : 'text-light' ?>" href="/reports">
                            <i class="bi bi-bar-chart-line me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-2 <?= str_starts_with($path, '/settings') ? 'active bg-primary text-white fw-semibold shadow-sm' : 'text-light' ?>" href="/settings">
                            <i class="bi bi-sliders me-1"></i> Settings
                        </a>
                    </li>
                </ul>

                <!-- Right Side Status Badges -->
                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    <!-- Database Status Pill -->
                    <?php if (!empty($isSupabase) && $isSupabase): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1.5" title="Connected to Cloud Supabase">
                            <span class="status-dot bg-success"></span>
                            <span class="fw-medium">Supabase Cloud</span>
                        </span>
                    <?php else: ?>
                        <a href="/settings?tab=supabase" class="text-decoration-none" title="Running in Local Persistent Mode. Click to connect Supabase">
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1.5">
                                <span class="status-dot bg-info"></span>
                                <span class="fw-medium">Local Engine</span>
                                <i class="bi bi-cloud-arrow-up ms-0.5"></i>
                            </span>
                        </a>
                    <?php endif; ?>

                    <!-- Active Shift Status -->
                    <?php if (!empty($activeShift)): ?>
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1" title="Register Shift is currently OPEN">
                            <i class="bi bi-unlock-fill text-success"></i>
                            <span><?= Format::escape($activeShift['cashier_name'] ?? 'Register') ?></span>
                        </span>
                    <?php else: ?>
                        <a href="/shifts" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-decoration-none px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1" title="Click to open register shift">
                            <i class="bi bi-lock-fill text-warning-emphasis"></i>
                            <span>Shift Closed</span>
                        </a>
                    <?php endif; ?>

                    <!-- Currency Display -->
                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1.5 rounded-pill">
                        <?= Format::escape($settings['currency_symbol'] ?? '$') ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Alert / Notification Toast Banner -->
    <?php if (!empty($_GET['msg'])): ?>
        <div class="container-fluid pt-3 px-4">
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2 mb-0" role="alert">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div class="fw-medium"><?= Format::escape($_GET['msg']) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="container-fluid pt-3 px-4">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div class="fw-medium"><?= Format::escape($_GET['error']) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="py-3 px-2 px-md-4">
