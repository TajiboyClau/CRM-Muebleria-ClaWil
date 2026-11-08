<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => User::with('supervisor')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('usuarios.create', ['supervisores' => User::where('rol', 'supervisor')->where('activo', true)->orderBy('name')->get()]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        return to_route('usuarios.index')->with('status', 'Usuario creado correctamente.');
    }

    public function toggle(User $usuario): RedirectResponse
    {
        abort_if($usuario->is(auth()->user()), 422, 'No puedes desactivar tu propia cuenta.');
        $usuario->update(['activo' => !$usuario->activo]);

        return to_route('usuarios.index')->with('status', 'Estado del usuario actualizado.');
    }
}
