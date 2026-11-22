<?php

// Server-side messages (flash toasts, notifications). UI strings live in lang/es/ui/.
return [
    'profile_updated' => 'Perfil actualizado.',
    'password_updated' => 'Contraseña actualizada.',
    'login_code' => [
        'subject' => ':code es tu código de acceso',
        'greeting' => '¡Hola!',
        'intro' => 'Usa este código para iniciar sesión:',
        'expires' => 'Caduca en :minutes minutos y solo se puede usar una vez.',
        'ignore' => 'Si no lo pediste, puedes ignorar este correo.',
        'invalid' => 'Este código no es válido o ha caducado. Pide uno nuevo.',
        'send_failed' => 'No pudimos enviar el correo ahora. Inténtalo de nuevo en un momento.',
    ],
    'google' => [
        'failed' => 'No pudimos iniciar sesión con Google. Inténtalo de nuevo.',
        'unverified' => 'El correo de tu cuenta de Google no está verificado.',
    ],
];
