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
        $search = trim((string) request('q', ''));
        $sellerFilter = request('vendedor_id');
        $onlyAvailable = request()->boolean('sin_asignar');
        $clientes = Cliente::with('vendedor')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('nit', 'like', "%{$search}%");
            }))
            ->when($user->rol === 'supervisor', function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->whereNull('vendedor_id')
                        ->orWhereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id));
                });
            })
            ->when($sellerFilter !== null && $sellerFilter !== '', fn ($query) => $query->where('vendedor_id', $sellerFilter))
            ->when($onlyAvailable, fn ($query) => $query->whereNull('vendedor_id'))
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', [
            'clientes' => $clientes,
            'vendedores' => $this->availableSellers($user),
            'team' => $this->teamSummary($user),
            'search' => $search,
            'sellerFilter' => $sellerFilter,
            'onlyAvailable' => $onlyAvailable,
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
        $this->ensureVisible($cliente, auth()->user());

        return view('clientes.edit', [
            'cliente' => $cliente,
            'vendedores' => $this->availableSellers(auth()->user()),
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $this->ensureVisible($cliente, $request->user());
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

    public function destroy(Cliente $cliente): RedirectResponse
    {
        abort_unless(auth()->user()?->rol === 'administrador', 403);

        if ($cliente->oportunidades()->exists()) {
            throw ValidationException::withMessages([
                'cliente' => 'No puede eliminar un cliente que tiene oportunidades registradas.',
            ]);
        }

        $cliente->delete();

        return to_route('clientes.index')->with('status', 'Cliente eliminado correctamente.');
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

    private function ensureVisible(Cliente $cliente, User $user): void
    {
        if ($user->rol === 'supervisor'
            && $cliente->vendedor_id !== null
            && $cliente->vendedor?->supervisor_id !== $user->id) {
            abort(404);
        }
    }

    private function teamSummary(User $user)
    {
        $sellers = User::query()
            ->where('rol', 'vendedor')
            ->when($user->rol === 'supervisor', fn ($query) => $query->where('supervisor_id', $user->id))
            ->withCount('clientes')
            ->orderBy('name')
            ->get();

        $start = now('America/Guatemala')->startOfMonth()->utc();
        $end = $start->copy()->addMonth();
        $totals = DB::table('oportunidades')
            ->leftJoin('oportunidad_producto', 'oportunidades.id', '=', 'oportunidad_producto.oportunidad_id')
            ->where('oportunidades.etapa', 'ganada')
            ->where('oportunidades.fecha_cierre', '>=', $start)
            ->where('oportunidades.fecha_cierre', '<', $end)
            ->groupBy('oportunidades.vendedor_id')
            ->select('oportunidades.vendedor_id')
            ->selectRaw('COUNT(DISTINCT oportunidades.id) AS ventas, COALESCE(SUM(cantidad * precio_unitario), 0) AS total')
            ->get()
            ->keyBy('vendedor_id');

        return $sellers->map(function ($seller) use ($totals) {
            $total = $totals->get($seller->id);
            $seller->ventas_mes = (int) ($total->ventas ?? 0);
            $seller->total_mes = (float) ($total->total ?? 0);
            return $seller;
        });
    }
}
