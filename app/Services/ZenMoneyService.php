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
        $response = $this->makeRequest('GET', 'accounts');
        return $response['accounts'] ?? [];
    }

    public function getCategories(): array
    {
        $response = $this->makeRequest('GET', 'categories');
        return $this->formatCategories($response['categories'] ?? []);
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
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Content-Type' => 'application/json'
        ])->$method("{$this->baseUrl}/{$endpoint}", $data);

        $this->handleErrors($response);

        return $response->json();
    }

    protected function formatCategories(array $categories): array
    {
        $formatted = [];
        foreach ($categories as $category) {
            if (!$category['parent']) {
                $formatted[$category['id']] = [
                    'name' => $category['name'],
                    'subcategories' => []
                ];
            }
        }

        foreach ($categories as $category) {
            if ($category['parent'] && isset($formatted[$category['parent']])) {
                $formatted[$category['parent']]['subcategories'][] = [
                    'id' => $category['id'],
                    'name' => $category['name']
                ];
            }
        }

        return $formatted;
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
            $error = $response->json('error') ?? $response->body();
            \Log::error('ZenMoney API Error:', ['error' => $error]);
            throw new \Exception('Failed to connect to ZenMoney API: ' . $error);
        }
    }
}
