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
                    <form method="POST" action="{{ route('admin.chats.store') }}" class="space-y-6">
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
                            <x-input-label for="zenmoney_account" value="ZenMoney Account" />
                            <select id="zenmoney_account" name="zenmoney_account" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach($zenmoneyAccounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['name'] }} ({{ $account['balance'] }} {{ $account['currency'] }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('zenmoney_account')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="transit_account" value="Transit Account" />
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
                                    <x-expense-category-folder :folder="$folder" />
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('expense_categories')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Обработка сворачивания/разворачивания папок
            document.querySelectorAll('[data-folder-code]').forEach(button => {
                button.addEventListener('click', function() {
                    const folderCode = this.dataset.folderCode;
                    const content = document.getElementById(`folder-content-${folderCode}`);
                    const icon = document.getElementById(`folder-icon-${folderCode}`);

                    content.classList.toggle('hidden');
                    icon.classList.toggle('rotate-90');
                });
            });

            // Валидация формы
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const checkedBoxes = document.querySelectorAll('input[name="expense_categories[]"]:checked');
                if (checkedBoxes.length === 0) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите хотя бы одну категорию расходов');
                }
            });
        });
    </script>
    @endpush
</x-admin-layout>
