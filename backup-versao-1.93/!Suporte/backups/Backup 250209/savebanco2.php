<?php
header('Content-Type: application/json');
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');
// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
// if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
//     // Salva a URL atual para redirecionar o usuário após o login
//     header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?erro=Ocorreu%20um%20erro!%20Talvez%20você%20tenha%20perdido%20sua%20última%20ação.%20Verifique."); // Redireciona para o login com o endereço de volta via GET
//     exit(); // Garante que o código abaixo não será executado
// }
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Habilitar relatórios de erros para depuração
error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'loadData') {
        // Obtém a data selecionada enviada pelo AJAX
        $dataSelecionada = $_POST['dataSelecionada'];
        list($dia, $mes, $ano) = explode('/', $dataSelecionada); // Divide a data em dia, mês e ano

        // Prepara a consulta para buscar os dados da tabela tbChamada
        $stmt = $pdo->prepare("
                                SELECT IdMatricula, IdAluno, presenca, falta, faltajust, Obs
                                FROM tbChamada 
                                WHERE Dia = ? AND Mes = ? AND Ano = ?
                            ");

        $stmt->bindParam(1, $dia, PDO::PARAM_INT);
        $stmt->bindParam(2, $mes, PDO::PARAM_INT);
        $stmt->bindParam(3, $ano, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dados = [];

        if (count($rows) > 0) {
            foreach ($rows as $rowSelect) {
                $dados[] = [
                    'IdMatricula' => $rowSelect['IdMatricula'],
                    'IdAluno'     => $rowSelect['IdAluno'],
                    'presenca'    => $rowSelect['presenca'],
                    'falta'       => $rowSelect['falta'],
                    'faltajust'   => $rowSelect['faltajust'],
                    'Obs'         => $rowSelect['Obs'],
                ];
            }
        } else {
            // Alterado para um retorno mais explicativo
            $dados = ['message' => 'Nenhum dado encontrado'];
        }

        echo json_encode($dados);
    } elseif ($action === 'insertData') {
        try {
            // Lógica para inserir os dados
            $idCurso = $_POST['idCurso'];
            $idTurma = $_POST['idTurma'];
            $cod = $_POST['idColaborador'];
            $idMatricula = $_POST['id__Matricula'];
            $idAluno = $_POST['idAluno'];
            $dataSelecionada = $_POST['dataSelecionada'];
            $selectedAction = $_POST['selectedAction'];
            list($dia, $mes, $ano) = explode('/', $dataSelecionada);

            $f = $selectedAction === 'F' ? 1 : 0;
            $fj = $selectedAction === 'FJ' ? 1 : 0;
            $p = $selectedAction === 'P' ? 1 : 0;

            $sqlInsert = "
            INSERT INTO tbChamada (IdCurso, IdTurma, IdMatricula, IdAluno, Dia, Mes, Ano, presenca, falta, faltajust, IdColaborador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                presenca = VALUES(presenca), 
                falta = VALUES(falta), 
                faltajust = VALUES(faltajust)
        ";
            $stmt = $pdo->prepare($sqlInsert);

            // Vinculando os parâmetros à statement
            $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
            $stmt->bindParam(2, $idTurma, PDO::PARAM_INT);
            $stmt->bindParam(3, $idMatricula, PDO::PARAM_INT);
            $stmt->bindParam(4, $idAluno, PDO::PARAM_INT);
            $stmt->bindParam(5, $dia, PDO::PARAM_INT);
            $stmt->bindParam(6, $mes, PDO::PARAM_INT);
            $stmt->bindParam(7, $ano, PDO::PARAM_INT);
            $stmt->bindParam(8, $p, PDO::PARAM_INT);
            $stmt->bindParam(9, $f, PDO::PARAM_INT);
            $stmt->bindParam(10, $fj, PDO::PARAM_INT);
            $stmt->bindParam(11, $cod, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Falha ao executar a query."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } elseif ($action === 'loadObs') {
        try {
            $idCurso = $_POST['idCurso'];
            $idTurma = $_POST['idTurma'];
            $idMatricula = $_POST['idMatricula'];
            $dataSelecionada = $_POST['dataSelecionada'];

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

            if ($resultado) {
                // Formata a resposta como JSON
                echo json_encode($resultado);
            } else {
                echo json_encode(array('erro' => 'Valor não encontrado'));
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } elseif ($action === 'salvaObs') {
        try {
            $idCurso = $_POST['idCurso'];
            $idTurma = $_POST['idTurma'];
            $idMatricula = $_POST['idMatricula'];
            $dataSelecionada = $_POST['dataSelecionada'];
            $observacoes = $_POST['observacoes'];

            // Divide a data selecionada em dia, mês e ano
            list($dia, $mes, $ano) = explode('/', $dataSelecionada);

            // Converte valores vazios ou somente espaços para NULL
            $obsValue = trim($observacoes) === '' ? null : $observacoes;

            // Prepara a consulta com ON DUPLICATE KEY UPDATE
            $sql = "
            INSERT INTO tbChamada (IdCurso, IdTurma, IdMatricula, Dia, Mes, Ano, Obs)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                Obs = VALUES(Obs),
                presenca = IF(VALUES(presenca) IS NOT NULL AND VALUES(presenca) <> '', VALUES(presenca), presenca),
                falta = IF(VALUES(falta) IS NOT NULL AND VALUES(falta) <> '', VALUES(falta), falta),
                faltajust = IF(VALUES(faltajust) IS NOT NULL AND VALUES(faltajust) <> '', VALUES(faltajust), faltajust);
        ";
            $stmt = $pdo->prepare($sql);
            // Define o valor do último parâmetro, simulando NULL corretamente
            $dummy = $obsValue;

            // Vinculando os parâmetros à statement
            $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
            $stmt->bindParam(2, $idTurma, PDO::PARAM_INT);
            $stmt->bindParam(3, $idMatricula, PDO::PARAM_INT);
            $stmt->bindParam(4, $dia, PDO::PARAM_INT);
            $stmt->bindParam(5, $mes, PDO::PARAM_INT);
            $stmt->bindParam(6, $ano, PDO::PARAM_INT);
            $stmt->bindParam(7, $obsValue, PDO::PARAM_STR);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Observação salva com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar a observação.']);
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
