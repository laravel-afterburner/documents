@props(['progress' => 0])

<div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
    <div
        class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300"
        style="width: {{ $progress }}%"
    ></div>
</div>
<div class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">
    {{ $progress }}%
</div>

