<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, ['administrador', 'supervisor'], true)
            && $this->user()->activo;
    }

    public function rules(): array
    {
        return ['completada' => ['required', 'boolean']];
    }
}
