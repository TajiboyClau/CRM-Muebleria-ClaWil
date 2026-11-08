<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar cliente') }}</h2></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('clientes.partials.form')
                <div class="flex justify-end gap-3"><a href="{{ route('clientes.index') }}" class="rounded-md px-4 py-2 text-sm text-gray-600">Cancelar</a><x-primary-button>Actualizar cliente</x-primary-button></div>
            </form>
        </div>
    </div></div>
</x-app-layout>
