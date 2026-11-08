<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteActividadRequest;
use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActividadController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $actividades = Actividad::with(['cliente', 'vendedor'])
            ->when($user->rol === 'supervisor', fn ($query) => $query->whereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id)))
            ->orderByDesc('vence_en')
            ->get();

        return view('actividades.index', compact('actividades'));
    }

    public function create(): View
    {
        return view('actividades.create', $this->formData());
    }

    public function store(StoreActividadRequest $request): RedirectResponse
    {
        Actividad::create([
            ...$request->validated(),
            'completada' => $request->boolean('completada'),
        ]);

        return to_route('actividades.index')->with('status', 'Actividad creada correctamente.');
    }

    public function edit(Actividad $actividad): View
    {
        return view('actividades.edit', array_merge(['actividad' => $actividad], $this->formData()));
    }

    public function update(UpdateActividadRequest $request, Actividad $actividad): RedirectResponse
    {
        $actividad->update([
            ...$request->validated(),
            'completada' => $request->boolean('completada'),
        ]);

        return to_route('actividades.index')->with('status', 'Actividad actualizada correctamente.');
    }

    public function complete(CompleteActividadRequest $request, Actividad $actividad): RedirectResponse
    {
        if (auth()->user()->rol === 'supervisor') {
            abort_unless($actividad->vendedor?->supervisor_id === auth()->id(), 403);
        }

        $actividad->update(['completada' => $request->boolean('completada')]);

        return to_route('actividades.index')->with('status', 'Estado de la actividad actualizado.');
    }

    private function formData(): array
    {
        return [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'vendedores' => User::query()->where('rol', 'vendedor')->where('activo', true)->orderBy('name')->get(),
        ];
    }
}
