<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class InventoryController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $settings = $this->db->getSettings();
        $categories = $this->db->getCategories();
        $products = $this->db->getProducts();
        $stockAdjustments = $this->db->getStockAdjustments(15);

        // Calculate inventory summary statistics
        $totalItems = count($products);
        $totalStockCount = 0;
        $totalStockValue = 0.0;
        $lowStockCount = 0;

        foreach ($products as $p) {
            $qty = (int)($p['stock_quantity'] ?? 0);
            $cost = (float)($p['cost_price'] ?? 0);
            $minAlert = (int)($p['min_stock_alert'] ?? 5);

            $totalStockCount += $qty;
            $totalStockValue += ($qty * $cost);
            if ($qty <= $minAlert) {
                $lowStockCount++;
            }
        }

        $pageTitle = 'Inventory Management';
        require __DIR__ . '/../../views/inventory/index.php';
    }

    public function saveProduct(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /inventory');
            exit;
        }

        $settings = $this->db->getSettings();
        $dynamicFields = $settings['dynamic_fields'] ?? [];

        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $categoryId = trim($_POST['category_id'] ?? '');
        $costPrice = (float)($_POST['cost_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
        $minStockAlert = (int)($_POST['min_stock_alert'] ?? 5);
        $unit = trim($_POST['unit'] ?? 'pcs');

        // Extract dynamic attribute inputs
        $dynamicAttributes = [];
        foreach ($dynamicFields as $field) {
            $key = is_array($field) ? ($field['name'] ?? '') : $field;
            if ($key && isset($_POST['dyn_' . md5($key)])) {
                $dynamicAttributes[$key] = trim($_POST['dyn_' . md5($key)]);
            }
        }

        $productData = [
            'id' => $id,
            'name' => $name,
            'barcode' => $barcode ?: Format::generateBarcode(),
            'sku' => $sku ?: 'SKU-' . strtoupper(substr(uniqid(), -5)),
            'category_id' => $categoryId,
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'stock_quantity' => $stockQuantity,
            'min_stock_alert' => $minStockAlert,
            'unit' => $unit,
            'dynamic_attributes' => $dynamicAttributes,
            'is_active' => true
        ];

        $this->db->saveProduct($productData);
        header('Location: /inventory?msg=Product+saved+successfully');
        exit;
    }

    public function deleteProduct(): void
    {
        $id = trim($_POST['id'] ?? $_GET['id'] ?? '');
        if ($id) {
            $this->db->deleteProduct($id);
        }
        header('Location: /inventory?msg=Product+deleted');
        exit;
    }

    public function adjustStock(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /inventory');
            exit;
        }

        $productId = trim($_POST['product_id'] ?? '');
        $type = trim($_POST['type'] ?? 'restock'); // restock, damage, expired, count_audit
        $quantityChange = (int)($_POST['quantity_change'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $adjustedBy = trim($_POST['adjusted_by'] ?? 'Store Manager');

        $product = $this->db->getProductById($productId);
        if ($product) {
            $prevStock = (int)($product['stock_quantity'] ?? 0);
            
            // If negative adjustment type (damage, expired), ensure sign is negative
            if (in_array($type, ['damage', 'expired']) && $quantityChange > 0) {
                $quantityChange = -$quantityChange;
            }

            $newStock = max(0, $prevStock + $quantityChange);

            $this->db->recordStockAdjustment([
                'product_id' => $productId,
                'product_name' => $product['name'],
                'type' => $type,
                'quantity_change' => $quantityChange,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'adjusted_by' => $adjustedBy
            ]);
        }

        header('Location: /inventory?msg=Stock+adjusted+successfully');
        exit;
    }

    public function saveCategory(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /inventory');
            exit;
        }

        $id = trim($_POST['category_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#0d6efd');
        $icon = trim($_POST['icon'] ?? 'bi-tag');
        $description = trim($_POST['description'] ?? '');

        if ($name) {
            $this->db->saveCategory([
                'id' => $id,
                'name' => $name,
                'color' => $color,
                'icon' => $icon,
                'description' => $description
            ]);
        }

        header('Location: /inventory?tab=categories&msg=Category+saved');
        exit;
    }

    public function deleteCategory(): void
    {
        $id = trim($_POST['id'] ?? $_GET['id'] ?? '');
        if ($id) {
            $this->db->deleteCategory($id);
        }
        header('Location: /inventory?tab=categories&msg=Category+deleted');
        exit;
    }
}
