<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_connect.php';  // Inclui o arquivo de conexão ao banco de dados

    $id = $_POST['id'];
    $nome = $_POST['nome'];
    // Outros campos...

    // Usando consultas preparadas para evitar injeção de SQL
    $stmt = $conn->prepare("UPDATE t_usuario SET Nome = ? WHERE IdUsuario = ?");
    $stmt->bind_param("si", $nome, $id);  // 's' para string, 'i' para inteiro
    $stmt->execute();

    if ($stmt->error) {
        // Tratar erro aqui
    } else {
        // Redirecionar para index.php ou mostrar mensagem de sucesso
    }

    $stmt->close();
    $conn->close();
}
// Código para exibir o formulário de edição...
?>
