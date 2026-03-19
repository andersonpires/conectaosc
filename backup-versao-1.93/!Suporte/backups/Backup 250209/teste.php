<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
$idCurso = 8;
$idTurma = 5;
$idMatricula = 212;
$dataSelecionada = "03/02/2025";

// Divide a data em dia, mês e ano
list($dia, $mes, $ano) = explode('/', $dataSelecionada);

// Consulta para buscar o campo Obs
$stmt = $pdo->prepare("
                                    SELECT Obs 
                                    FROM tbChamada 
                                    WHERE IdCurso = :idCurso AND IdTurma = :idTurma 
                                    AND IdMatricula = :idMatricula AND Dia = :dia 
                                    AND Mes = :mes AND Ano = :ano
                                ");

// Bind dos parâmetros
$stmt->bindParam(':idCurso', $idCurso, PDO::PARAM_INT);
$stmt->bindParam(':idTurma', $idTurma, PDO::PARAM_INT);
$stmt->bindParam(':idMatricula', $idMatricula, PDO::PARAM_INT);
$stmt->bindParam(':dia', $dia, PDO::PARAM_INT);
$stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
$stmt->bindParam(':ano', $ano, PDO::PARAM_INT);

// Executa a consulta
$stmt->execute();
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtém uma única linha da consulta
$dados = [];

/*if ($rows) { // Verifica se há dados na linha
                $dados = [
                    'Obs' => $rows['Obs'] // Aqui você coloca o valor do campo 'Obs' do resultado
                ];
            } else {
                echo $dados = ['Obs' => null];
            }
            echo json_encode($dados);*/
if ($resultado) {
    // Formata a resposta como JSON
    echo json_encode($resultado);
} else {
    echo json_encode(array('erro' => 'Valor não encontrado'));
}


