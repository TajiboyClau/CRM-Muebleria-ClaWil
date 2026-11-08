<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignClienteRequest;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $clientes = Cliente::with('vendedor')
            ->when($user->rol === 'supervisor', function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->whereNull('vendedor_id')
                        ->orWhereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id));
                });
            })
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', [
            'clientes' => $clientes,
            'vendedores' => $this->availableSellers($user),
        ]);
    }

    public function create(): View
    {
        return view('clientes.create', [
            'vendedores' => $this->availableSellers(auth()->user()),
        ]);
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        Cliente::create($request->validated());

        return to_route('clientes.index')->with('status', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', [
            'cliente' => $cliente,
            'vendedores' => $this->availableSellers(auth()->user()),
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return to_route('clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    public function assign(AssignClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $sellerId = $request->validated('vendedor_id');

        $user = $request->user();

        if ($user->rol === 'supervisor') {
            abort_if(
                $cliente->vendedor_id !== null && $cliente->vendedor?->supervisor_id !== $user->id,
                404,
            );

            if ($sellerId !== null) {
                $seller = User::findOrFail($sellerId);
                abort_unless($seller->supervisor_id === $user->id, 403);
            }

            // Recheck the current assignment in the write in case it changed concurrently.
            $updated = Cliente::query()->whereKey($cliente->id)
                ->where(fn ($query) => $query->whereNull('vendedor_id')
                    ->orWhereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id)))
                ->update(['vendedor_id' => $sellerId]);

            abort_if($updated === 0, 404);
        } else {
            $cliente->update(['vendedor_id' => $sellerId]);
        }

        return to_route('clientes.index')->with('status', 'Asignación del cliente actualizada.');
    }

    private function availableSellers(User $user)
    {
        return User::query()
            ->where('rol', 'vendedor')
            ->where('activo', true)
            ->when($user->rol === 'supervisor', fn ($query) => $query->where('supervisor_id', $user->id))
            ->orderBy('name')
            ->get();
    }
}
