<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ __('ZenMoney Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Accounts Section -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-4">Счета</h3>
                            <div class="space-y-4">
                                @foreach($zenMoneyData['accounts'] ?? [] as $account)
                                    <div class="border-b pb-2">
                                        <p class="font-medium">{{ $account['title'] }}</p>
                                        <p class="text-sm text-gray-600">Баланс: {{ $account['balance'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Categories Section -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-4">Категории</h3>
                            <div class="space-y-4">
                                @foreach($zenMoneyData['tags'] ?? [] as $tag)
                                    <div class="border-b pb-2">
                                        <p class="font-medium">{{ $tag['title'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
