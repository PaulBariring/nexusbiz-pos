<?php

namespace App\Services;

class SupabaseService
{
    private string $url;
    private string $key;

    public function __construct(string $url, string $key)
    {
        $this->url = rtrim($url, '/');
        $this->key = $key;
    }

    public function isConfigured(): bool
    {
        return !empty($this->url) && !empty($this->key);
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Supabase URL or API Key is missing.'];
        }

        $res = $this->request('GET', '/rest/v1/business_settings?select=id&limit=1');
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['success' => true, 'message' => 'Successfully connected to Supabase PostgreSQL!'];
        }

        return [
            'success' => false,
            'message' => 'Supabase connection failed (HTTP ' . $res['status'] . '): ' . ($res['data']['message'] ?? json_encode($res['data']))
        ];
    }

    public function request(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 0, 'data' => ['message' => 'Supabase not configured']];
        }

        $url = $this->url . $path;
        $ch = curl_init($url);

        $defaultHeaders = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $rawResponse = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return ['status' => 0, 'data' => ['message' => 'cURL Error: ' . $error]];
        }

        $decoded = json_decode($rawResponse, true);
        return [
            'status' => $status,
            'data' => $decoded ?? $rawResponse
        ];
    }

    public function select(string $table, string $query = 'select=*'): array
    {
        $res = $this->request('GET', "/rest/v1/{$table}?{$query}");
        return ($res['status'] >= 200 && $res['status'] < 300 && is_array($res['data'])) ? $res['data'] : [];
    }

    public function insert(string $table, array $data): ?array
    {
        $res = $this->request('POST', "/rest/v1/{$table}", $data);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return is_array($res['data']) && isset($res['data'][0]) ? $res['data'][0] : $res['data'];
        }
        return null;
    }

    public function update(string $table, string $idColumn, string $idValue, array $data): bool
    {
        $res = $this->request('PATCH', "/rest/v1/{$table}?{$idColumn}=eq.{$idValue}", $data);
        return ($res['status'] >= 200 && $res['status'] < 300);
    }

    public function delete(string $table, string $idColumn, string $idValue): bool
    {
        $res = $this->request('DELETE', "/rest/v1/{$table}?{$idColumn}=eq.{$idValue}");
        return ($res['status'] >= 200 && $res['status'] < 300);
    }
}
