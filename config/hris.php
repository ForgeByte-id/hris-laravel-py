<?php

return [
    'default_import_password' => env('HRIS_DEFAULT_IMPORT_PASSWORD', "password123"),

    'flagging_secret' => env('HRIS_FLAGGING_SECRET'),

    'flaggable_hidden_menus' => [
        'Role Management',
        'Hak Akses',
        'Divisi',
        'Jabatan',
    ],

    'hidden_menus' => [
        'Role Management',
        'Hak Akses',
        'Divisi',
        'Jabatan',
    ],
];
