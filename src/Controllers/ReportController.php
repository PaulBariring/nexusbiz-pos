<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class ReportController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $settings = $this->db->getSettings();
        $allSales = $this->db->getSales(500);

        $filter = $_GET['range'] ?? 'today'; // today, week, month, all
        $now = time();

        $filteredSales = array_filter($allSales, function ($sale) use ($filter, $now) {
            $created = strtotime($sale['created_at'] ?? '');
            if (!$created) return true;

            return match ($filter) {
                'today' => date('Y-m-d', $created) === date('Y-m-d', $now),
                'week'  => ($now - $created) <= (7 * 86400),
                'month' => date('Y-m', $created) === date('Y-m', $now),
                default => true,
            };
        });

        // Compute metrics
        $totalRevenue = 0.0;
        $totalProfit = 0.0;
        $totalDiscounts = 0.0;
        $totalTax = 0.0;
        $salesCount = count($filteredSales);

        $paymentMethods = [
            'cash' => 0.0,
            'card' => 0.0,
            'qr'   => 0.0,
            'other'=> 0.0
        ];

        $productSales = [];

        foreach ($filteredSales as $sale) {
            $rev = (float)($sale['total_amount'] ?? 0);
            $totalRevenue += $rev;
            $totalProfit += (float)($sale['gross_profit'] ?? 0);
            $totalDiscounts += (float)($sale['discount_amount'] ?? 0);
            $totalTax += (float)($sale['tax_amount'] ?? 0);

            $method = strtolower($sale['payment_method'] ?? 'cash');
            if (isset($paymentMethods[$method])) {
                $paymentMethods[$method] += $rev;
            } else {
                $paymentMethods['other'] += $rev;
            }

            if (!empty($sale['items']) && is_array($sale['items'])) {
                foreach ($sale['items'] as $item) {
                    $pName = $item['product_name'] ?? 'Unknown Item';
                    $qty = (int)($item['quantity'] ?? 1);
                    $sub = (float)($item['subtotal'] ?? 0);

                    if (!isset($productSales[$pName])) {
                        $productSales[$pName] = ['name' => $pName, 'qty' => 0, 'revenue' => 0.0];
                    }
                    $productSales[$pName]['qty'] += $qty;
                    $productSales[$pName]['revenue'] += $sub;
                }
            }
        }

        // Sort top products by units sold
        usort($productSales, fn($a, $b) => $b['qty'] <=> $a['qty']);
        $topProducts = array_slice($productSales, 0, 10);

        $avgOrderValue = $salesCount > 0 ? round($totalRevenue / $salesCount, 2) : 0.0;

        $pageTitle = 'Sales & Financial Reports';
        require __DIR__ . '/../../views/reports/index.php';
    }
}
