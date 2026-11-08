<div>
    <x-input-label for="nombre" value="Nombre del producto" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $producto->nombre ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>
<div>
    <x-input-label for="precio" value="Precio en quetzales" />
    <x-text-input id="precio" name="precio" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('precio', $producto->precio ?? '')" required />
    <x-input-error :messages="$errors->get('precio')" class="mt-2" />
</div>
<label class="inline-flex items-center gap-2">
    <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo ?? true)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
    <span class="text-sm text-gray-600">Producto activo</span>
</label>
