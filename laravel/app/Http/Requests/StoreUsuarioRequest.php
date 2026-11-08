<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', Rule::in(['administrador', 'supervisor', 'vendedor'])],
            'supervisor_id' => [
                'exclude_unless:rol,vendedor',
                'required_if:rol,vendedor',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('rol', 'supervisor')->where('activo', true)),
            ],
        ];
    }
}
