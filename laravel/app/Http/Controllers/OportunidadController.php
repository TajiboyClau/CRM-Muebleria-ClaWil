<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOportunidadRequest;
use App\Http\Requests\UpdateOportunidadRequest;
use App\Models\Cliente;
use App\Models\HistorialEtapa;
use App\Models\Oportunidad;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OportunidadController extends Controller
{
    public function index(): View
    {
        return view('oportunidades.index', [
            'oportunidades' => Oportunidad::with(['cliente', 'vendedor'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('oportunidades.create', $this->formData());
    }

    public function store(StoreOportunidadRequest $request): RedirectResponse
    {
        $oportunidad = Oportunidad::create($request->validated());
        $this->recordStageChange($oportunidad, null, $oportunidad->etapa);

        return to_route('oportunidades.index')->with('status', 'Oportunidad creada correctamente.');
    }

    public function edit(Oportunidad $oportunidad): View
    {
        return view('oportunidades.edit', array_merge(['oportunidad' => $oportunidad], $this->formData()));
    }

    public function update(UpdateOportunidadRequest $request, Oportunidad $oportunidad): RedirectResponse
    {
        $oldStage = $oportunidad->etapa;
        $oportunidad->update($request->validated());

        if ($oldStage !== $oportunidad->etapa) {
            $this->recordStageChange($oportunidad, $oldStage, $oportunidad->etapa);
        }

        return to_route('oportunidades.index')->with('status', 'Oportunidad actualizada correctamente.');
    }

    private function formData(): array
    {
        return [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'vendedores' => User::query()->where('rol', 'vendedor')->where('activo', true)->orderBy('name')->get(),
        ];
    }

    private function recordStageChange(Oportunidad $oportunidad, ?string $previous, string $current): void
    {
        HistorialEtapa::create([
            'oportunidad_id' => $oportunidad->id,
            'usuario_id' => auth()->id(),
            'etapa_anterior' => $previous,
            'etapa_nueva' => $current,
        ]);
    }
}
