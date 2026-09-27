<?php

namespace App\Services;

class DatabaseService
{
    private LocalStorageService $local;
    private SupabaseService $supabase;
    private bool $useSupabase = false;

    public function __construct(LocalStorageService $local, SupabaseService $supabase)
    {
        $this->local = $local;
        $this->supabase = $supabase;
        
        // Auto-detect if Supabase is configured
        if ($this->supabase->isConfigured()) {
            $this->useSupabase = true;
        }
    }

    public function isUsingSupabase(): bool
    {
        return $this->useSupabase;
    }

    public function getSupabaseService(): SupabaseService
    {
        return $this->supabase;
    }

    public function getLocalStorageService(): LocalStorageService
    {
        return $this->local;
    }

    // Settings
    public function getSettings(): array
    {
        if ($this->useSupabase) {
            $res = $this->supabase->select('business_settings', 'select=*&limit=1');
            if (!empty($res[0])) {
                $s = $res[0];
                if (is_string($s['dynamic_fields'] ?? null)) {
                    $s['dynamic_fields'] = json_decode($s['dynamic_fields'], true) ?: [];
                }
                return $s;
            }
        }
        return $this->local->getSettings();
    }

    public function saveSettings(array $settings): bool
    {
        $localSuccess = $this->local->saveSettings($settings);

        if ($this->useSupabase) {
            $supabaseSettings = $settings;
            if (isset($supabaseSettings['dynamic_fields']) && is_array($supabaseSettings['dynamic_fields'])) {
                // PostgREST accepts JSON objects or strings
            }
            $existing = $this->supabase->select('business_settings', 'select=id&limit=1');
            if (!empty($existing[0]['id'])) {
                $this->supabase->update('business_settings', 'id', $existing[0]['id'], $supabaseSettings);
            } else {
                $supabaseSettings['id'] = 'default';
                $this->supabase->insert('business_settings', $supabaseSettings);
            }
        }

        return $localSuccess;
    }

    // Categories
    public function getCategories(): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('categories', 'select=*&order=name.asc');
            if (is_array($data) && count($data) > 0) return $data;
        }
        return $this->local->getCategories();
    }

    public function saveCategory(array $category): array
    {
        $saved = $this->local->saveCategory($category);
        if ($this->useSupabase) {
            $this->supabase->insert('categories', $saved);
        }
        return $saved;
    }

    public function deleteCategory(string $id): bool
    {
        $localRes = $this->local->deleteCategory($id);
        if ($this->useSupabase) {
            $this->supabase->delete('categories', 'id', $id);
        }
        return $localRes;
    }

    // Products
    public function getProducts(): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('products', 'select=*&order=name.asc');
            if (is_array($data) && count($data) > 0) {
                foreach ($data as &$p) {
                    if (is_string($p['dynamic_attributes'] ?? null)) {
                        $p['dynamic_attributes'] = json_decode($p['dynamic_attributes'], true) ?: [];
                    }
                }
                return $data;
            }
        }
        return $this->local->getProducts();
    }

    public function getProductById(string $id): ?array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('products', "select=*&id=eq.{$id}&limit=1");
            if (!empty($data[0])) {
                $p = $data[0];
                if (is_string($p['dynamic_attributes'] ?? null)) {
                    $p['dynamic_attributes'] = json_decode($p['dynamic_attributes'], true) ?: [];
                }
                return $p;
            }
        }
        return $this->local->getProductById($id);
    }

    public function getProductByBarcode(string $barcode): ?array
    {
        if ($this->useSupabase) {
            $barcodeClean = urlencode(trim($barcode));
            $data = $this->supabase->select('products', "select=*&barcode=eq.{$barcodeClean}&limit=1");
            if (!empty($data[0])) {
                $p = $data[0];
                if (is_string($p['dynamic_attributes'] ?? null)) {
                    $p['dynamic_attributes'] = json_decode($p['dynamic_attributes'], true) ?: [];
                }
                return $p;
            }
        }
        return $this->local->getProductByBarcode($barcode);
    }

    public function saveProduct(array $product): array
    {
        $saved = $this->local->saveProduct($product);
        if ($this->useSupabase) {
            $existing = $this->supabase->select('products', "select=id&id=eq.{$saved['id']}&limit=1");
            if (!empty($existing[0])) {
                $this->supabase->update('products', 'id', $saved['id'], $saved);
            } else {
                $this->supabase->insert('products', $saved);
            }
        }
        return $saved;
    }

    public function deleteProduct(string $id): bool
    {
        $res = $this->local->deleteProduct($id);
        if ($this->useSupabase) {
            $this->supabase->delete('products', 'id', $id);
        }
        return $res;
    }

    // Stock Adjustments
    public function recordStockAdjustment(array $adjustment): array
    {
        $saved = $this->local->recordStockAdjustment($adjustment);
        if ($this->useSupabase) {
            $this->supabase->insert('stock_adjustments', $saved);
            // Update stock in Supabase
            $p = $this->getProductById($adjustment['product_id']);
            if ($p) {
                $this->supabase->update('products', 'id', $p['id'], ['stock_quantity' => $p['stock_quantity']]);
            }
        }
        return $saved;
    }

    public function getStockAdjustments(int $limit = 50): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('stock_adjustments', "select=*&order=created_at.desc&limit={$limit}");
            if (is_array($data) && count($data) > 0) return $data;
        }
        return $this->local->getStockAdjustments($limit);
    }

    // Sales
    public function getSales(int $limit = 100): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('sales', "select=*&order=created_at.desc&limit={$limit}");
            if (is_array($data) && count($data) > 0) {
                // Fetch items for each sale if needed or stored in JSON
                return $data;
            }
        }
        return $this->local->getSales($limit);
    }

    public function getSaleById(string $id): ?array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('sales', "select=*&id=eq.{$id}&limit=1");
            if (!empty($data[0])) {
                $sale = $data[0];
                $items = $this->supabase->select('sale_items', "select=*&sale_id=eq.{$id}");
                $sale['items'] = $items;
                return $sale;
            }
        }
        return $this->local->getSaleById($id);
    }

    public function createSale(array $saleData): array
    {
        $saved = $this->local->createSale($saleData);

        if ($this->useSupabase) {
            $saleHeader = $saved;
            $items = $saleHeader['items'] ?? [];
            unset($saleHeader['items']);

            $this->supabase->insert('sales', $saleHeader);

            foreach ($items as $item) {
                $item['id'] = 'item_' . bin2hex(random_bytes(5));
                $item['sale_id'] = $saved['id'];
                $this->supabase->insert('sale_items', $item);

                // Update product stock in Supabase
                if (!empty($item['product_id'])) {
                    $p = $this->local->getProductById($item['product_id']);
                    if ($p) {
                        $this->supabase->update('products', 'id', $item['product_id'], [
                            'stock_quantity' => $p['stock_quantity']
                        ]);
                    }
                }
            }
        }

        return $saved;
    }

    // Shifts
    public function getShifts(int $limit = 30): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('shifts', "select=*&order=opened_at.desc&limit={$limit}");
            if (is_array($data) && count($data) > 0) return $data;
        }
        return $this->local->getShifts($limit);
    }

    public function getActiveShift(): ?array
    {
        return $this->local->getActiveShift();
    }

    public function openShift(string $cashierName, float $startingCash, string $notes = ''): array
    {
        $saved = $this->local->openShift($cashierName, $startingCash, $notes);
        if ($this->useSupabase) {
            $this->supabase->insert('shifts', $saved);
        }
        return $saved;
    }

    public function closeShift(string $shiftId, float $actualEndingCash, string $closingNotes = ''): ?array
    {
        $saved = $this->local->closeShift($shiftId, $actualEndingCash, $closingNotes);
        if ($this->useSupabase && $saved) {
            $this->supabase->update('shifts', 'id', $shiftId, $saved);
        }
        return $saved;
    }

    public function recordCashMovement(string $shiftId, string $type, float $amount, string $reason): array
    {
        $saved = $this->local->recordCashMovement($shiftId, $type, $amount, $reason);
        if ($this->useSupabase) {
            $this->supabase->insert('cash_movements', $saved);
        }
        return $saved;
    }

    public function getCashMovements(string $shiftId): array
    {
        if ($this->useSupabase) {
            $data = $this->supabase->select('cash_movements', "select=*&shift_id=eq.{$shiftId}");
            if (is_array($data) && count($data) > 0) return $data;
        }
        return $this->local->getCashMovements($shiftId);
    }

    // Parked Sales
    public function getParkedSales(): array
    {
        return $this->local->getParkedSales();
    }

    public function saveParkedSale(string $customerNote, array $items, float $subtotal): array
    {
        return $this->local->saveParkedSale($customerNote, $items, $subtotal);
    }

    public function removeParkedSale(string $id): ?array
    {
        return $this->local->removeParkedSale($id);
    }

    // Sync all local data to Supabase
    public function syncLocalToSupabase(): array
    {
        if (!$this->supabase->isConfigured()) {
            return ['success' => false, 'message' => 'Supabase is not configured yet.'];
        }

        $allData = $this->local->readAll();

        // 1. Settings
        if (!empty($allData['business_settings'])) {
            $this->supabase->insert('business_settings', $allData['business_settings']);
        }

        // 2. Categories
        if (!empty($allData['categories'])) {
            foreach ($allData['categories'] as $c) {
                $this->supabase->insert('categories', $c);
            }
        }

        // 3. Products
        if (!empty($allData['products'])) {
            foreach ($allData['products'] as $p) {
                $this->supabase->insert('products', $p);
            }
        }

        return ['success' => true, 'message' => 'Local categories, products, and settings have been synced to Supabase!'];
    }
}
