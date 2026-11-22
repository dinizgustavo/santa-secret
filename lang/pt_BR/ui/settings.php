<?php

return [
    'title' => 'Configurações',
    'description' => 'Gerencie seu perfil e sua conta',
    'nav' => [
        'profile' => 'Perfil',
        'security' => 'Segurança',
        'appearance' => 'Aparência',
    ],
    'profile' => [
        'head' => 'Configurações do perfil',
        'title' => 'Perfil',
        'description' => 'Atualize seu nome e seu e-mail',
    ],
    'delete_account' => [
        'title' => 'Excluir conta',
        'description' => 'Exclua sua conta e todos os seus dados',
        'warning_title' => 'Atenção',
        'warning_body' => 'Tenha cuidado: esta ação não pode ser desfeita.',
        'button' => 'Excluir conta',
        'dialog_title' => 'Tem certeza de que deseja excluir sua conta?',
        'dialog_description' => 'Depois que sua conta for excluída, todos os seus dados serão apagados permanentemente. Digite sua senha para confirmar.',
        'dialog_description_passwordless' => 'Depois que sua conta for excluída, todos os seus dados serão apagados permanentemente. Digite seu e-mail para confirmar.',
    ],
    'security' => [
        'head' => 'Configurações de segurança',
        'title' => 'Alterar senha',
        'description' => 'Use uma senha longa e aleatória para manter sua conta segura',
        'set_title' => 'Criar uma senha',
        'set_description' => 'Você entra com o Google ou com um código por e-mail. Crie uma senha se também quiser entrar com ela.',
    ],
    'appearance' => [
        'head' => 'Configurações de aparência',
        'title' => 'Aparência',
        'description' => 'Escolha como o app aparece para você',
        'light' => 'Claro',
        'dark' => 'Escuro',
        'system' => 'Sistema',
    ],
    'language' => [
        'title' => 'Idioma',
        'description' => 'Escolha o idioma usado no app e nos e-mails',
    ],
];
