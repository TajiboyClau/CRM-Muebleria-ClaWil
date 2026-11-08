<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Clientes') }}</h2>
            @if (auth()->user()->rol === 'administrador')
                <a href="{{ route('clientes.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                    {{ __('Nuevo cliente') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-3">Nombre</th>
                                <th class="px-3 py-3">NIT</th>
                                <th class="px-3 py-3">Teléfono</th>
                                <th class="px-3 py-3">Vendedor</th>
                                <th class="px-3 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($clientes as $cliente)
                                <tr>
                                    <td class="px-3 py-3 font-medium text-gray-900">{{ $cliente->nombre }}</td>
                                    <td class="px-3 py-3">{{ $cliente->nit ?: 'Sin NIT' }}</td>
                                    <td class="px-3 py-3">{{ $cliente->telefono }}</td>
                                    <td class="px-3 py-3">{{ $cliente->vendedor?->name ?: 'Disponible' }}</td>
                                    <td class="px-3 py-3 text-right">
                                        @if (auth()->user()->rol === 'administrador')
                                            <a href="{{ route('clientes.edit', $cliente) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                        @elseif ($vendedores->isNotEmpty())
                                            <form method="POST" action="{{ route('clientes.assign', $cliente) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="vendedor_id" class="rounded-md border-gray-300 py-1 text-sm">
                                                    <option value="">Disponible</option>
                                                    @foreach ($vendedores as $vendedor)
                                                        <option value="{{ $vendedor->id }}" @selected($cliente->vendedor_id === $vendedor->id)>{{ $vendedor->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="text-indigo-600 hover:text-indigo-900">Asignar</button>
                                            </form>
                                        @else
                                            <span class="text-gray-400">Sin vendedores</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No hay clientes disponibles.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
