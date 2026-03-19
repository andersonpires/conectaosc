<?php
$files = [
  'app/views/config/formConfigView.php',
  'app/views/atendimento/index.php',
  'app/views/partials/corpo.php'
];

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }

    // Apply up to 3 passes to fix single/double mojibake.
    for ($i = 0; $i < 3; $i++) {
        $fixed = utf8_encode(utf8_decode($content));
        if ($fixed === $content) {
            break;
        }
        $content = $fixed;
    }

    file_put_contents($file, $content);
}

echo "done\n";
?>
