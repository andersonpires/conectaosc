<?php
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$apiKey = bootstrap_env('OPENAI_API_KEY', bootstrap_env('API_OPENAI_KEY', ''));
