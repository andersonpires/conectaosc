<?php
return [
    'env' => (stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || stripos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? 'development' : 'production',
    'timezone' => 'America/Sao_Paulo',
    'rate_limit_requests' => 60,
    'rate_limit_window' => 60,
];
