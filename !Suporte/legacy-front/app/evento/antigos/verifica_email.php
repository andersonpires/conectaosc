<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
header('Content-Type: application/json');
include 'conexao.php'; // Inclua sua conexão com o banco

$email = $_GET['email'] ? '';
$response = ['exists' => false];

if (!empty($email)) {
    $sql = "SELECT COUNT(*) as total FROM InscritosEvento WHERE email = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($total > 0) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
$stmt->close();
$conexao->close();
?>


