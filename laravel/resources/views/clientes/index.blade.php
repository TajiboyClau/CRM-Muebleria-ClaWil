<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Clientes') }}</h2>
            @if (in_array(auth()->user()->rol, ['administrador', 'supervisor'], true))
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
                                            <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" class="inline" onsubmit="return confirm('¿Eliminar este cliente?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ml-3 text-red-600 hover:text-red-900">Eliminar</button>
                                            </form>
                                        @elseif ($vendedores->isNotEmpty())
                                            <a href="{{ route('clientes.edit', $cliente) }}" class="mr-3 text-indigo-600 hover:text-indigo-900">Editar</a>
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

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('clientes.index') }}" class="grid gap-4 p-6 sm:grid-cols-4">
                    <div><label for="q" class="block text-sm font-medium">Buscar por nombre o NIT</label><input id="q" name="q" value="{{ $search }}" class="mt-1 w-full rounded-md border-gray-300" /></div>
                    <div><label for="vendedor_id" class="block text-sm font-medium">Vendedor</label><select id="vendedor_id" name="vendedor_id" class="mt-1 w-full rounded-md border-gray-300"><option value="">Todos</option>@foreach ($vendedores as $vendedor)<option value="{{ $vendedor->id }}" @selected((string) $sellerFilter === (string) $vendedor->id)>{{ $vendedor->name }}</option>@endforeach</select></div>
                    <label class="flex items-end gap-2 pb-2"><input type="checkbox" name="sin_asignar" value="1" @checked($onlyAvailable) /> Sin asignar</label>
                    <div class="flex items-end"><button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Filtrar</button></div>
                </form>
            </div>

            @if ($team->isNotEmpty())
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6"><h3 class="font-semibold">Cartera del equipo</h3><div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th class="px-3 py-2">Vendedor</th><th class="px-3 py-2">Cartera</th><th class="px-3 py-2">Ventas del mes</th><th class="px-3 py-2">Total del mes</th></tr></thead><tbody>@foreach ($team as $seller)<tr><td class="px-3 py-2">{{ $seller->name }}</td><td class="px-3 py-2">{{ $seller->clientes_count }} de 10</td><td class="px-3 py-2">{{ $seller->ventas_mes }}</td><td class="px-3 py-2">Q {{ number_format($seller->total_mes, 2) }}</td></tr>@endforeach</tbody></table></div></div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
