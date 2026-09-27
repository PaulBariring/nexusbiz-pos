<?php

// Front Controller & Router for NexusBiz POS & Inventory

declare(strict_types=1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoloader for App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load Configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize Services
$localStorage = new App\Services\LocalStorageService($config['storage_path']);
$supabaseService = new App\Services\SupabaseService(
    $config['supabase']['url'] ?? '',
    $config['supabase']['key'] ?? ''
);
$db = new App\Services\DatabaseService($localStorage, $supabaseService);

// Request URI and Path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Route dispatch
switch ($path) {
    // POS Terminal
    case '/':
    case '/pos':
        (new App\Controllers\PosController($db))->index();
        break;

    case '/receipt':
        (new App\Controllers\PosController($db))->receipt();
        break;

    // Inventory
    case '/inventory':
        (new App\Controllers\InventoryController($db))->index();
        break;

    case '/inventory/save':
        (new App\Controllers\InventoryController($db))->saveProduct();
        break;

    case '/inventory/delete':
        (new App\Controllers\InventoryController($db))->deleteProduct();
        break;

    case '/inventory/adjust':
        (new App\Controllers\InventoryController($db))->adjustStock();
        break;

    case '/inventory/category/save':
        (new App\Controllers\InventoryController($db))->saveCategory();
        break;

    case '/inventory/category/delete':
        (new App\Controllers\InventoryController($db))->deleteCategory();
        break;

    // Shifts & Register
    case '/shifts':
        (new App\Controllers\ShiftController($db))->index();
        break;

    case '/shifts/open':
        (new App\Controllers\ShiftController($db))->open();
        break;

    case '/shifts/close':
        (new App\Controllers\ShiftController($db))->close();
        break;

    case '/shifts/movement':
        (new App\Controllers\ShiftController($db))->cashMovement();
        break;

    // Reports & Analytics
    case '/reports':
        (new App\Controllers\ReportController($db))->index();
        break;

    // Settings & Dynamic Business Customizer
    case '/settings':
        (new App\Controllers\SettingsController($db))->index();
        break;

    case '/settings/profile':
        (new App\Controllers\SettingsController($db))->saveProfile();
        break;

    case '/settings/preset':
        (new App\Controllers\SettingsController($db))->applyPreset();
        break;

    case '/settings/fields/add':
        (new App\Controllers\SettingsController($db))->addDynamicField();
        break;

    case '/settings/fields/remove':
        (new App\Controllers\SettingsController($db))->removeDynamicField();
        break;

    case '/settings/supabase':
        (new App\Controllers\SettingsController($db))->saveSupabaseConfig();
        break;

    case '/settings/supabase/sync':
        (new App\Controllers\SettingsController($db))->syncToSupabase();
        break;

    // API AJAX Endpoints
    case '/api/products':
        (new App\Controllers\ApiController($db))->searchProducts();
        break;

    case '/api/barcode':
        (new App\Controllers\ApiController($db))->scanBarcode();
        break;

    case '/api/checkout':
        (new App\Controllers\ApiController($db))->checkout();
        break;

    case '/api/park':
        (new App\Controllers\ApiController($db))->parkSale();
        break;

    case '/api/parked':
        (new App\Controllers\ApiController($db))->getParkedSales();
        break;

    case '/api/parked/resume':
        (new App\Controllers\ApiController($db))->resumeParkedSale();
        break;

    case '/api/parked/delete':
        (new App\Controllers\ApiController($db))->deleteParkedSale();
        break;

    case '/api/test-supabase':
        (new App\Controllers\ApiController($db))->testSupabase();
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../views/layouts/header.php';
        echo '<div class="container text-center py-5">
                <i class="bi bi-question-circle display-1 text-muted mb-3 d-block"></i>
                <h3 class="fw-bold">404 - Page Not Found</h3>
                <p class="text-muted">The requested page does not exist.</p>
                <a href="/pos" class="btn btn-primary fw-semibold">Back to POS</a>
              </div>';
        require __DIR__ . '/../views/layouts/footer.php';
        break;
}
