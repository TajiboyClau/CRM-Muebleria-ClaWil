<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'vendedor_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('rol', 'vendedor')->where('activo', true))],
            'tipo' => ['required', Rule::in(['llamada', 'reunion', 'tarea'])],
            'descripcion' => ['required', 'string', 'max:500'],
            'vence_en' => ['nullable', 'date'],
            'completada' => ['sometimes', 'boolean'],
        ];
    }
}
