<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ __('Add New Chat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.chats.store') }}" class="space-y-6" id="chatForm">
                        @csrf

                        <div>
                            <x-input-label for="name" value="Chat Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telegram_chat_id" value="Telegram Chat ID" />
                            <x-text-input id="telegram_chat_id" name="telegram_chat_id" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="zenmoney_account" value="Счет начисления (пользователя)" />
                            <select id="zenmoney_account" name="zenmoney_account" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach($zenmoneyAccounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['name'] }} ({{ $account['balance'] }} {{ $account['currency'] }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('zenmoney_account')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="transit_account" value="Счет списания (транзитный)" />
                            <select id="transit_account" name="transit_account" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach($zenmoneyAccounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['name'] }} ({{ $account['balance'] }} {{ $account['currency'] }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('transit_account')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Expense Categories" />
                            <div class="mt-2 space-y-1">
                                @foreach($expenseCategories as $folder)
                                    <div class="folder-group">
                                        <div class="flex items-center">
                                            <button type="button" class="folder-toggle" data-folder-code="{{ $folder['code'] }}">
                                                <svg class="w-4 h-4 transform transition-transform" id="folder-icon-{{ $folder['code'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <span class="ml-2">{{ $folder['name'] }}</span>
                                        </div>
                                        <div class="ml-6 mt-1 space-y-1" id="folder-content-{{ $folder['code'] }}">
                                            @foreach($folder['children'] as $category)
                                                <div class="flex items-center">
                                                    <input type="checkbox"
                                                           name="expense_categories[]"
                                                           value="{{ $category['code'] }}"
                                                           id="category-{{ $category['code'] }}"
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                    <label for="category-{{ $category['code'] }}" class="ml-2">
                                                        {{ $category['name'] }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('expense_categories')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('chatForm');

            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Предотвращаем стандартную отправку формы

                const checkedCategories = document.querySelectorAll('input[name="expense_categories[]"]:checked');

                if (checkedCategories.length === 0) {
                    alert('Пожалуйста, выберите хотя бы одну категорию расходов');
                    return;
                }

                console.log('Submitting form with categories:', Array.from(checkedCategories).map(cb => cb.value));

                // Если все в порядке, отправляем форму
                this.submit();
            });

            // Обработка сворачивания/разворачивания папок
            document.querySelectorAll('.folder-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const folderCode = this.dataset.folderCode;
                    const content = document.getElementById(`folder-content-${folderCode}`);
                    const icon = document.getElementById(`folder-icon-${folderCode}`);

                    content.classList.toggle('hidden');
                    icon.classList.toggle('rotate-90');
                });
            });
        });
    </script>
    @endpush
</x-admin-layout>
