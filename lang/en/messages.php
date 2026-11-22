<?php

// Server-side messages (flash toasts, notifications). UI strings live in lang/en/ui/.
return [
    'profile_updated' => 'Profile updated.',
    'password_updated' => 'Password updated.',
    'login_code' => [
        'subject' => ':code is your sign-in code',
        'greeting' => 'Hi!',
        'intro' => 'Use this code to sign in:',
        'expires' => 'It expires in :minutes minutes and can only be used once.',
        'ignore' => 'If you did not ask for it, you can safely ignore this e-mail.',
        'invalid' => 'This code is invalid or has expired. Request a new one.',
        'send_failed' => 'We could not send the e-mail right now. Please try again in a moment.',
    ],
    'google' => [
        'failed' => 'We could not sign you in with Google. Please try again.',
        'unverified' => 'Your Google account e-mail is not verified.',
    ],
];
