<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel del CRM') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-gray-900">{{ __('Sesión iniciada correctamente.') }}</p>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Rol:') }} {{ auth()->user()->rol }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
