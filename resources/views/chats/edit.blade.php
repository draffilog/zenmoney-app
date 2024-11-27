<div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl mb-4">Редактирование чата</h2>
    <form action="{{ route('chats.update', $chat->id) }}" method="POST">
        @csrf
        @method('PUT')
        <!-- Поля формы -->
    </form>
</div>
