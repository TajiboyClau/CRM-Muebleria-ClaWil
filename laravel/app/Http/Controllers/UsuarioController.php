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

    public function edit(User $usuario): View
    {
        return view('usuarios.create', [
            'usuario' => $usuario,
            'supervisores' => User::where('rol', 'supervisor')->where('activo', true)
                ->whereKeyNot($usuario->id)->orderBy('name')->get(),
        ]);
    }

    public function update(StoreUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $data = $request->validated();
        abort_if($usuario->is($request->user()) && $data['rol'] !== 'administrador', 422, 'No puede quitarse el rol de administrador.');
        abort_if($data['rol'] !== 'supervisor' && $usuario->vendedores()->exists(), 422, 'Reasigne a los vendedores antes de cambiar este rol.');
        abort_if((string) ($data['supervisor_id'] ?? '') === (string) $usuario->id, 422, 'No puede ser su propio supervisor.');
        $data['supervisor_id'] = $data['supervisor_id'] ?? null;
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $usuario->update($data);

        return to_route('usuarios.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggle(User $usuario): RedirectResponse
    {
        abort_if($usuario->is(auth()->user()), 422, 'No puedes desactivar tu propia cuenta.');
        $usuario->update(['activo' => !$usuario->activo]);

        return to_route('usuarios.index')->with('status', 'Estado del usuario actualizado.');
    }
}
