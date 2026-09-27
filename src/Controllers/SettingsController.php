<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Helpers\Format;

class SettingsController
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $settings = $this->db->getSettings();
        $isSupabase = $this->db->isUsingSupabase();
        $supabaseService = $this->db->getSupabaseService();
        $supabaseConfigured = $supabaseService->isConfigured();
        $supabaseStatus = $supabaseConfigured ? $supabaseService->testConnection() : ['success' => false, 'message' => 'Supabase URL/Key not set'];

        $pageTitle = 'Business Settings & Presets';
        require __DIR__ . '/../../views/settings/index.php';
    }

    public function saveProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings');
            exit;
        }

        $businessName = trim($_POST['business_name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $businessType = trim($_POST['business_type'] ?? 'retail');
        $taxId = trim($_POST['tax_id'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currencySymbol = trim($_POST['currency_symbol'] ?? '$') ?: '$';
        $taxName = trim($_POST['tax_name'] ?? 'Sales Tax');
        $taxRate = (float)($_POST['tax_rate'] ?? 0);
        $taxInclusive = isset($_POST['tax_inclusive']) && $_POST['tax_inclusive'] === '1';
        $receiptHeader = trim($_POST['receipt_header'] ?? '');
        $receiptFooter = trim($_POST['receipt_footer'] ?? '');

        $dataToSave = [
            'business_name' => $businessName,
            'tagline' => $tagline,
            'business_type' => $businessType,
            'tax_id' => $taxId,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'currency_symbol' => $currencySymbol,
            'tax_name' => $taxName,
            'tax_rate' => $taxRate,
            'tax_inclusive' => $taxInclusive,
            'receipt_header' => $receiptHeader,
            'receipt_footer' => $receiptFooter
        ];

        $this->db->saveSettings($dataToSave);
        header('Location: /settings?msg=Business+profile+updated+successfully');
        exit;
    }

    public function applyPreset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings');
            exit;
        }

        $preset = trim($_POST['preset'] ?? 'retail');

        $presets = [
            'retail' => [
                'business_type' => 'retail',
                'dynamic_fields' => ['Brand', 'SKU/Model', 'Supplier', 'Warranty']
            ],
            'apparel' => [
                'business_type' => 'apparel',
                'dynamic_fields' => ['Size (XS/S/M/L/XL)', 'Color', 'Material', 'Gender / Department', 'Season']
            ],
            'grocery' => [
                'business_type' => 'grocery',
                'dynamic_fields' => ['Unit of Measure (kg/pcs/box)', 'Batch / Lot Number', 'Expiration Date', 'Brand']
            ],
            'cafe' => [
                'business_type' => 'cafe',
                'dynamic_fields' => ['Serving Size (Regular/Large)', 'Sugar / Sweetness Level', 'Temperature (Hot/Iced)', 'Kitchen Station']
            ],
            'repair' => [
                'business_type' => 'repair',
                'dynamic_fields' => ['Serial Number / IMEI', 'Device Model', 'Warranty Coverage', 'Technician']
            ]
        ];

        if (isset($presets[$preset])) {
            $this->db->saveSettings($presets[$preset]);
        }

        header('Location: /settings?tab=preset&msg=Preset+' . urlencode($preset) . '+applied+successfully');
        exit;
    }

    public function addDynamicField(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings');
            exit;
        }

        $fieldName = trim($_POST['field_name'] ?? '');
        if ($fieldName) {
            $settings = $this->db->getSettings();
            $fields = $settings['dynamic_fields'] ?? [];
            if (!in_array($fieldName, $fields)) {
                $fields[] = $fieldName;
                $this->db->saveSettings(['dynamic_fields' => array_values($fields)]);
            }
        }

        header('Location: /settings?tab=fields&msg=Custom+field+added');
        exit;
    }

    public function removeDynamicField(): void
    {
        $fieldName = trim($_POST['field_name'] ?? $_GET['field_name'] ?? '');
        if ($fieldName) {
            $settings = $this->db->getSettings();
            $fields = $settings['dynamic_fields'] ?? [];
            $fields = array_values(array_filter($fields, fn($f) => $f !== $fieldName));
            $this->db->saveSettings(['dynamic_fields' => $fields]);
        }

        header('Location: /settings?tab=fields&msg=Custom+field+removed');
        exit;
    }

    public function saveSupabaseConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings');
            exit;
        }

        $url = trim($_POST['supabase_url'] ?? '');
        $key = trim($_POST['supabase_key'] ?? '');

        // Update .env file
        $envPath = __DIR__ . '/../../.env';
        $envContent = "# Supabase Cloud Configuration\nSUPABASE_URL={$url}\nSUPABASE_KEY={$key}\n";
        file_put_contents($envPath, $envContent);

        putenv("SUPABASE_URL={$url}");
        putenv("SUPABASE_KEY={$key}");

        header('Location: /settings?tab=supabase&msg=Supabase+credentials+saved');
        exit;
    }

    public function syncToSupabase(): void
    {
        $res = $this->db->syncLocalToSupabase();
        $msg = $res['success'] ? 'Data synced to Supabase successfully!' : ('Sync failed: ' . $res['message']);
        header('Location: /settings?tab=supabase&msg=' . urlencode($msg));
        exit;
    }
}
