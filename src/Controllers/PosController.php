<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class PosController
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
        $activeShift = $this->db->getActiveShift();
        $parkedSales = $this->db->getParkedSales();

        $pageTitle = 'Point of Sale (POS)';
        require __DIR__ . '/../../views/pos/index.php';
    }

    public function receipt(): void
    {
        $saleId = trim($_GET['id'] ?? '');
        $sale = $this->db->getSaleById($saleId);

        if (!$sale) {
            header('Location: /pos?error=Sale+not+found');
            exit;
        }

        $settings = $this->db->getSettings();
        require __DIR__ . '/../../views/pos/receipt.php';
    }
}
