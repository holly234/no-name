<x-app-layout>
    <div class="mx-auto max-w-xl py-12">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-950">Create your workspace</h1>
            <p class="mt-2 text-sm text-gray-600">Every conversation, customer, rule, and connected account is scoped to this workspace.</p>
            <form method="POST" action="{{ route('onboarding.workspace.store') }}" class="mt-6 space-y-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Workspace name
                    <input name="name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </label>
                <label class="block text-sm font-medium text-gray-700">Category
                    <input name="category" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </label>
                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create workspace</button>
            </form>
        </div>
    </div>
</x-app-layout>
