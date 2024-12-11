<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class ZenMoneyService
{
    protected string $baseUrl = 'https://api.zenmoney.ru/v8';
    protected string $token;
    protected $userId;

    public function __construct()
    {
        $token = config('services.zenmoney.token');

        if (!$token) {
            throw new \RuntimeException('ZenMoney API token not configured. Please check ZENMONEY_API_TOKEN in .env');
        }

        $this->token = $token;
    }

    public function getAccounts(): array
    {
        try {


            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0,
                'account' => []
            ]);



            if (!$response->successful()) {

                throw new \Exception("Failed to fetch data from ZenMoney API: HTTP {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['account']) || empty($data['account'])) {

                return [];
            }



            return $this->formatAccounts($data['account']);
        } catch (\Exception $e) {

            throw $e;
        }
    }

    protected function formatAccounts(array $accounts): array
    {


        $formatted = [];

        foreach ($accounts as $account) {
            if (!empty($account['deleted']) || !empty($account['archive'])) {
                continue;
            }

            $formatted[] = [
                'id' => $account['id'],
                'code_zenmoney_account' => $account['id'],
                'name' => $account['title'],
                'balance' => $account['balance'] ?? 0,
                'currency' => $account['instrument'] ?? 'RUB',
                'type' => $account['type'] ?? 'checking',
                'sync_ID' => $account['syncID'] ?? null,
                'enabled' => !($account['archive'] ?? false)
            ];
        }

        usort($formatted, fn($a, $b) => strcmp($a['name'], $b['name']));



        return $formatted;
    }

    public function getCategories(): array
    {
        try {

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0
            ]);

            $this->handleErrors($response);

            $data = $response->json();



            return $this->formatCategories($data['tag'] ?? []);
        } catch (\Exception $e) {

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
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/transaction", [
                'transaction' => [
                    'created' => time(),
                    'income' => $data['income'],
                    'outcome' => $data['outcome'],
                    'outcomeAccount' => $data['outcomeAccount'],
                    'tag' => $data['tag'],
                    'comment' => $data['comment'],
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to create transaction: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->$method("{$this->baseUrl}/{$endpoint}", $data);

            if (!$response->successful()) {

                throw new \Exception('Failed to fetch data from ZenMoney API: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function formatCategories(array $categories): array
    {

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

        return $response->json();
    }

    protected function handleErrors(Response $response): void
    {
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch data from ZenMoney API: ' . $response->status());
        }
    }

    public function refreshAccounts(): array
    {
        try {


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

                throw new \Exception("Failed to refresh ZenMoney accounts: HTTP {$response->status()} - " . $response->body());
            }

            $data = $response->json();
            return $this->formatAccounts($data['account'] ?? []);
        } catch (\Exception $e) {

            throw $e;
        }
    }

    public function updateCategories()
    {
        try {
            // Получаем категории из API
            $categories = $this->getCategories();

            // Начинаем транзацию
            DB::beginTransaction();

            try {
                // Сохраняем существующие связи с кодами категорий
                $existingLinks = DB::table('telegram_chat_expense_category')
                    ->join('expense_categories', 'expense_categories.id', '=', 'telegram_chat_expense_category.expense_category_id')
                    ->select('telegram_chat_expense_category.*', 'expense_categories.code as category_code')
                    ->get();

                // Отключаем внешний ключ временно
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Очищаем старые категории
                DB::table('expense_categories')->truncate();

                // Включаем обратно внешний ключ
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                // Добавляем родительские категории и сохраняем их новые ID
                $codeToIdMap = [];

                foreach ($categories as $parentCategory) {
                    $id = DB::table('expense_categories')->insertGetId([
                        'code' => $parentCategory['code'],
                        'name' => $parentCategory['name'],
                        'type' => $parentCategory['type'],
                        'parent_code' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $codeToIdMap[$parentCategory['code']] = $id;

                    // Добавляем дочерние категории
                    foreach ($parentCategory['children'] as $child) {
                        $childId = DB::table('expense_categories')->insertGetId([
                            'code' => $child['code'],
                            'name' => $child['name'],
                            'type' => $child['type'],
                            'parent_code' => $parentCategory['code'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $codeToIdMap[$child['code']] = $childId;
                    }
                }

                // Очища��м старые связи
                DB::table('telegram_chat_expense_category')->truncate();

                // Восстанавливаем связи используя сохраненные коды категорий
                foreach ($existingLinks as $link) {
                    if (isset($codeToIdMap[$link->category_code])) {
                        DB::table('telegram_chat_expense_category')->insert([
                            'telegram_chat_id' => $link->telegram_chat_id,
                            'expense_category_id' => $codeToIdMap[$link->category_code],
                            'created_at' => $link->created_at ?? now(),
                            'updated_at' => $link->updated_at ?? now()
                        ]);
                    }
                }

                DB::commit();
                Log::info('Категории успешно обновлены. Восстановлено связей: ' . count($existingLinks));

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Ошибка при обновлении категорий: ' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при получении категорий из API: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createExpenseTransaction($accountId, $amount, $comment = '', $category = null, $merchantId = null)
    {
        try {
            // Получаем информацию о счете для определения валюты
            $accountInfo = $this->getAccountInfo($accountId);
            if (!$accountInfo) {
                throw new \Exception("Account not found: {$accountId}");
            }

            $tags = [];
            if ($category) {
                $tags = is_array($category) ? $category : [$category];
                $tags = array_map('strval', $tags);
            }

            Log::info('Creating ZenMoney transaction', [
                'amount' => $amount,
                'comment' => $comment,
                'tags' => $tags
            ]);

            // Формируем транзакцию согласно спецификации ZenMoney API
            $transaction = [
                'id' => Str::uuid()->toString(),
                'created' => time(),
                'changed' => time(),
                'user' => $this->getUserId(),
                'deleted' => false,
                'payee' => null,
                'reminderMarker' => null,
                'incomeBankID' => null,
                'outcomeBankID' => null,
                'opIncome' => null,
                'opIncomeInstrument' => null,
                'opOutcome' => null,
                'opOutcomeInstrument' => null,
                'latitude' => null,
                'longitude' => null,

                // Расходная часть
                'outcomeAccount' => (string)$accountId,
                'outcomeInstrument' => $accountInfo['instrument'] ?? 2,
                'outcome' => (float)$amount,

                // Приходная часть (для расхода - нулевая)
                'incomeAccount' => (string)$accountId,
                'incomeInstrument' => $accountInfo['instrument'] ?? 2,
                'income' => 0,

                // Дополнительная информация
                'date' => date('Y-m-d'),
                'hold' => false,

                // Merchant должен быть строкой
                'merchant' => null // Используйте реальный идентификатор мерчанта
            ];

            // Добавляем опциональные поля только если они заполнены
            if (!empty($tags)) {
                $transaction['tag'] = $tags;
            }

            if (!empty($comment)) {
                $transaction['comment'] = $comment;
            }

            // Добавляем dd для отладки
            // dd([
            //     'transaction' => $transaction,
            //     'request_body' => [
            //         'currentClientTimestamp' => time(),
            //         'serverTimestamp' => 0,
            //         'transaction' => [$transaction]
            //     ],
            //     'merchant_type' => gettype($transaction['merchant']),
            //     'merchant_value' => $transaction['merchant']
            // ]);

            Log::info('Sending transaction to ZenMoney', ['transaction' => $transaction]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'serverTimestamp' => 0,
                'transaction' => [$transaction]
            ]);

            if (!$response->successful()) {
                Log::error('ZenMoney API error', [
                    'error' => $response->body(),
                    'request_data' => $transaction
                ]);
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error creating expense: ' . $e->getMessage(), [
                'account_id' => $accountId,
                'amount' => $amount,
                'comment' => $comment,
                'category' => $category
            ]);
            throw $e;
        }
    }

    // Метод для проверки корректности UUID
    protected function isValidUUID($uuid): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid) === 1;
    }

    // Добавьте новый вспомогательный метод для получения информации о счете
    protected function getAccountInfo($accountId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get account info');
            }

            $data = $response->json();

            foreach ($data['account'] ?? [] as $account) {
                if ($account['id'] === $accountId) {
                    return [
                        'id' => $account['id'],
                        'instrument' => $account['instrument'] ?? 2, // По умолчанию RUB
                        'type' => $account['type'] ?? 'checking'
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error getting account info: ' . $e->getMessage());
            throw $e;
        }
    }

    // Метод для получения идентификатора тега ZenMoney
    protected function getZenMoneyTagId($localCategoryId)
    {
        // Здесь вы должны реализовать логику получения ZenMoney Tag ID из вашей базы данных
        // Например, запрос к таблице expense_categories, чтобы получить соответствующий ZenMoney Tag ID

        // Пример:
        $category = DB::table('expense_categories')
            ->where('code', $localCategoryId)
            ->first();

        return $category ? $category->zenmoney_tag_id : null;
    }

    // Метод для получения User ID
    protected function getUserId()
    {
        // Проверяем, есь ли уже закешированный User ID
        if (isset($this->userId)) {
            return $this->userId;
        }

        try {
            // Получаем данные пользователя и ZenMoney API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Извлекаем User ID из полученных данных
                $user = $data['user'][0] ?? null;
                if ($user && isset($user['id'])) {
                    $this->userId = $user['id']; // Кешируем User ID
                    return $this->userId;
                }
            }

            throw new \Exception('Unable to retrieve User ID from ZenMoney API');
        } catch (\Exception $e) {
            Log::error('Error fetching User ID: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountBalance($accountId)
    {
        try {
            Log::info("Getting account balance", ['account_id' => $accountId]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'lastServerTimestamp' => 0
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get balance', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get balance: ' . $response->body());
            }

            $data = $response->json();

            // Find the account in the response
            foreach ($data['account'] ?? [] as $account) {
                if ($account['id'] === $accountId) {
                    Log::info("Balance found", [
                        'account_id' => $accountId,
                        'balance' => $account['balance']
                    ]);
                    return $account['balance'];
                }
            }

            Log::warning("Account not found", ['account_id' => $accountId]);
            return null;

        } catch (\Exception $e) {
            Log::error('Balance check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function createIncomeTransaction($accountId, $amount, $comment = '', $transitAccountId = null)
    {
        try {
            $accountInfo = $this->getAccountInfo($accountId);
            if (!$accountInfo) {
                throw new \Exception("Account not found: {$accountId}");
            }

            if (!$transitAccountId) {
                throw new \Exception("Transit account not specified");
            }

            // Создаем транзакцию перевода между счетами
            $transaction = [
                'id' => Str::uuid()->toString(),
                'created' => time(),
                'changed' => time(),
                'user' => $this->getUserId(),
                'deleted' => false,

                // Приходная часть (транзитный счет)
                'incomeAccount' => (string)$accountId,
                'incomeInstrument' => $accountInfo['instrument'] ?? 2,
                'income' => (float)$amount,

                // Расходная часть (основной счет)
                'outcomeAccount' => (string)$transitAccountId,
                'outcomeInstrument' => $accountInfo['instrument'] ?? 2,
                'outcome' => (float)$amount,

                // Дополнительная информация
                'date' => date('Y-m-d'),
                'comment' => $comment ?: 'Перевод на транзитный счет',

                // Изменяем формат тега для доходов
                'tag' => null,
                'merchant' => null,
                'payee' => null,
                'reminderMarker' => null,
                'incomeBankID' => null,
                'outcomeBankID' => null,
                'opIncome' => null,
                'opIncomeInstrument' => null,
                'opOutcome' => null,
                'opOutcomeInstrument' => null,
                'latitude' => null,
                'longitude' => null,

            ];
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/diff", [
                'currentClientTimestamp' => time(),
                'serverTimestamp' => 0,
                'transaction' => [$transaction]
            ]);

            if (!$response->successful()) {
                Log::error('ZenMoney API error', [
                    'error' => $response->body(),
                    'request_data' => $transaction
                ]);
                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error creating transfer: ' . $e->getMessage(), [
                'account_id' => $accountId,
                'transit_account_id' => $transitAccountId,
                'amount' => $amount,
                'comment' => $comment
            ]);
            throw $e;
        }
    }
}
