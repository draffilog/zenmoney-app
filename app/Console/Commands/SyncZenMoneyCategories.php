<?php

namespace App\Console\Commands;

use App\Models\ExpenseCategory;
use App\Services\ZenMoneyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncZenMoneyCategories extends Command
{
    protected $signature = 'zenmoney:sync-categories';
    protected $description = 'Синхронизация категорий расходов из ZenMoney API в базу данных';

    public function handle(ZenMoneyService $zenMoneyService)
    {
        $this->info('Начинаем синхронизацию категорий из ZenMoney...');

        try {
            DB::beginTransaction();

            // Получаем категории из API
            $apiCategories = $zenMoneyService->getCategories();

            $this->info('Получено категорий из API: ' . count($apiCategories));

            // Очищаем существующие категории
            DB::statement('TRUNCATE telegram_chat_expense_category CASCADE');
            DB::statement('TRUNCATE expense_categories CASCADE');

            $this->info('Существующие категории очищены');

            // Сначала добавляем родительские категории (папки)
            foreach ($apiCategories as $folder) {
                ExpenseCategory::create([
                    'code' => $folder['code'],
                    'name' => $folder['name'],
                    'type' => 'folder',
                    'parent_code' => null
                ]);

                $this->info("Добавлена папка: {$folder['name']}");

                // Добавляем дочерние категории
                foreach ($folder['children'] as $category) {
                    ExpenseCategory::create([
                        'code' => $category['code'],
                        'name' => $category['name'],
                        'type' => 'category',
                        'parent_code' => $folder['code']
                    ]);

                    $this->info("- Добавлена категория: {$category['name']}");
                }
            }

            DB::commit();

            $totalCategories = ExpenseCategory::count();
            $this->info("Синхронизация успешно завершена! Всего категорий в базе: {$totalCategories}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при синхронизации категорий: ' . $e->getMessage());
            $this->error('Произошла ошибка при синхронизации: ' . $e->getMessage());
        }
    }
}
