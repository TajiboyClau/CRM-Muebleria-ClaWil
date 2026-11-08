<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Oportunidades de venta') }}</h2>
            @if (auth()->user()->rol === 'administrador')
                <a href="{{ route('oportunidades.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Nueva oportunidad</a>
            @endif
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>@endif
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg"><div class="overflow-x-auto p-6">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Título</th><th class="px-3 py-3">Cliente</th><th class="px-3 py-3">Vendedor</th><th class="px-3 py-3">Etapa</th><th class="px-3 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($oportunidades as $oportunidad)
                        <tr><td class="px-3 py-3 font-medium text-gray-900">{{ $oportunidad->titulo }}</td><td class="px-3 py-3">{{ $oportunidad->cliente->nombre }}</td><td class="px-3 py-3">{{ $oportunidad->vendedor->name }}</td><td class="px-3 py-3"><span class="rounded-full bg-indigo-100 px-2 py-1 text-xs text-indigo-700">{{ ucfirst($oportunidad->etapa) }}</span></td><td class="px-3 py-3 text-right">@if (auth()->user()->rol === 'administrador')<a href="{{ route('oportunidades.edit', $oportunidad) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>@else<span class="text-gray-400">Solo lectura</span>@endif</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No hay oportunidades registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div></div>
    </div></div>
</x-app-layout>
