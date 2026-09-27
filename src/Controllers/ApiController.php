<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class ApiController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    private function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function searchProducts(): void
    {
        $query = strtolower(trim($_GET['q'] ?? ''));
        $categoryId = trim($_GET['category'] ?? '');
        $products = $this->db->getProducts();

        $filtered = array_filter($products, function ($p) use ($query, $categoryId) {
            if (!empty($p['is_active']) && $p['is_active'] === false) return false;
            if (!empty($categoryId) && ($p['category_id'] ?? '') !== $categoryId) return false;
            if (empty($query)) return true;

            $nameMatch = str_contains(strtolower($p['name'] ?? ''), $query);
            $barcodeMatch = str_contains(strtolower($p['barcode'] ?? ''), $query);
            $skuMatch = str_contains(strtolower($p['sku'] ?? ''), $query);
            return $nameMatch || $barcodeMatch || $skuMatch;
        });

        $this->json(array_values($filtered));
    }

    public function scanBarcode(): void
    {
        $barcode = trim($_GET['code'] ?? '');
        if (empty($barcode)) {
            $this->json(['success' => false, 'message' => 'Barcode is required.'], 400);
        }

        $product = $this->db->getProductByBarcode($barcode);
        if ($product) {
            $this->json(['success' => true, 'product' => $product]);
        } else {
            $this->json(['success' => false, 'message' => 'Product not found for barcode: ' . $barcode], 404);
        }
    }

    public function checkout(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['items'])) {
            $this->json(['success' => false, 'message' => 'Cart is empty or invalid data.'], 400);
        }

        $settings = $this->db->getSettings();
        $taxRate = (float)($settings['tax_rate'] ?? 0);
        $taxInclusive = (bool)($settings['tax_inclusive'] ?? false);

        $items = $input['items'];
        $subtotal = 0.0;
        $totalCost = 0.0;
        $processedItems = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? '';
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $product = $this->db->getProductById($productId);

            if ($product) {
                $unitPrice = (float)($product['selling_price'] ?? 0);
                $costPrice = (float)($product['cost_price'] ?? 0);
                $lineTotal = round($unitPrice * $qty, 2);

                $subtotal += $lineTotal;
                $totalCost += ($costPrice * $qty);

                $processedItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'barcode' => $product['barcode'] ?? '',
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'quantity' => $qty,
                    'subtotal' => $lineTotal,
                    'total' => $lineTotal,
                    'dynamic_attributes' => $product['dynamic_attributes'] ?? []
                ];
            }
        }

        if (empty($processedItems)) {
            $this->json(['success' => false, 'message' => 'No valid products in cart.'], 400);
        }

        // Discounts
        $discountAmount = max(0, (float)($input['discount_amount'] ?? 0));
        $discountType = $input['discount_type'] ?? 'flat';
        if ($discountType === 'percent') {
            $discountAmount = round(($subtotal * ($discountAmount / 100)), 2);
        }
        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        // Tax calculation
        if ($taxInclusive) {
            // Price already includes tax: tax = total - (total / (1 + rate))
            $taxAmount = round($discountedSubtotal - ($discountedSubtotal / (1 + ($taxRate / 100))), 2);
            $totalAmount = $discountedSubtotal;
        } else {
            // Tax added on top
            $taxAmount = round($discountedSubtotal * ($taxRate / 100), 2);
            $totalAmount = round($discountedSubtotal + $taxAmount, 2);
        }

        $paymentMethod = $input['payment_method'] ?? 'cash';
        $amountTendered = (float)($input['amount_tendered'] ?? $totalAmount);
        $changeAmount = max(0, round($amountTendered - $totalAmount, 2));

        $activeShift = $this->db->getActiveShift();

        $saleData = [
            'invoice_number' => Format::generateInvoiceNumber(),
            'shift_id' => $activeShift['id'] ?? null,
            'cashier_name' => $activeShift['cashier_name'] ?? 'Main Cashier',
            'customer_name' => trim($input['customer_name'] ?? '') ?: 'Walk-in Customer',
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_type' => $discountType,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'gross_profit' => round($totalAmount - $taxAmount - $totalCost, 2),
            'payment_method' => $paymentMethod,
            'payment_details' => $input['payment_details'] ?? [],
            'amount_tendered' => $amountTendered,
            'change_amount' => $changeAmount,
            'status' => 'completed',
            'items' => $processedItems
        ];

        $savedSale = $this->db->createSale($saleData);

        // If this sale was from a parked order, remove the parked order
        if (!empty($input['parked_id'])) {
            $this->db->removeParkedSale($input['parked_id']);
        }

        $this->json([
            'success' => true,
            'message' => 'Sale completed successfully!',
            'sale' => $savedSale
        ]);
    }

    public function parkSale(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['items'])) {
            $this->json(['success' => false, 'message' => 'Cannot park an empty cart.'], 400);
        }

        $note = trim($input['customer_note'] ?? 'Order #' . substr(uniqid(), -4));
        $items = $input['items'];
        $subtotal = (float)($input['subtotal'] ?? 0);

        $parked = $this->db->saveParkedSale($note, $items, $subtotal);
        $this->json(['success' => true, 'message' => 'Order parked successfully!', 'parked' => $parked]);
    }

    public function getParkedSales(): void
    {
        $parked = $this->db->getParkedSales();
        $this->json(array_values($parked));
    }

    public function resumeParkedSale(): void
    {
        $id = trim($_GET['id'] ?? '');
        $parked = $this->db->removeParkedSale($id);

        if ($parked) {
            $this->json(['success' => true, 'parked' => $parked]);
        } else {
            $this->json(['success' => false, 'message' => 'Parked order not found.'], 404);
        }
    }

    public function deleteParkedSale(): void
    {
        $id = trim($_GET['id'] ?? '');
        $removed = $this->db->removeParkedSale($id);
        $this->json(['success' => (bool)$removed]);
    }

    public function testSupabase(): void
    {
        $result = $this->db->getSupabaseService()->testConnection();
        $this->json($result);
    }
}
