<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ __('Bot Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 text-sm font-medium text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Telegram Bot Token</label>
                            <input type="text" name="telegram_bot_token"
                                value="{{ old('telegram_bot_token', $settings['telegram_bot_token'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('telegram_bot_token') border-red-500 @enderror">
                            @error('telegram_bot_token')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Telegram Bot Username</label>
                            <input type="text" name="telegram_bot_username"
                                value="{{ old('telegram_bot_username', $settings['telegram_bot_username'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('telegram_bot_username') border-red-500 @enderror">
                            @error('telegram_bot_username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">ZenMoney Token</label>
                            <input type="text" name="zenmoney_token"
                                value="{{ old('zenmoney_token', $settings['zenmoney_token'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm @error('zenmoney_token') border-red-500 @enderror">
                            @error('zenmoney_token')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
