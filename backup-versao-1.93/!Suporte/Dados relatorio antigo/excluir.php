<?php
if (isset($_GET['id'])) {
    include 'db_connect.php';  // Inclui o arquivo de conexão ao banco de dados

    $id = $_GET['id'];

    // Usando consultas preparadas para evitar injeção de SQL
    $stmt = $conn->prepare("DELETE FROM t_usuario WHERE IdUsuario = ?");
    $stmt->bind_param("i", $id);  // 'i' para inteiro
    $stmt->execute();

    if ($stmt->error) {
        // Tratar erro aqui
    } else {
        // Redirecionar para index.php ou mostrar mensagem de sucesso
    }

    $stmt->close();
    $conn->close();
}
?>
