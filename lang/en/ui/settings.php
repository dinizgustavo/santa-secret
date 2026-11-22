<?php

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',
    'nav' => [
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
    ],
    'profile' => [
        'head' => 'Profile settings',
        'title' => 'Profile',
        'description' => 'Update your name and email address',
    ],
    'delete_account' => [
        'title' => 'Delete account',
        'description' => 'Delete your account and all of its data',
        'warning_title' => 'Warning',
        'warning_body' => 'Please proceed with caution, this cannot be undone.',
        'button' => 'Delete account',
        'dialog_title' => 'Are you sure you want to delete your account?',
        'dialog_description' => 'Once your account is deleted, all of its data will be permanently deleted. Please enter your password to confirm.',
        'dialog_description_passwordless' => 'Once your account is deleted, all of its data will be permanently deleted. Type your email address to confirm.',
    ],
    'security' => [
        'head' => 'Security settings',
        'title' => 'Update password',
        'description' => 'Ensure your account is using a long, random password to stay secure',
        'set_title' => 'Set a password',
        'set_description' => 'You sign in with Google or an emailed code. Add a password if you also want to sign in with it.',
    ],
    'appearance' => [
        'head' => 'Appearance settings',
        'title' => 'Appearance settings',
        'description' => 'Update the appearance settings for your account',
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],
    'language' => [
        'title' => 'Language',
        'description' => 'Choose the language used across the app and in emails',
    ],
];
