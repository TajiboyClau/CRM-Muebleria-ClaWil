<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120', 'unique:productos,nombre'],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
