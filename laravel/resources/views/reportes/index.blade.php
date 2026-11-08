<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Reportes del CRM</h2></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Clientes', $clients], ['Productos activos', $products], ['Oportunidades', $opportunities], ['Actividades', $activities]] as [$label, $value])
                <div class="rounded-lg bg-white p-6 shadow-sm"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ $value }}</p></div>
            @endforeach
        </div>
        <div class="rounded-lg bg-white p-6 shadow-sm"><h3 class="font-semibold text-gray-900">Oportunidades por etapa</h3><div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@forelse ($stages as $stage => $total)<div class="rounded-md bg-indigo-50 p-4"><span class="text-sm text-indigo-700">{{ ucfirst($stage) }}</span><strong class="mt-1 block text-2xl text-indigo-900">{{ $total }}</strong></div>@empty<p class="text-sm text-gray-500">No hay datos para mostrar.</p>@endforelse</div></div>
    </div></div>
</x-app-layout>
