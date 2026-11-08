<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(): View
    {
        return view('productos.index', [
            'productos' => Producto::query()->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('productos.create');
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        Producto::create([
            ...$request->validated(),
            'activo' => $request->boolean('activo', true),
        ]);

        return to_route('productos.index')->with('status', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto): View
    {
        return view('productos.edit', compact('producto'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update([
            ...$request->validated(),
            'activo' => $request->boolean('activo', false),
        ]);

        return to_route('productos.index')->with('status', 'Producto actualizado correctamente.');
    }

    public function deactivate(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => false]);

        return to_route('productos.index')->with('status', 'Producto desactivado correctamente.');
    }
}
