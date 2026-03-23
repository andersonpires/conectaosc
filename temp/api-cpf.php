<?php
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$apiKeyCPF = bootstrap_env('CPF_API_KEY', bootstrap_env('APICPF_API_KEY', ''));
