<?php

return [
    'rate_limits' => [
        'read_per_minute' => (int) env('PRO_API_RATE_LIMIT_READ', 120),
        'write_per_minute' => (int) env('PRO_API_RATE_LIMIT_WRITE', 30),
    ],
];
