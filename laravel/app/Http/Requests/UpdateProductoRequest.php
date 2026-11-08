<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Producto;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('productos', 'nombre')->ignore(
                    $producto instanceof Producto ? $producto->getKey() : $producto
                ),
            ],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
