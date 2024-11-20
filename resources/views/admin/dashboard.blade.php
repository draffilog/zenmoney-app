<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Telegram Bot Status -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Telegram Bot Status</h3>
                            <p>Status: {{ $stats['bot_status'] }}</p>
                            <p>Total Chats: {{ $stats['total_chats'] }}</p>
                            <p class="mt-2">
                                <a href="{{ route('admin.settings.edit') }}" class="text-blue-500 hover:underline">
                                    Configure Bot Settings
                                </a>
                            </p>
                        </div>

                        <!-- ZenMoney Status -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">ZenMoney Integration</h3>
                            <p>Status: {{ $stats['zenmoney_status'] }}</p>
                            <p class="mt-2">
                                <a href="{{ route('admin.settings.edit') }}" class="text-blue-500 hover:underline">
                                    Configure ZenMoney Settings
                                </a>
                            </p>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                            <div class="flex space-x-4">
                                <a href="{{ route('admin.chats.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Add New Chat
                                </a>
                                <a href="{{ route('admin.chats.index') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                    Manage Chats
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
