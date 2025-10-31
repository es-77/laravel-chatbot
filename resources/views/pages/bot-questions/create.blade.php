<x-admin-layout>
    <x-slot name="header">
        Create Bot Question
    </x-slot>

    <div class="mb-6">
        <a href="{{ route('bot-questions.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">← Back to List</a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form action="{{ route('bot-questions.store') }}" method="POST">
            @include('laravel-chatbot::pages.bot-questions._form')
            <div class="flex justify-end space-x-4 mt-6">
                <a href="{{ route('bot-questions.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition">Cancel</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">Create Question</button>
            </div>
        </form>
    </div>
</x-admin-layout>