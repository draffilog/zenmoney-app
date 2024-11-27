<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\TelegramChat;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::beginTransaction();

            // Получаем чат
            $chat = TelegramChat::find(3); // или какой у вас ID чата

            if ($chat) {
                \Log::info('Found chat:', ['chat_id' => $chat->id]);

                // Получаем ID категорий
                $categories = ExpenseCategory::whereIn('code', [
                    'ip_veselov',
                    'bugrovka94',
                    'apartment',
                    'food'
                ])->get();

                \Log::info('Found categories:', ['categories' => $categories->pluck('id')->toArray()]);

                if ($categories->isNotEmpty()) {
                    // Создаем записи в связующей таблице
                    $data = $categories->map(function($category) use ($chat) {
                        return [
                            'telegram_chat_id' => $chat->id,
                            'expense_category_id' => $category->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    DB::table('telegram_chat_expense_category')->insert($data);
                    \Log::info('Inserted records:', ['count' => count($data)]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function down(): void
    {
        if ($chat = TelegramChat::find(3)) {
            $chat->expenseCategories()->detach();
        }
    }
};
