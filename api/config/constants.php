<?php

return [
    'statusCodes' => [
        'resourceNotFound' => 404,
        'internalError' => 500,
        'success' => 200,
        'deleteError' => 403,
        'fieldNotFound' => 422
    ],

    'defaultPaginateLimit' => 10,

    'canLogIn' => [
        'superAdmin' => 1,
        'isStaff' => 1
    ],

    'emailConfig' => [
        'generateOtpEmail' => [
            'subject' => 'OpenEMIS - One-time Password (OTP)' 
        ],
        'registrationSuccessEmail' => [
            'subject' => 'OpenEMIS - Successful Registration'
        ]
    ],

];
