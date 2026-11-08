<?php

namespace App\Http\Controllers;

use App\Models\Oportunidad;
use Illuminate\View\View;

class OportunidadController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $oportunidades = Oportunidad::with(['cliente', 'vendedor'])
            ->when($user->rol === 'supervisor', fn ($query) => $query
                ->whereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id)))
            ->latest()
            ->get();

        return view('oportunidades.index', compact('oportunidades'));
    }
}
