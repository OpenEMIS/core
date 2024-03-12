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


    'identity_privatekey' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDC3EyU4Q6ZEt9v
gFqggSMQnxHMl7rCTuC7yvTnTkDYxVVSaEZbnhP3YKSimYLty4QR1GKpS4cjB5gc
uz8fuYYjYMiMIU3TudFk0so3zP/tDlErHcI2dAs7yXsBDERjkNPSzPhKRGZaN+io
rYh9uw3dflh9coUn4lkPKexX8opZnruysMUibGT+EP3/hbTpQ5vYneGzMf42kYSY
2KsLLpqADKRwZckOV0r//Za3Gk0NX6n2iD0JFQawXPBiTaUPZ+Gz5hSJuDTOHlL7
rLVX4XdQKgACTqC29e5Pe/f3Vkno6Eg+112Hdo1MqVyvuWMbDoE24SI436xUUO9K
SpFn2dYxAgMBAAECggEAItveJ1QLlH630e6YR9ZSO4r5WCxckJ4jvfSU0zxAhYbn
uJJG4+TnlX2Idj4YGgoqWjYwYDDOwAl/wMQOitJZmMKbndXnYlT/jJXY7xqRPgst
ohT0xWEFEXD9vDZBlb425qMcV8zcso8F5AcHP9bSqCkOE4MFJxlq7TeGvET7UwSu
JhH79lWREIDodTOL2r4buvwqYplGC1Ky65xIcjsG0nv/4mEhgtQucF1hjLK/wYjG
xBpJr3Y6rIasDS3qhMj5AxJ/ewWdPqHsdGAQJEq4nkZc8LbtLsSWzkBI7ZiDW/vr
C43eJsjCmcgOWMAC6ASpa1kDdQXD+Mz2/tpIXXa4AQKBgQD3Byf8sCSui15nm+Mf
SKQgRmCrsEbJrJ1kIKdi8+KprhGBAJWN8IQWajsgQ1J/V3PDXYwZlY5fEhGFipFa
SymlCE4iU5ah/X2BEiOJ7x9WixWGm42YCcyJFJC7tgk5+NcbyYYZAGVQ/vP1P2d+
aSDZ1GtiwcY4yYzPJgJ8itP1kQKBgQDJ8BhURjns/eDFPvOHFCJEbzeXVmzk9QTY
+SCCuuZtE0BZXMApj8UHPhJtJgoWHHMZlhRhbRCFfqW3cfBax0+qi7luB43I39gK
SP1SyvSt5KH8WPT2CRgnl4V/53tVMHQ9rRvzom3gGh5i93lhKDL43+rmYBoKgC1g
mrCPUpsGoQKBgBDAL3n3B8W+NZyY+YG5j0eQ/iUmQuaSCeosPK19FDWlVBKHU5zY
XlyWv4OkjQeNipAI2+MwPQM9WmrPxqN0zVIfigzR1jkN02DZNge4a0uXCtKh4awZ
ngD9oALaiS2hLhT4SVuQp04iu7A5qG7t+ghLWyzLLwHSyPWTAkAnuc/BAoGAawzC
ePp5fq7fVvEWPEdqGwkyWSHRvFY/aZ43o9XcjXolJLpMjDvQ4RQAxKfjtPED+05a
I4OjvID07JiUKCt8ihZkCHYsrY4sgtdKo3c/2mdXj+TAhyUvDt3+QV2/Pdvf40o/
hRpYUPLHpQM3709WJiTd656Kb4Kfi5S1gGT0FcECgYA3ew5sVXzgn0UnN+Of3q7b
T092AoctcYcrKxe0wK8TkXWH7aMNH1tOAQASsVYXeO0fmVjm2h2lgp0/EiHaC6T3
N7pQ5coMe/+FwDK4VoiCCUQZ+Gh+BgpDS6MLcDqCGN53VkxsjB44le3UZs9int6U
FvRYcDKZkv1THQe6Rd0xEQ==
-----END PRIVATE KEY-----'

];
