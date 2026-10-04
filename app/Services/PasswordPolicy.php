<?php

namespace App\Services;

class PasswordPolicy
{
    public function __construct(private SystemSettings $settings)
    {
    }

    public function minLength(): int
    {
        return min(32, max(8, (int) $this->settings->get('password_min_length', 8)));
    }

    public function requiresComplexity(): bool
    {
        return (bool) $this->settings->get('password_complexity', false);
    }

    public function rules(): array
    {
        $rules = [
            'bail',
            'required',
            'string',
            'min:' . $this->minLength(),
            'confirmed',
        ];

        if ($this->requiresComplexity()) {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) {
                $cumple = preg_match('/\p{Ll}/u', (string) $value)
                    && preg_match('/\p{Lu}/u', (string) $value)
                    && preg_match('/\d/', (string) $value);

                if (! $cumple) {
                    $fail('La contraseña debe incluir al menos una mayúscula, una minúscula y un número.');
                }
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña no es válida.',
            'password.min' => 'La contraseña debe tener al menos ' . $this->minLength() . ' caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function description(): string
    {
        $texto = 'Mínimo ' . $this->minLength() . ' caracteres';

        if ($this->requiresComplexity()) {
            $texto .= ', con mayúsculas, minúsculas y números';
        }

        return $texto . '.';
    }
}