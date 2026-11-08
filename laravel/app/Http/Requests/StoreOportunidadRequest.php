<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOportunidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === 'administrador' && $this->user()->activo;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'vendedor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', 'vendedor')
                    ->where('activo', true)),
            ],
            'titulo' => ['required', 'string', 'max:150'],
            'etapa' => ['required', Rule::in(['prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida'])],
            'motivo_perdida' => ['nullable', 'required_if:etapa,perdida', 'string', 'max:255'],
            'fecha_cierre' => ['nullable', 'required_if:etapa,ganada,perdida', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('etapa') !== 'ganada' || !str_contains(mb_strtolower($this->input('titulo', '')), 'a medida')) {
                return;
            }

            $hasMeasurement = Cliente::find($this->input('cliente_id'))?->actividades()
                ->where('tipo', 'reunion')
                ->where('completada', true)
                ->where(function ($query) {
                    $query->where('descripcion', 'like', '%medicion%')
                        ->orWhere('descripcion', 'like', '%medición%');
                })
                ->exists();

            if (!$hasMeasurement) {
                $validator->errors()->add('etapa', 'Una oportunidad a medida necesita una reunión de medición completada.');
            }
        });
    }
}
