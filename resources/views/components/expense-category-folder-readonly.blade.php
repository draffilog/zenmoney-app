@props(['folder', 'selectedCategories'])

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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
        <span class="font-medium text-gray-700">{{ $folder['name'] }}</span>
    </button>

    <div class="ml-6 space-y-2 hidden transition-all duration-200 ease-in-out"
         id="folder-content-{{ $folder['code'] }}">
        @foreach($folder['children'] as $category)
            <div class="flex items-center p-2 hover:bg-gray-50 rounded">
                <input type="checkbox"
                       disabled
                       {{ in_array($category['code'], $selectedCategories) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-gray-400 shadow-sm">
                <span class="ml-2 text-gray-600">{{ $category['name'] }}</span>
            </div>
        @endforeach
    </div>
</div>
