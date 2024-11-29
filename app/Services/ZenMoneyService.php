<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ZenMoneyService
{
    protected string $baseUrl = 'https://api.zenmoney.ru/v8';
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.zenmoney.token');
    }

    public function getAccounts(): array
    {
        try {
            \Log::info('Attempting to fetch ZenMoney accounts');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0,
                'account' => []
            ]);

            \Log::debug('ZenMoney API response status:', [
                'status' => $response->status(),
                'account_count' => count($response->json()['account'] ?? [])
            ]);

            if (!$response->successful()) {
                \Log::error('ZenMoney API error', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                throw new \Exception("Failed to fetch data from ZenMoney API: HTTP {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['account']) || empty($data['account'])) {
                \Log::warning('ZenMoney API returned no accounts');
                return [];
            }

            \Log::info('Successfully fetched ZenMoney accounts', [
                'accounts_count' => count($data['account'])
            ]);

            return $this->formatAccounts($data['account']);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch ZenMoney accounts', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function formatAccounts(array $accounts): array
    {
        \Log::debug('Formatting accounts', [
            'input_accounts_count' => count($accounts)
        ]);

        $formatted = [];

        foreach ($accounts as $account) {
            if (!empty($account['deleted']) || !empty($account['archive'])) {
                continue;
            }

            $formatted[] = [
                'id' => $account['id'],
                'name' => $account['title'],
                'balance' => $account['balance'] ?? 0,
                'currency' => $account['instrument'] ?? 'RUB',
                'type' => $account['type'] ?? 'checking',
                'sync_ID' => $account['syncID'] ?? null,
                'enabled' => !($account['archive'] ?? false)
            ];
        }

        usort($formatted, fn($a, $b) => strcmp($a['name'], $b['name']));

        \Log::debug('Accounts formatted', [
            'output_accounts_count' => count($formatted)
        ]);

        return $formatted;
    }

    public function getCategories(): array
    {
        try {
            \Log::info('Attempting to fetch ZenMoney categories');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0,
                'tag' => []
            ]);

            \Log::debug('ZenMoney API raw response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                \Log::error('ZenMoney API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token_length' => strlen($this->token),
                    'url' => "{$this->baseUrl}/diff"
                ]);
                throw new \Exception("Failed to fetch data from ZenMoney API: HTTP {$response->status()} - " . $response->body());
            }

            $data = $response->json();

            if (!isset($data['tag']) || empty($data['tag'])) {
                \Log::warning('ZenMoney API returned no categories', [
                    'response_data' => $data
                ]);
                return [];
            }

            \Log::info('Successfully fetched ZenMoney categories', [
                'categories_count' => count($data['tag'])
            ]);

            return $this->formatCategories($data['tag']);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch ZenMoney categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getBalance(string $accountId): float
    {
        $accounts = $this->getAccounts();
        $account = collect($accounts)->firstWhere('id', $accountId);
        return $account['balance'] ?? 0.0;
    }

    public function createTransaction(array $data): array
    {
        return $this->makeRequest('POST', 'transactions', $data);
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->$method("{$this->baseUrl}/{$endpoint}", $data);

            if (!$response->successful()) {
                \Log::error('ZenMoney API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch data from ZenMoney API: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('ZenMoney API request failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            throw $e;
        }
    }

    protected function formatCategories(array $categories): array
    {
        \Log::debug('Formatting categories', [
            'input_categories_count' => count($categories)
        ]);

        $formatted = [];
        $parentCategories = [];

        // First, find all parent categories (tags)
        foreach ($categories as $category) {
            if (empty($category['parent']) && ($category['showOutcome'] ?? true)) {
                $parentCategories[$category['id']] = [
                    'code' => $category['id'],
                    'name' => $category['title'],
                    'type' => 'folder',
                    'children' => []
                ];
            }
        }

        // Then, add child categories to their parents
        foreach ($categories as $category) {
            if (!empty($category['parent']) &&
                isset($parentCategories[$category['parent']]) &&
                ($category['showOutcome'] ?? true)) {
                $parentCategories[$category['parent']]['children'][] = [
                    'code' => $category['id'],
                    'name' => $category['title'],
                    'type' => 'category',
                    'parent_code' => $category['parent']
                ];
            }
        }

        // Convert to indexed array and sort by name
        $result = array_values($parentCategories);
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        foreach ($result as &$category) {
            usort($category['children'], fn($a, $b) => strcmp($a['name'], $b['name']));
        }

        \Log::debug('Categories formatted', [
            'output_categories_count' => count($result)
        ]);

        return $result;
    }

    public function getDiff($timestamp = 0): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/diff", [
            'currentClientTimestamp' => $timestamp ?: time(),
            'lastServerTimestamp' => 0
        ]);

        $this->handleErrors($response);

        // Log the raw response for debugging
        \Log::debug('ZenMoney Raw Response:', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers()
        ]);

        return $response->json();
    }

    protected function handleErrors(Response $response): void
    {
        if (!$response->successful()) {
            \Log::error('ZenMoney API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to fetch data from ZenMoney API: ' . $response->body());
        }
    }

    public function refreshAccounts(): array
    {
        try {
            \Log::info('Forcing refresh of ZenMoney accounts');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0,
                'forceFetch' => true,  // Добавляем флаг принудительного обновления
                'account' => []
            ]);

            if (!$response->successful()) {
                \Log::error('ZenMoney API error during refresh', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception("Failed to refresh ZenMoney accounts: HTTP {$response->status()} - " . $response->body());
            }

            $data = $response->json();
            return $this->formatAccounts($data['account'] ?? []);
        } catch (\Exception $e) {
            \Log::error('Failed to refresh ZenMoney accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
