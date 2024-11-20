<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Add New Chat</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.chats.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label>Chat Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="mb-4">
                    <label>Telegram Chat ID</label>
                    <input type="text" name="chat_id" required>
                </div>

                <div class="mb-4">
                    <label>Transaction Account</label>
                    <select name="transaction_account_id" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label>Deposit Account</label>
                    <select name="deposit_account_id" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label>Allowed Categories</label>
                    @foreach($categories as $id => $category)
                        <div class="ml-4">
                            <label>
                                <input type="checkbox" name="allowed_categories[]" value="{{ $id }}">
                                {{ $category['name'] }}
                            </label>

                            @foreach($category['subcategories'] as $sub)
                                <div class="ml-8">
                                    <label>
                                        <input type="checkbox" name="allowed_categories[]" value="{{ $sub['id'] }}">
                                        {{ $sub['name'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                    Save Chat Settings
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
