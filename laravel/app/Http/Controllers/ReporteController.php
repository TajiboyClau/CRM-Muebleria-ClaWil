<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\Producto;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $teamFilter = fn ($query) => $user->rol === 'supervisor'
            ? $query->whereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id))
            : $query;

        return view('reportes.index', [
            'products' => Producto::where('activo', true)->count(),
            'clients' => Cliente::when($user->rol === 'supervisor', fn ($query) => $query->where(function ($query) use ($user) {
                $query->whereNull('vendedor_id')->orWhereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id));
            }))->count(),
            'opportunities' => Oportunidad::when($user->rol === 'supervisor', $teamFilter)->count(),
            'activities' => Actividad::when($user->rol === 'supervisor', $teamFilter)->count(),
            'stages' => Oportunidad::selectRaw('etapa, COUNT(*) AS total')
                ->when($user->rol === 'supervisor', $teamFilter)
                ->groupBy('etapa')
                ->orderBy('etapa')
                ->pluck('total', 'etapa'),
        ]);
    }
}
