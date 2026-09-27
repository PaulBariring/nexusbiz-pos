<?php

namespace App\Services;

class LocalStorageService
{
    private string $filePath;

    public function __construct(string $storagePath)
    {
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }
        $this->filePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . 'db.json';
        $this->ensureInitialized();
    }

    private function ensureInitialized(): void
    {
        if (!file_exists($this->filePath)) {
            $seedFile = __DIR__ . '/../../database/seed_data.json';
            if (file_exists($seedFile)) {
                copy($seedFile, $this->filePath);
            } else {
                $emptyData = [
                    'business_settings' => [
                        'id' => 'default',
                        'business_name' => 'Nexus Retail Store',
                        'business_type' => 'retail',
                        'currency_symbol' => '$',
                        'tax_rate' => 8.50,
                        'tax_inclusive' => false,
                        'dynamic_fields' => ['Brand', 'SKU/Model', 'Supplier', 'Warranty']
                    ],
                    'categories' => [],
                    'products' => [],
                    'sales' => [],
                    'shifts' => [],
                    'cash_movements' => [],
                    'stock_adjustments' => [],
                    'parked_sales' => []
                ];
                file_put_contents($this->filePath, json_encode($emptyData, JSON_PRETTY_PRINT));
            }
        }
    }

    public function readAll(): array
    {
        $this->ensureInitialized();
        $json = @file_get_contents($this->filePath);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public function writeAll(array $data): bool
    {
        return file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    // Settings
    public function getSettings(): array
    {
        $data = $this->readAll();
        return $data['business_settings'] ?? [];
    }

    public function saveSettings(array $settings): bool
    {
        $data = $this->readAll();
        $current = $data['business_settings'] ?? [];
        $data['business_settings'] = array_merge($current, $settings);
        return $this->writeAll($data);
    }

    // Categories
    public function getCategories(): array
    {
        $data = $this->readAll();
        return $data['categories'] ?? [];
    }

    public function saveCategory(array $category): array
    {
        $data = $this->readAll();
        $categories = $data['categories'] ?? [];
        
        if (empty($category['id'])) {
            $category['id'] = 'cat_' . bin2hex(random_bytes(4));
            $categories[] = $category;
        } else {
            $found = false;
            foreach ($categories as $index => $existing) {
                if ($existing['id'] === $category['id']) {
                    $categories[$index] = array_merge($existing, $category);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $categories[] = $category;
            }
        }

        $data['categories'] = $categories;
        $this->writeAll($data);
        return $category;
    }

    public function deleteCategory(string $id): bool
    {
        $data = $this->readAll();
        $categories = $data['categories'] ?? [];
        $data['categories'] = array_values(array_filter($categories, fn($c) => $c['id'] !== $id));
        return $this->writeAll($data);
    }

    // Products
    public function getProducts(): array
    {
        $data = $this->readAll();
        return $data['products'] ?? [];
    }

    public function getProductById(string $id): ?array
    {
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product['id'] === $id) return $product;
        }
        return null;
    }

    public function getProductByBarcode(string $barcode): ?array
    {
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (!empty($product['barcode']) && trim($product['barcode']) === trim($barcode)) {
                return $product;
            }
        }
        return null;
    }

    public function saveProduct(array $product): array
    {
        $data = $this->readAll();
        $products = $data['products'] ?? [];

        if (empty($product['id'])) {
            $product['id'] = 'prod_' . bin2hex(random_bytes(5));
            $product['created_at'] = date('c');
            $products[] = $product;
        } else {
            $found = false;
            foreach ($products as $index => $existing) {
                if ($existing['id'] === $product['id']) {
                    $product['updated_at'] = date('c');
                    $products[$index] = array_merge($existing, $product);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $product['created_at'] = date('c');
                $products[] = $product;
            }
        }

        $data['products'] = $products;
        $this->writeAll($data);
        return $product;
    }

    public function deleteProduct(string $id): bool
    {
        $data = $this->readAll();
        $products = $data['products'] ?? [];
        $data['products'] = array_values(array_filter($products, fn($p) => $p['id'] !== $id));
        return $this->writeAll($data);
    }

    public function updateProductStock(string $productId, int $quantityChange): bool
    {
        $data = $this->readAll();
        $products = $data['products'] ?? [];
        $updated = false;

        foreach ($products as $index => $p) {
            if ($p['id'] === $productId) {
                $currentStock = (int)($p['stock_quantity'] ?? 0);
                $newStock = max(0, $currentStock + $quantityChange);
                $products[$index]['stock_quantity'] = $newStock;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $data['products'] = $products;
            $this->writeAll($data);
        }
        return $updated;
    }

    // Stock Adjustments
    public function recordStockAdjustment(array $adjustment): array
    {
        $data = $this->readAll();
        $adjustments = $data['stock_adjustments'] ?? [];
        $products = $data['products'] ?? [];

        $adjustment['id'] = 'adj_' . bin2hex(random_bytes(5));
        $adjustment['created_at'] = date('c');
        $adjustments[] = $adjustment;

        // Also adjust the product's stock count
        $change = (int)($adjustment['quantity_change'] ?? 0);
        foreach ($products as $idx => $p) {
            if ($p['id'] === $adjustment['product_id']) {
                $cur = (int)($p['stock_quantity'] ?? 0);
                $products[$idx]['stock_quantity'] = max(0, $cur + $change);
                break;
            }
        }

        $data['stock_adjustments'] = $adjustments;
        $data['products'] = $products;
        $this->writeAll($data);

        return $adjustment;
    }

    public function getStockAdjustments(int $limit = 50): array
    {
        $data = $this->readAll();
        $adjustments = $data['stock_adjustments'] ?? [];
        usort($adjustments, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_slice($adjustments, 0, $limit);
    }

    // Sales
    public function getSales(int $limit = 100): array
    {
        $data = $this->readAll();
        $sales = $data['sales'] ?? [];
        usort($sales, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_slice($sales, 0, $limit);
    }

    public function getSaleById(string $id): ?array
    {
        $sales = $this->getSales(500);
        foreach ($sales as $sale) {
            if ($sale['id'] === $id) return $sale;
        }
        return null;
    }

    public function createSale(array $saleData): array
    {
        $data = $this->readAll();
        $sales = $data['sales'] ?? [];
        $products = $data['products'] ?? [];

        $saleId = 'sale_' . bin2hex(random_bytes(6));
        $saleData['id'] = $saleId;
        $saleData['created_at'] = date('c');

        // Deduct inventory for each item
        if (!empty($saleData['items']) && is_array($saleData['items'])) {
            foreach ($saleData['items'] as $item) {
                if (!empty($item['product_id'])) {
                    $qty = (int)($item['quantity'] ?? 1);
                    foreach ($products as $idx => $p) {
                        if ($p['id'] === $item['product_id']) {
                            $cur = (int)($p['stock_quantity'] ?? 0);
                            $products[$idx]['stock_quantity'] = max(0, $cur - $qty);
                            break;
                        }
                    }
                }
            }
        }

        $sales[] = $saleData;
        $data['sales'] = $sales;
        $data['products'] = $products;
        $this->writeAll($data);

        // Update active shift totals if a shift is active
        $this->recordSaleToActiveShift($saleData);

        return $saleData;
    }

    // Shifts
    public function getShifts(int $limit = 30): array
    {
        $data = $this->readAll();
        $shifts = $data['shifts'] ?? [];
        usort($shifts, fn($a, $b) => strcmp($b['opened_at'] ?? '', $a['opened_at'] ?? ''));
        return array_slice($shifts, 0, $limit);
    }

    public function getActiveShift(): ?array
    {
        $shifts = $this->getShifts();
        foreach ($shifts as $shift) {
            if (($shift['status'] ?? 'open') === 'open') {
                return $shift;
            }
        }
        return null;
    }

    public function openShift(string $cashierName, float $startingCash, string $notes = ''): array
    {
        $data = $this->readAll();
        $shifts = $data['shifts'] ?? [];

        // Close any lingering open shift first
        foreach ($shifts as &$s) {
            if ($s['status'] === 'open') {
                $s['status'] = 'closed';
                $s['closed_at'] = date('c');
            }
        }
        unset($s);

        $newShift = [
            'id' => 'shift_' . bin2hex(random_bytes(5)),
            'cashier_name' => $cashierName,
            'starting_cash' => $startingCash,
            'ending_cash' => 0.0,
            'expected_cash' => $startingCash,
            'cash_sales' => 0.0,
            'card_sales' => 0.0,
            'qr_sales' => 0.0,
            'other_sales' => 0.0,
            'cash_in' => 0.0,
            'cash_out' => 0.0,
            'status' => 'open',
            'notes' => $notes,
            'opened_at' => date('c'),
            'closed_at' => null
        ];

        $shifts[] = $newShift;
        $data['shifts'] = $shifts;
        $this->writeAll($data);

        return $newShift;
    }

    public function closeShift(string $shiftId, float $actualEndingCash, string $closingNotes = ''): ?array
    {
        $data = $this->readAll();
        $shifts = $data['shifts'] ?? [];
        $closedShift = null;

        foreach ($shifts as $index => $shift) {
            if ($shift['id'] === $shiftId) {
                $expected = (float)$shift['starting_cash'] + (float)$shift['cash_sales'] + (float)$shift['cash_in'] - (float)$shift['cash_out'];
                $shifts[$index]['status'] = 'closed';
                $shifts[$index]['ending_cash'] = $actualEndingCash;
                $shifts[$index]['expected_cash'] = $expected;
                $shifts[$index]['difference'] = round($actualEndingCash - $expected, 2);
                $shifts[$index]['closed_at'] = date('c');
                $shifts[$index]['closing_notes'] = $closingNotes;
                $closedShift = $shifts[$index];
                break;
            }
        }

        if ($closedShift) {
            $data['shifts'] = $shifts;
            $this->writeAll($data);
        }

        return $closedShift;
    }

    public function recordCashMovement(string $shiftId, string $type, float $amount, string $reason): array
    {
        $data = $this->readAll();
        $movements = $data['cash_movements'] ?? [];
        $shifts = $data['shifts'] ?? [];

        $movement = [
            'id' => 'mv_' . bin2hex(random_bytes(5)),
            'shift_id' => $shiftId,
            'type' => $type, // 'in' or 'out'
            'amount' => $amount,
            'reason' => $reason,
            'created_at' => date('c')
        ];
        $movements[] = $movement;
        $data['cash_movements'] = $movements;

        // Update shift totals
        foreach ($shifts as $index => $s) {
            if ($s['id'] === $shiftId) {
                if ($type === 'in') {
                    $shifts[$index]['cash_in'] = (float)($s['cash_in'] ?? 0) + $amount;
                } else {
                    $shifts[$index]['cash_out'] = (float)($s['cash_out'] ?? 0) + $amount;
                }
                break;
            }
        }
        $data['shifts'] = $shifts;
        $this->writeAll($data);

        return $movement;
    }

    public function getCashMovements(string $shiftId): array
    {
        $data = $this->readAll();
        $movements = $data['cash_movements'] ?? [];
        return array_values(array_filter($movements, fn($m) => $m['shift_id'] === $shiftId));
    }

    private function recordSaleToActiveShift(array $sale): void
    {
        $activeShift = $this->getActiveShift();
        if (!$activeShift) return;

        $data = $this->readAll();
        $shifts = $data['shifts'] ?? [];

        $method = strtolower($sale['payment_method'] ?? 'cash');
        $total = (float)($sale['total_amount'] ?? 0);

        foreach ($shifts as $index => $shift) {
            if ($shift['id'] === $activeShift['id']) {
                if ($method === 'cash') {
                    $shifts[$index]['cash_sales'] = (float)($shift['cash_sales'] ?? 0) + $total;
                } elseif ($method === 'card') {
                    $shifts[$index]['card_sales'] = (float)($shift['card_sales'] ?? 0) + $total;
                } elseif ($method === 'qr' || $method === 'wallet') {
                    $shifts[$index]['qr_sales'] = (float)($shift['qr_sales'] ?? 0) + $total;
                } else {
                    $shifts[$index]['other_sales'] = (float)($shift['other_sales'] ?? 0) + $total;
                }
                break;
            }
        }

        $data['shifts'] = $shifts;
        $this->writeAll($data);
    }

    // Parked / Held Sales
    public function getParkedSales(): array
    {
        $data = $this->readAll();
        return $data['parked_sales'] ?? [];
    }

    public function saveParkedSale(string $customerNote, array $items, float $subtotal): array
    {
        $data = $this->readAll();
        $parked = $data['parked_sales'] ?? [];

        $item = [
            'id' => 'parked_' . bin2hex(random_bytes(4)),
            'customer_note' => $customerNote ?: 'Held Order',
            'items' => $items,
            'subtotal' => $subtotal,
            'created_at' => date('c')
        ];

        $parked[] = $item;
        $data['parked_sales'] = $parked;
        $this->writeAll($data);

        return $item;
    }

    public function removeParkedSale(string $id): ?array
    {
        $data = $this->readAll();
        $parked = $data['parked_sales'] ?? [];
        $found = null;
        $newParked = [];

        foreach ($parked as $p) {
            if ($p['id'] === $id) {
                $found = $p;
            } else {
                $newParked[] = $p;
            }
        }

        $data['parked_sales'] = $newParked;
        $this->writeAll($data);
        return $found;
    }
}
