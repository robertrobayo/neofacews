<?php

return [
    'urlsoap' => "NEC.Identity.NeoFace.API.Watch/mex?WSDL",
    'configsoap' => [
        'login' => "system",
        'password' => "system",
        'exceptions' => true,
    ],
    'messages' => [
        '1' => [
            'code' => 500,
            'message' => "Ha ocurrido un error internamente"
        ],
        '2' => [
            'code' => 400,
            'message' => "Ha ocurrido un error con la comunicación"
        ],
        '3' => [
            'code' => 200,
            'message' => "Información retornada exitosamente"
        ],
        '4' => [
            'code' => 201,
            'message' => "Success enrolment"
        ],
        '5' => [
            'code' => 401,
            'message' => "Invalid extension file"
        ],
        '6' => [
            'code' => 401,
            'message' => "Wroung uploaded file"
        ]
    ]
];