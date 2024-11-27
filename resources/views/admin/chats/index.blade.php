<div class="w-full bg-white border-b border-gray-200">
    <div class="mb-4 px-4 py-4">
        <a href="{{ route('admin.chats.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Добавить чат
        </a>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="w-full table-fixed divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="w-1/3 px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Название
                    </th>
                    <th class="w-1/3 px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Telegram ID
                    </th>
                    <th class="w-1/3 px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Действия
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($chats as $chat)
                    <tr>
                        <td class="px-6 py-4 text-sm">{{ $chat->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $chat->telegram_chat_id }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('admin.chats.show', $chat) }}" class="text-blue-600 hover:text-blue-900 mr-2">
                                Просмотреть
                            </a>
                            <a href="{{ route('admin.chats.edit', $chat) }}" class="text-green-600 hover:text-green-900 mr-2">
                                Редактировать
                            </a>
                            <form action="{{ route('admin.chats.destroy', $chat) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Вы уверены?')">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
