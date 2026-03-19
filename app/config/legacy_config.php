<?php
// Caminho absoluto no sistema de arquivos (para includes)
define('BASE_para_PATH', __DIR__);

// Caminho relativo para uso em URLs (para imagens, scripts, etc.)
define('BASE_para_URL', dirname($_SERVER['SCRIPT_NAME']));
?>
