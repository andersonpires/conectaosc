<?php
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$versatilisUsername = bootstrap_env('VERSATILIS_USERNAME', '');
$versatilisPassword = bootstrap_env('VERSATILIS_PASSWORD', '');
$versatilisBaseUrl = bootstrap_env('VERSATILIS_BASE_URL', 'https://sistema.versatilis.com.br/Iteva');
$versatilisDefaultPassword = bootstrap_env('VERSATILIS_DEFAULT_PASSWORD', '');
$versatilisLogPath = bootstrap_env('VERSATILIS_LOG_PATH', dirname(__DIR__) . '/temp/versatilis.log');
