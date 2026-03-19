<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
require_once __DIR__ . '/../controllers/BeneficiarioCadastroFlow.php';

$flow = new \FrontEnd\Controllers\BeneficiarioCadastroFlow(
    (string) ($runtime['base_path'] ?? ''),
    (string) ($runtime['base_url'] ?? '')
);
$flow->handle();

