<?php
$runtime = require __DIR__ . '/bootstrap/runtime.php';
echo 'base_url=' . ($runtime['base_url'] ?? '') . PHP_EOL;
echo 'base_para_url=' . ($runtime['base_para_url'] ?? '') . PHP_EOL;
