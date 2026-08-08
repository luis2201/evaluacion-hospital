<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña actual no es correcta.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'array' => 'Selecciona al menos :min elemento.'],
    'max' => ['string' => 'El campo :attribute no debe superar :max caracteres.'],
    'password' => [
        'letters' => 'La contraseña debe incluir al menos una letra.',
        'mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
        'numbers' => 'La contraseña debe incluir al menos un número.',
        'symbols' => 'La contraseña debe incluir al menos un símbolo.',
    ],
    'attributes' => [
        'name' => 'nombre', 'email' => 'correo electrónico', 'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña', 'current_password' => 'contraseña actual',
        'roles' => 'roles', 'activo' => 'estado',
    ],
];
