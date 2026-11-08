<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, ['administrador', 'supervisor'], true)
            && $this->user()->activo;
    }

    public function rules(): array
    {
        return [
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
