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

    'SALT' => '3b07b2f17a71b29db58115fbea9e2a03385eb4d224c07b5fba3b0f67cddc082f',

    'userTypes' => [
        1 => 'STUDENT',
        2 => 'STAFF',
        3 => 'GUARDIAN',
        4 => 'OTHER',
    ],


    'positionTypes' => [
        'Full-Time' => 'Full-Time',
        'Part-Time' => 'Part-Time'
    ],


    'fteList' => [
        '0.25' => '25%',
        '0.5' => '50%',
        '0.75' => '75%'
    ],

];
