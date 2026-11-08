<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignClienteRequest;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $this->ensureSellerHasCapacity($data['vendedor_id'] ?? null);
            Cliente::create($data);
        });

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
        $data = $request->validated();

        DB::transaction(function () use ($cliente, $data) {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);

            if (array_key_exists('vendedor_id', $data)) {
                $this->ensureSellerHasCapacity($data['vendedor_id'], $cliente);
            }

            $cliente->update($data);
        });

        return to_route('clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    public function assign(AssignClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $sellerId = $request->validated('vendedor_id');
        $user = $request->user();

        DB::transaction(function () use ($cliente, $sellerId, $user) {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);

            if ($user->rol === 'supervisor') {
                abort_if(
                    $cliente->vendedor_id !== null && $cliente->vendedor?->supervisor_id !== $user->id,
                    404,
                );
            }

            $this->ensureSellerHasCapacity($sellerId, $cliente, $user);
            $cliente->update(['vendedor_id' => $sellerId]);
        });

        return to_route('clientes.index')->with('status', 'Asignación del cliente actualizada.');
    }

    private function ensureSellerHasCapacity(int|string|null $sellerId, ?Cliente $cliente = null, ?User $actor = null): void
    {
        if ($sellerId === null) {
            return;
        }

        // The API locks the same seller row before adding a client.
        $seller = User::lockForUpdate()->findOrFail($sellerId);

        if ($actor?->rol === 'supervisor') {
            abort_unless($seller->supervisor_id === $actor->id, 403);
        }

        if ((string) $cliente?->vendedor_id !== (string) $seller->id
            && Cliente::where('vendedor_id', $seller->id)->count() >= 10) {
            throw ValidationException::withMessages([
                'vendedor_id' => 'El vendedor ya tiene 10 clientes asignados.',
            ]);
        }
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
