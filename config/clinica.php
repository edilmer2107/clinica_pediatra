<?php

return [
    'version' => '1.0.0',

    // Política de cancelación/reprogramación de citas (horas de anticipación)
    'horas_minimas_cancelacion' => 24,

    // Días para considerar un lote "próximo a vencer"
    'dias_alerta_vencimiento' => 90,

    // Impuesto General a las Ventas (Perú)
    'igv' => 0.18,

    // Días de crédito para facturas
    'dias_credito' => 30,

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'), // log | android
        'url' => env('SMS_GATEWAY_URL', 'https://api.sms-gate.app/3rdparty/v1/message'),
        'usuario' => env('SMS_GATEWAY_USER'),
        'password' => env('SMS_GATEWAY_PASSWORD'),
    ],

    'telemedicina_url' => env('TELEMEDICINE_BASE_URL', 'https://meet.jit.si'),
];
