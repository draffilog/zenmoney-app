<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ __('Просмотр чата') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="space-y-6">
                        <div>
                            <x-input-label value="Chat Name" />
                            <div class="mt-1 p-2 bg-gray-50 rounded">{{ $chat->name }}</div>
                        </div>

                        <div>
                            <x-input-label value="Telegram Chat ID" />
                            <div class="mt-1 p-2 bg-gray-50 rounded">{{ $chat->telegram_chat_id }}</div>
                        </div>

                        <div>
                            <x-input-label value="ZenMoney Account" />
                            <div class="mt-1 p-2 bg-gray-50 rounded">{{ $chat->zenmoneyAccount->name }}</div>
                        </div>

                        <div>
                            <x-input-label value="Transit Account" />
                            <div class="mt-1 p-2 bg-gray-50 rounded">{{ $chat->transitAccount->name }}</div>
                        </div>

                        <div>
                            <x-input-label value="Expense Categories" />
                            <div class="mt-2 space-y-1">
                                @foreach($expenseCategories as $folder)
                                    <x-expense-category-folder-readonly
                                        :folder="$folder"
                                        :selectedCategories="$selectedCategories"
                                    />
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.chats.edit', $chat) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Редактировать') }}
                            </a>
                            <a href="{{ route('admin.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Назад') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-folder-code]').forEach(button => {
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
