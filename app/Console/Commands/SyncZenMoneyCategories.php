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

            // Получаем все существующие категории
            $existingCategories = ExpenseCategory::pluck('id', 'code')->toArray();

            // Собираем все коды категорий из API
            $apiCodes = [];

            // Обновляем или создаем родительские категории
            foreach ($apiCategories as $folder) {
                $apiCodes[] = $folder['code'];

                ExpenseCategory::updateOrCreate(
                    ['code' => $folder['code']],
                    [
                        'name' => $folder['name'],
                        'type' => 'folder',
                        'parent_code' => null
                    ]
                );

                $this->info("Обработана папка: {$folder['name']}");

                // Обновляем или создаем дочерние категории
                foreach ($folder['children'] as $category) {
                    $apiCodes[] = $category['code'];

                    ExpenseCategory::updateOrCreate(
                        ['code' => $category['code']],
                        [
                            'name' => $category['name'],
                            'type' => 'category',
                            'parent_code' => $folder['code']
                        ]
                    );

                    $this->info("- Обработана категория: {$category['name']}");
                }
            }

            // Удаляем категории, которых больше нет в API
            $deletedCount = ExpenseCategory::whereNotIn('code', $apiCodes)->delete();
            if ($deletedCount > 0) {
                $this->info("Удалено устаревших категорий: {$deletedCount}");
            }

            DB::commit();

            $totalCategories = ExpenseCategory::count();
            $totalLinks = DB::table('telegram_chat_expense_category')->count();

            $this->info("Синхронизация успешно завершена!");
            $this->info("Всего категорий в базе: {$totalCategories}");
            $this->info("Активных связей с чатами: {$totalLinks}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при синхронизации категорий: ' . $e->getMessage());
            $this->error('Произошла ошибка при синхронизации: ' . $e->getMessage());
        }
    }
}
