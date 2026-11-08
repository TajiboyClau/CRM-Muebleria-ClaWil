<div>
    <x-input-label for="nombre" :value="__('Nombre completo o empresa')" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $cliente->nombre ?? '')" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
</div>
<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="nit" :value="__('NIT')" />
        <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" :value="old('nit', $cliente->nit ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('nit')" />
    </div>
    <div>
        <x-input-label for="telefono" :value="__('Teléfono')" />
        <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $cliente->telefono ?? '')" required />
        <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
    </div>
</div>
<div>
    <x-input-label for="correo" :value="__('Correo electrónico')" />
    <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" :value="old('correo', $cliente->correo ?? '')" />
    <x-input-error class="mt-2" :messages="$errors->get('correo')" />
</div>
<div>
    <x-input-label for="vendedor_id" :value="__('Vendedor asignado')" />
    <select id="vendedor_id" name="vendedor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Disponible</option>
        @foreach ($vendedores as $vendedor)
            <option value="{{ $vendedor->id }}" @selected(old('vendedor_id', $cliente->vendedor_id ?? '') == $vendedor->id)>{{ $vendedor->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('vendedor_id')" />
</div>
