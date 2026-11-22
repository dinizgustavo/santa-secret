<?php

// Server-side messages (flash toasts, notifications). UI strings live in lang/pt_BR/ui/.
return [
    'profile_updated' => 'Perfil atualizado.',
    'password_updated' => 'Senha atualizada.',
    'login_code' => [
        'subject' => ':code é o seu código de acesso',
        'greeting' => 'Olá!',
        'intro' => 'Use este código para entrar:',
        'expires' => 'Ele expira em :minutes minutos e só pode ser usado uma vez.',
        'ignore' => 'Se você não pediu este código, pode ignorar este e-mail.',
        'invalid' => 'Este código é inválido ou expirou. Peça um novo.',
        'send_failed' => 'Não conseguimos enviar o e-mail agora. Tente novamente daqui a pouco.',
    ],
    'google' => [
        'failed' => 'Não conseguimos entrar com o Google. Tente novamente.',
        'unverified' => 'O e-mail da sua conta Google não está verificado.',
    ],
];
