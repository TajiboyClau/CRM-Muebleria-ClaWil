<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Reportes del CRM</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 p-6">
        <form method="GET" class="flex items-center gap-3">
            <label for="mes">Mes</label>
            <input type="month" id="mes" name="mes" value="{{ $mes }}" required>
            <button class="rounded bg-indigo-700 px-3 py-2 text-white">Consultar</button>
        </form>
        @error('mes') <p class="text-red-700">{{ $message }}</p> @enderror
        <section class="rounded bg-white p-4">
            <p>Ventas: {{ $sales }}</p>
            <p>Total vendido: Q{{ number_format($total, 2) }}</p>
            <p>Pronóstico de oportunidades abiertas: Q{{ number_format($forecast, 2) }}</p>
        </section>
        <section class="rounded bg-white p-4">
            <h3 class="font-semibold">Ventas por vendedor</h3>
            @forelse ($bySeller as $seller)
                <p>{{ $seller->name }} · {{ $seller->ventas }} ventas · Q{{ number_format($seller->total, 2) }}</p>
            @empty <p>Sin ventas en el mes.</p> @endforelse
        </section>
        <section class="rounded bg-white p-4">
            <h3 class="font-semibold">Ventas por producto</h3>
            @forelse ($products as $product)
                <p>{{ $product->nombre }} · {{ $product->unidades }} unidades · Q{{ number_format($product->total, 2) }}</p>
            @empty <p>Sin productos vendidos en el mes.</p> @endforelse
        </section>
    </div>
</x-app-layout>
