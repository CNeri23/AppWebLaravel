<?php

return [

    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'unique' => 'El :attribute ya ha sido registrado.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'array' => 'El campo :attribute debe ser un arreglo.',
    'integer' => 'El campo :attribute debe ser un número entero.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'roles' => 'roles',
        'roles.*' => 'rol',
    ],

];