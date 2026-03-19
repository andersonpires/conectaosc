<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');


// Definir a data e hora no formato desejado (ex: backup_2025-04-26_10-46-00.sql)
$dataHora = date('Y-m-d_H-i-s');

// Montar o comando mysqldump com o nome do arquivo dinâmico
$comando = "mysqldump -u mwtech63_admin_matricula -pIteva@100 mwtech63_matricula > backup_{$dataHora}.sql";
$fileUpload = 'backup_' . $dataHora . '.sql';
// Executar o comando
shell_exec($comando);
