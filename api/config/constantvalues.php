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
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC2mDNmNpFmMn9U
/LuTAG4mIPol6SRoYGPyBjBP0B+GxfyNEpZRKrAZgMHfMmMmqk/mFKlvOa8Fk3Sr
GBP1T45rwDdhxG26Ob8yyK/lwKMCi+v2TujLcSboB1VlMW8PuLviFQIWbM0yIMfX
BRfPntV9dcoO8KIGenfi/S68im2ltKIg+MLGyfgjpVFLDV/pvP6vjpCPYm6dEnu7
7gwJZxS9AWNvqAloeaMvxgofYRs8APpzt4BFZlPOAHv4UY5fpYlVTSOqZSPqtiEj
rJOwnTHc6lGPUHkrLdzI7rYzQ2h22u6j5FbH5ob3KhWd/2B7OgmgUDQpAXuMbQIr
JjFhXE4vAgMBAAECggEAewqAyEXJILayexB7TYmir+rU5ar/L56UesiU0ZOLSwQr
NNzrkfJUDDtpaP/JNIrboE0YB601NWqx2YE2Ib3kWNOD/kuhLTYwkwYNEaMHtXWY
Ibf2wvCSqRQYBUKUdmGjqatCZt4WP8s7Hrd93hhIAGzZJcwdQoRQORMm5UHatSep
OPa8I6HXDz49rP5i0lCJSphqpEALvkot60lSyMNKrZEiBQJFzTXqkQ5PwjgLuK1Z
GI8Nc03jgC9doszf+7tsEE54+9HNUv+6HjRYWE1hpFiycCoiKtMhnPAj8Jghg8WK
GJz+KsLXyue/2hPJ2qojMr86yB+ThF4Apx8S4IgD8QKBgQDp3XG4mE3/tBrrOvG6
9hbU1SSDrQiSoPT3JlXITsl8DmpugV8XB2VISWc7bRBJD7G6butCg95a0TvusiCF
b1GgBeVZUYg4ivIZe0hMYjFvfwDoOB7MTqSNS/iNCvnYI7AHXyB5Pg2gpwBcCSXS
1ocy+hKPmxsaCo2N1b+Y4oUzDQKBgQDH4HirNzPsXzt8nffGSoYztny1f69Q/Hyb
qKu35amZX2T0o+AB+cgO8a5BEKqLeHxFBxqmM4wVbR35lNQYb55DR1g/VlS7xAuC
kwiRwdZr4SxlLGj5pA5YbSwixs6rjxnNsSvYNPDfRvjLk2oOqrFMHIRa+RTWvi+b
cbPYKJznKwKBgFJXgOnw3k2w+WVnfKNKcGGBpniiXQlbmMTIf52md/SxErJT5Moz
9WpNRga7cOd4mig9U6I40fqB/ysdFqxEtKW2Tbl0JpVZ/sIQETWrwSIzwnsA/38K
FZAWdq4adjuu3RTLXqCxw5SUGGFPazzgAfxl5lEf2JwDqTGOWAaZgzKlAoGAA68n
ikBiPKEkv49J2eXVw25BrjbETIHa6iOZxrH9Bk6z1a6Pmnm4Lk5WH+zmt9torv0K
iLW6h0qTmt4barfj+ul9vu0gcrWSYL3FEqq4ARUNdG/1H2TevtKEVb/EX3UrzJbP
cBTJu9PbEiWNdue+jOCZr/OXZr3qsrrNzdYxzjECgYEA1MLomrFew31pvd+letOA
zECeE3WndcaYQmtr5oX8oczcGkkSHBUfC+TjIgYeOEBc5Sv7OEdIVgHAoMYGnL5K
0dp0MErWsDpo4t2PzwtjRWcfNOG6okki5VkDLo8X9eN70FJgBR09+pil/6+mJpcO
ZA+2wTKNehFHLFruHFUzxnE=
-----END PRIVATE KEY-----'

];
