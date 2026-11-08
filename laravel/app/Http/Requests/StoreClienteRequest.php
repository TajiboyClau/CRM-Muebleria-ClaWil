<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:12', 'unique:clientes,nit'],
            'telefono' => ['required', 'digits:8', 'unique:clientes,telefono'],
            'correo' => ['nullable', 'email', 'max:150'],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', 'vendedor')
                    ->where('activo', true)),
            ],
        ];
    }
}
