<div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl mb-4">Детали чата</h2>
    <div class="space-y-4">
        <div>
            <p class="font-bold">Название:</p>
            <p>{{ $chat->name }}</p>
        </div>
        <div>
            <p class="font-bold">Telegram ID:</p>
            <p>{{ $chat->telegram_chat_id }}</p>
        </div>
        <div>
            <p class="font-bold">Создан:</p>
            <p>{{ $chat->created_at->format('d.m.Y H:i') }}</p>
        </div>
        <div>
            <p class="font-bold">Обновлен:</p>
            <p>{{ $chat->updated_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>
</div>
