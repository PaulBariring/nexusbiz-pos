<?php

namespace App\Helpers;

class Format
{
    public static function currency(float|int $amount, string $symbol = '$'): string
    {
        return $symbol . ' ' . number_format((float)$amount, 2);
    }

    public static function date(?string $dateStr, string $format = 'M d, Y h:i A'): string
    {
        if (!$dateStr) return 'N/A';
        $time = strtotime($dateStr);
        return $time ? date($format, $time) : 'N/A';
    }

    public static function generateId(string $prefix = 'id'): string
    {
        return $prefix . '_' . bin2hex(random_bytes(6));
    }

    public static function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    public static function generateBarcode(): string
    {
        return '89' . str_pad((string)random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    public static function escape(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
