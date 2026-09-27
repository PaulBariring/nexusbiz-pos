<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class ShiftController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $settings = $this->db->getSettings();
        $activeShift = $this->db->getActiveShift();
        $recentShifts = $this->db->getShifts(25);
        $movements = $activeShift ? $this->db->getCashMovements($activeShift['id']) : [];

        $pageTitle = 'Shift & Cash Drawer Management';
        require __DIR__ . '/../../views/shifts/index.php';
    }

    public function open(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /shifts');
            exit;
        }

        $cashierName = trim($_POST['cashier_name'] ?? 'Main Cashier') ?: 'Main Cashier';
        $startingCash = (float)($_POST['starting_cash'] ?? 100.00);
        $notes = trim($_POST['notes'] ?? '');

        $this->db->openShift($cashierName, $startingCash, $notes);
        header('Location: /shifts?msg=Shift+opened+successfully');
        exit;
    }

    public function close(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /shifts');
            exit;
        }

        $shiftId = trim($_POST['shift_id'] ?? '');
        $actualEndingCash = (float)($_POST['actual_ending_cash'] ?? 0);
        $closingNotes = trim($_POST['closing_notes'] ?? '');

        if ($shiftId) {
            $this->db->closeShift($shiftId, $actualEndingCash, $closingNotes);
        }

        header('Location: /shifts?msg=Shift+reconciled+and+closed+successfully');
        exit;
    }

    public function cashMovement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /shifts');
            exit;
        }

        $shiftId = trim($_POST['shift_id'] ?? '');
        $type = trim($_POST['type'] ?? 'in'); // 'in' or 'out'
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($shiftId && $amount > 0 && $reason) {
            $this->db->recordCashMovement($shiftId, $type, $amount, $reason);
        }

        header('Location: /shifts?msg=Cash+movement+recorded');
        exit;
    }
}
