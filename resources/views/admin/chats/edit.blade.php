<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Редактировать чат') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(config('app.debug'))
                        <div class="mb-4 p-4 bg-gray-100 rounded">
                            <p>Debug Info:</p>
                            <pre>{{ print_r($chat->expenseCategories->pluck('code')->toArray(), true) }}</pre>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.chats.update', $chat) }}">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Название</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $chat->name) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mb-4">
                            <label for="telegram_chat_id" class="block text-sm font-medium text-gray-700">Telegram ID</label>
                            <input type="text" name="telegram_chat_id" id="telegram_chat_id"
                                   value="{{ old('telegram_chat_id', $chat->telegram_chat_id) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mb-4">
                            <label for="zenmoney_account" class="block text-sm font-medium text-gray-700">Счет ZenMoney</label>
                            <select name="zenmoney_account" id="zenmoney_account"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach($zenmoneyAccounts as $account)
                                    <option value="{{ $account->id }}"
                                            {{ old('zenmoney_account', $chat->zenmoney_account_id) == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="transit_account" class="block text-sm font-medium text-gray-700">Транзитный счет</label>
                            <select name="transit_account" id="transit_account"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach($zenmoneyAccounts as $account)
                                    <option value="{{ $account->id }}"
                                            {{ old('transit_account', $chat->transit_account_id) == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Категории расходов</label>
                            @foreach($expenseCategories as $folder)
                                <div class="mb-2">
                                    <button type="button"
                                            class="flex items-center w-full text-left cursor-pointer p-2 hover:bg-gray-50 rounded"
                                            id="folder-header-{{ $folder['code'] }}"
                                            data-folder-code="{{ $folder['code'] }}">
                                        <svg class="w-4 h-4 mr-2 transform transition-transform duration-200"
                                             id="folder-icon-{{ $folder['code'] }}"
                                             fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="font-medium text-gray-700">{{ $folder['name'] }}</span>
                                    </button>

                                    <div class="ml-6 space-y-2 hidden transition-all duration-200 ease-in-out"
                                         id="folder-content-{{ $folder['code'] }}">
                                        @foreach($folder['children'] as $category)
                                            <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                <input type="checkbox"
                                                       name="expense_categories[]"
                                                       value="{{ $category['code'] }}"
                                                       {{ in_array($category['code'], old('expense_categories', $chat->expenseCategories ? $chat->expenseCategories->pluck('code')->toArray() : [])) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                <span class="ml-2">{{ $category['name'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Script loaded');  // Проверка загрузки скрипта

        // Функция для разворачивания/сворачивания папки
        function initializeFolders() {
            console.log('Initializing folders');  // Отладка

            // Находим все кнопки папок
            const folderButtons = document.querySelectorAll('[data-folder-code]');
            console.log('Found folder buttons:', folderButtons.length);  // Отладка

            // Добавляем обработчики для каждой кнопки
            folderButtons.forEach(button => {
                const folderCode = button.dataset.folderCode;
                console.log('Setting up folder:', folderCode);  // Отладка

                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Folder clicked:', folderCode);  // Отладка

                    const content = document.getElementById(`folder-content-${folderCode}`);
                    const icon = document.getElementById(`folder-icon-${folderCode}`);

                    if (!content || !icon) {
                        console.error('Elements not found:', { content, icon });  // Отладка
                        return;
                    }

                    // Toggle visibility
                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        icon.style.transform = 'rotate(90deg)';
                    } else {
                        content.classList.add('hidden');
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            });
        }

        // Вызываем функцию инициализации при загрузке страницы
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeFolders);
        } else {
            initializeFolders();
        }
    </script>

</x-admin-layout>