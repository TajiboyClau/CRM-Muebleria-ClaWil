<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:12',
                Rule::unique('clientes', 'nit')->ignore(
                    $cliente instanceof Cliente ? $cliente->getKey() : $cliente
                ),
            ],
            'telefono' => [
                'required',
                'digits:8',
                Rule::unique('clientes', 'telefono')->ignore(
                    $cliente instanceof Cliente ? $cliente->getKey() : $cliente
                ),
            ],
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
