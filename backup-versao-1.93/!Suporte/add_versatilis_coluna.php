<?php
header('Content-Type: text/plain; charset=UTF-8');

require_once dirname(__DIR__) . '/conectabd/conexao.php';

try {
    $dbName = $pdo->query('select database()')->fetchColumn();

    $stmt = $pdo->prepare(
        'select count(*) from information_schema.columns
         where table_schema = :schema
           and table_name = :table
           and column_name = :column'
    );
    $stmt->execute([
        ':schema' => $dbName,
        ':table' => 'tbAluno',
        ':column' => 'Versatilis',
    ]);

    $exists = (int) $stmt->fetchColumn() > 0;
    if ($exists) {
        echo "Coluna Versatilis ja existe em tbAluno.\n";
        exit;
    }

    $pdo->exec('alter table tbAluno add column Versatilis tinyint(1) null default null after Habilitado');
    echo "Coluna Versatilis criada com sucesso em tbAluno.\n";
} catch (Throwable $e) {
    echo "Erro ao criar a coluna Versatilis: " . $e->getMessage() . "\n";
    exit(1);
}
