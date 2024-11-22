@props(['category', 'level' => 0])

<div class="ml-{{ $level * 4 }} py-1">
    <label class="inline-flex items-center {{ $category['type'] === 'folder' ? 'font-medium' : '' }}">
        <input type="checkbox"
               name="expense_categories[]"
               value="{{ $category['name'] }}"
               data-type="{{ $category['type'] }}"
               data-parent="{{ $category['type'] === 'category' ? ($category['parent_id'] ?? '') : $category['id'] }}"
               class="category-checkbox rounded border-gray-300 text-blue-600 shadow-sm"
               {{ $category['type'] === 'folder' ? 'disabled' : '' }}>
        <span class="ml-2">{{ $category['name'] }}</span>
    </label>

    @if(isset($category['children']) && !empty($category['children']))
        <div class="ml-4">
            @foreach($category['children'] as $child)
                <x-category-tree :category="$child" :level="$level + 1" />
            @endforeach
        </div>
    @endif
</div>
