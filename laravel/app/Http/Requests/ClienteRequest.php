<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

abstract class ClienteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($nit = $this->input('nit'))) {
            $nit = preg_replace('/[\s-]/u', '', mb_strtoupper($nit));
            $this->merge(['nit' => $nit === 'CF' ? null : preg_replace('/^0+(?=\d)/', '', $nit)]);
        }
        if (is_string($phone = $this->input('telefono'))) {
            $phone = preg_replace('/[\s.()-]/u', '', $phone);
            $this->merge(['telefono' => preg_replace('/^(?:\+502|502)(?=\d{8}$)/', '', $phone)]);
        }
    }

    protected function nitRule(): Closure
    {
        return static function (string $attribute, mixed $nit, Closure $fail): void {
            if (!is_string($nit) || !preg_match('/^\d{1,11}[0-9K]$/D', $nit)) {
                $fail('Escriba un NIT válido o CF.');
                return;
            }

            $sum = 0;
            foreach (str_split(strrev(substr($nit, 0, -1))) as $index => $digit) {
                $sum += (int) $digit * ($index + 2);
            }
            $check = (11 - $sum % 11) % 11;
            if (substr($nit, -1) !== ($check === 10 ? 'K' : (string) $check)) {
                $fail('Escriba un NIT válido o CF.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'telefono.regex' => 'Ingrese un teléfono de Guatemala de 8 dígitos.',
            'telefono.unique' => 'El teléfono ya está registrado.',
            'nit.unique' => 'El NIT ya está registrado.',
        ];
    }
}
