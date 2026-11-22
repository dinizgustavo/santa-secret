<?php

return [
    'title' => 'Configuración',
    'description' => 'Administra tu perfil y tu cuenta',
    'nav' => [
        'profile' => 'Perfil',
        'security' => 'Seguridad',
        'appearance' => 'Apariencia',
    ],
    'profile' => [
        'head' => 'Configuración del perfil',
        'title' => 'Perfil',
        'description' => 'Actualiza tu nombre y tu correo electrónico',
    ],
    'delete_account' => [
        'title' => 'Eliminar cuenta',
        'description' => 'Elimina tu cuenta y todos sus datos',
        'warning_title' => 'Atención',
        'warning_body' => 'Ten cuidado: esta acción no se puede deshacer.',
        'button' => 'Eliminar cuenta',
        'dialog_title' => '¿Seguro que quieres eliminar tu cuenta?',
        'dialog_description' => 'Cuando se elimine tu cuenta, todos sus datos se borrarán de forma permanente. Introduce tu contraseña para confirmar.',
        'dialog_description_passwordless' => 'Cuando se elimine tu cuenta, todos sus datos se borrarán de forma permanente. Escribe tu correo para confirmar.',
    ],
    'security' => [
        'head' => 'Configuración de seguridad',
        'title' => 'Cambiar contraseña',
        'description' => 'Usa una contraseña larga y aleatoria para mantener tu cuenta segura',
        'set_title' => 'Crear una contraseña',
        'set_description' => 'Inicias sesión con Google o con un código por correo. Crea una contraseña si también quieres usarla.',
    ],
    'appearance' => [
        'head' => 'Configuración de apariencia',
        'title' => 'Apariencia',
        'description' => 'Elige cómo se ve la aplicación',
        'light' => 'Claro',
        'dark' => 'Oscuro',
        'system' => 'Sistema',
    ],
    'language' => [
        'title' => 'Idioma',
        'description' => 'Elige el idioma de la aplicación y de los correos',
    ],
];
