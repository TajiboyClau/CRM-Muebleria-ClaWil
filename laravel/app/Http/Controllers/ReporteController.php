<?php

namespace App\Http\Controllers;

use App\Models\Oportunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['mes' => ['nullable', 'date_format:Y-m']]);
        $mes = $request->input('mes') ?: now('America/Guatemala')->format('Y-m');
        $start = Carbon::createFromFormat('!Y-m', $mes, 'America/Guatemala');
        $end = $start->copy()->addMonth()->utc();
        $start->utc();

        $user = $request->user();
        $base = Oportunidad::query()->when($user->rol === 'supervisor', fn ($query) => $query
            ->whereHas('vendedor', fn ($seller) => $seller->where('supervisor_id', $user->id)));
        $sales = (clone $base)->where('etapa', 'ganada')
            ->where('fecha_cierre', '>=', $start)->where('fecha_cierre', '<', $end);
        $lines = DB::table('oportunidad_producto')->whereIn('oportunidad_id', (clone $sales)->select('id'));
        $products = (clone $lines)->join('productos', 'productos.id', '=', 'oportunidad_producto.producto_id')
            ->groupBy('productos.id', 'productos.nombre')->orderBy('productos.nombre')
            ->selectRaw('productos.nombre, SUM(cantidad) AS unidades, SUM(cantidad * precio_unitario) AS total')->get();
        $forecast = (clone $base)->join('oportunidad_producto', 'oportunidades.id', '=', 'oportunidad_producto.oportunidad_id')
            ->whereIn('etapa', ['prospecto', 'calificado', 'propuesta', 'negociacion'])
            ->selectRaw("SUM(cantidad * precio_unitario * CASE etapa
                WHEN 'prospecto' THEN 0.10 WHEN 'calificado' THEN 0.25
                WHEN 'propuesta' THEN 0.50 ELSE 0.75 END) AS total")->value('total');
        $bySeller = (clone $sales)->join('users', 'users.id', '=', 'oportunidades.vendedor_id')
            ->leftJoin('oportunidad_producto', 'oportunidades.id', '=', 'oportunidad_producto.oportunidad_id')
            ->groupBy('users.id', 'users.name')->orderBy('users.name')
            ->selectRaw('users.name, COUNT(DISTINCT oportunidades.id) AS ventas, COALESCE(SUM(cantidad * precio_unitario), 0) AS total')->get();

        return view('reportes.index', [
            'mes' => $mes, 'sales' => $sales->count(),
            'total' => $lines->sum(DB::raw('cantidad * precio_unitario')),
            'forecast' => round((float) $forecast, 2), 'products' => $products, 'bySeller' => $bySeller,
        ]);
    }
}
