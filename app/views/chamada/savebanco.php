<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once __DIR__ . '/../../../api/conectabd/conexao.php';
require_once __DIR__ . '/funcoes.php';

// Habilitar relatórios de erros para depuração
bootstrap_apply_php_runtime();

function chamadaResponderJson(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function chamadaIdsMatriculaPost(): array
{
    $ids = $_POST['idsMatricula'] ?? [];
    if (!is_array($ids)) {
        $ids = explode(',', (string)$ids);
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
    return $ids;
}

function chamadaValidarDataPost(?string $dataSelecionada): ?array
{
    $dataSelecionada = trim((string)$dataSelecionada);
    $partes = explode('/', $dataSelecionada);
    if (count($partes) !== 3) {
        return null;
    }

    [$dia, $mes, $ano] = array_map('intval', $partes);
    if (!checkdate($mes, $dia, $ano)) {
        return null;
    }

    return [
        'dia' => $dia,
        'mes' => $mes,
        'ano' => $ano,
        'iso' => sprintf('%04d-%02d-%02d', $ano, $mes, $dia),
    ];
}

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
            $dados = ['success'];
        }

        echo json_encode($dados);
    } elseif ($action === 'loadHistoricoData') {
        $idAluno = $_POST['idAluno'];
        $idCurso = $_POST['idCurso'];

        $stmt = $pdo->prepare("
                SELECT IdMatricula, IdAluno, Data, presenca, falta, faltajust, Obs
                FROM tbChamada
                WHERE IdAluno = ? AND IdCurso = ?
                ORDER BY Data
            ");
        $stmt->execute([$idAluno, $idCurso]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dados = [];

        foreach ($rows as $row) {
            $dados[] = [
                'IdMatricula' => $row['IdMatricula'],
                'IdAluno'     => $row['IdAluno'],
                'Data'        => $row['Data'],
                'presenca'    => $row['presenca'],
                'falta'       => $row['falta'],
                'faltajust'   => $row['faltajust'],
                'Obs'         => $row['Obs']
            ];
        }

        echo json_encode($dados);
        exit;
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
            $data = $ano . '-' . $mes . '-' . $dia;
            $f = $selectedAction === 'F' ? 1 : 0;
            $fj = $selectedAction === 'FJ' ? 1 : 0;
            $p = $selectedAction === 'P' ? 1 : 0;

            $sqlInsert = "
            INSERT INTO tbChamada (IdCurso, IdTurma, IdMatricula, IdAluno, Dia, Mes, Ano, Data, presenca, falta, faltajust, IdColaborador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                presenca = VALUES(presenca), 
                falta = VALUES(falta), 
                faltajust = VALUES(faltajust),
                IdColaborador = VALUES(IdColaborador)
        ";
            $stmt = $pdo->prepare($sqlInsert);

            // Vinculando os parâmetros ao statement
            $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
            $stmt->bindParam(2, $idTurma, PDO::PARAM_INT);
            $stmt->bindParam(3, $idMatricula, PDO::PARAM_INT);
            $stmt->bindParam(4, $idAluno, PDO::PARAM_INT);
            $stmt->bindParam(5, $dia, PDO::PARAM_INT);
            $stmt->bindParam(6, $mes, PDO::PARAM_INT);
            $stmt->bindParam(7, $ano, PDO::PARAM_INT);
            $stmt->bindParam(8, $data, PDO::PARAM_STR);
            $stmt->bindParam(9, $p, PDO::PARAM_INT);
            $stmt->bindParam(10, $f, PDO::PARAM_INT);
            $stmt->bindParam(11, $fj, PDO::PARAM_INT);
            $stmt->bindParam(12, $cod, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Falha ao executar a query."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } elseif ($action === 'bulkChamada') {
        try {
            $idCurso = (int)($_POST['idCurso'] ?? 0);
            $idTurma = (int)($_POST['idTurma'] ?? 0);
            $idColaborador = (int)($_POST['idColaborador'] ?? 0);
            $selectedAction = (string)($_POST['selectedAction'] ?? '');
            $dataInfo = chamadaValidarDataPost($_POST['dataSelecionada'] ?? null);
            $idsMatricula = chamadaIdsMatriculaPost();

            if (!in_array($selectedAction, ['P', 'F'], true)) {
                chamadaResponderJson(['success' => false, 'message' => 'Ação em lote inválida.']);
            }
            if ($idCurso <= 0 || $idTurma <= 0 || !$dataInfo || $idsMatricula === []) {
                chamadaResponderJson(['success' => false, 'message' => 'Dados insuficientes para atualizar a chamada.']);
            }

            $placeholders = implode(',', array_fill(0, count($idsMatricula), '?'));
            $stmtAlunos = $pdo->prepare("
                SELECT IdMatricula, IdUsuario AS IdAluno
                FROM tbMatricula
                WHERE IdCurso = ?
                  AND IdTurma = ?
                  AND Habilitado = 1
                  AND IdMatricula IN ($placeholders)
            ");
            $stmtAlunos->execute(array_merge([$idCurso, $idTurma], $idsMatricula));
            $matriculas = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);

            if ($matriculas === []) {
                chamadaResponderJson(['success' => false, 'message' => 'Nenhuma matrícula válida foi encontrada para a listagem atual.']);
            }

            $presenca = $selectedAction === 'P' ? 1 : 0;
            $falta = $selectedAction === 'F' ? 1 : 0;
            $faltajust = 0;

            $sqlInsert = "
                INSERT INTO tbChamada (IdCurso, IdTurma, IdMatricula, IdAluno, Dia, Mes, Ano, Data, presenca, falta, faltajust, IdColaborador)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    IdAluno = VALUES(IdAluno),
                    presenca = VALUES(presenca),
                    falta = VALUES(falta),
                    faltajust = VALUES(faltajust),
                    IdColaborador = VALUES(IdColaborador)
            ";
            $stmt = $pdo->prepare($sqlInsert);

            $pdo->beginTransaction();
            foreach ($matriculas as $matricula) {
                $stmt->execute([
                    $idCurso,
                    $idTurma,
                    (int)$matricula['IdMatricula'],
                    (int)$matricula['IdAluno'],
                    $dataInfo['dia'],
                    $dataInfo['mes'],
                    $dataInfo['ano'],
                    $dataInfo['iso'],
                    $presenca,
                    $falta,
                    $faltajust,
                    $idColaborador,
                ]);
            }
            $pdo->commit();

            chamadaResponderJson([
                'success' => true,
                'message' => 'Chamada atualizada com sucesso.',
                'total' => count($matriculas),
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            chamadaResponderJson(['success' => false, 'message' => 'Erro ao atualizar chamada em lote: ' . $e->getMessage()]);
        }
    } elseif ($action === 'resetChamada') {
        try {
            if (!chamadaUsuarioPodeRedefinir($_SESSION ?? [])) {
                chamadaResponderJson(['success' => false, 'message' => 'Permissão de Admin necessária.']);
            }

            $idCurso = (int)($_POST['idCurso'] ?? 0);
            $idTurma = (int)($_POST['idTurma'] ?? 0);
            $dataInfo = chamadaValidarDataPost($_POST['dataSelecionada'] ?? null);
            $idsMatricula = chamadaIdsMatriculaPost();

            if ($idCurso <= 0 || $idTurma <= 0 || !$dataInfo || $idsMatricula === []) {
                chamadaResponderJson(['success' => false, 'message' => 'Dados insuficientes para redefinir a chamada.']);
            }

            $placeholders = implode(',', array_fill(0, count($idsMatricula), '?'));
            $stmt = $pdo->prepare("
                DELETE FROM tbChamada
                WHERE IdCurso = ?
                  AND IdTurma = ?
                  AND Dia = ?
                  AND Mes = ?
                  AND Ano = ?
                  AND IdMatricula IN ($placeholders)
            ");
            $stmt->execute(array_merge([
                $idCurso,
                $idTurma,
                $dataInfo['dia'],
                $dataInfo['mes'],
                $dataInfo['ano'],
            ], $idsMatricula));

            chamadaResponderJson([
                'success' => true,
                'message' => 'Chamada redefinida com sucesso.',
                'total' => $stmt->rowCount(),
            ]);
        } catch (Throwable $e) {
            chamadaResponderJson(['success' => false, 'message' => 'Erro ao redefinir chamada: ' . $e->getMessage()]);
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
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Formata a resposta como JSON
                echo json_encode(['Obs' => $row['Obs']]);
            } else {
                echo json_encode(['Obs' => null]);
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

            // Resolve IdAluno from IdMatricula to keep tbChamada consistent.
            $stmtAluno = $pdo->prepare("SELECT IdUsuario FROM tbMatricula WHERE IdMatricula = ? LIMIT 1");
            $stmtAluno->execute([$idMatricula]);
            $rowAluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
            if (!$rowAluno || empty($rowAluno['IdUsuario'])) {
                echo json_encode(['success' => false, 'message' => 'Matrícula não encontrada para registrar observação.']);
                exit;
            }
            $idAluno = (int)$rowAluno['IdUsuario'];

            // Divide a data selecionada em dia, mês e ano
            list($dia, $mes, $ano) = explode('/', $dataSelecionada);
            $data = $ano . '-' . $mes . '-' . $dia;
            // Converte valores vazios ou somente espaços para NULL
            $obsValue = trim($observacoes) === '' ? null : $observacoes;

            // Prepara a consulta com ON DUPLICATE KEY UPDATE
            $sql = "
                INSERT INTO tbChamada (IdCurso, IdTurma, IdMatricula, IdAluno, Dia, Mes, Ano, Data, Obs)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                IdAluno = VALUES(IdAluno),
                Obs = VALUES(Obs);
            ";

            $stmt = $pdo->prepare($sql);
            // Define o valor do último parâmetro, simulando NULL corretamente
            $dummy = $obsValue;

            // Vinculando os parâmetros ao statement
            $stmt->bindParam(1, $idCurso, PDO::PARAM_INT);
            $stmt->bindParam(2, $idTurma, PDO::PARAM_INT);
            $stmt->bindParam(3, $idMatricula, PDO::PARAM_INT);
            $stmt->bindParam(4, $idAluno, PDO::PARAM_INT);
            $stmt->bindParam(5, $dia, PDO::PARAM_INT);
            $stmt->bindParam(6, $mes, PDO::PARAM_INT);
            $stmt->bindParam(7, $ano, PDO::PARAM_INT);
            $stmt->bindParam(8, $data, PDO::PARAM_STR);
            $stmt->bindValue(9, $obsValue, $obsValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Observação salva com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar a observação.']);
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => 'Primeiro faça a chamada do aluno(a), para depois inserir uma observação.']);
        }
    } elseif ($action === 'getFaltas') {
        try {
            $idMatricula = $_POST['id__Matricula'];
            $stmt = $pdo->prepare("
                SELECT
                    SUM(CASE WHEN falta = 1 THEN 1 ELSE 0 END) AS totalFaltas,
                    SUM(CASE WHEN faltajust = 1 THEN 1 ELSE 0 END) AS totalFaltasJustificadas
                FROM tbChamada 
                WHERE IdMatricula = ?
            ");
            $stmt->execute([$idMatricula]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'totalFaltas' => (int)($row['totalFaltas'] ?? 0),
                'totalFaltasJustificadas' => (int)($row['totalFaltasJustificadas'] ?? 0),
            ]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } elseif ($action === 'deleteAula') {
        try {
            $idMatricula = $_POST['IdMatricula'];
            $dataSelecionada = $_POST['dataSelecionada'];

            list($dia, $mes, $ano) = explode('/', $dataSelecionada);

            // Primeiro verifica quantos registros existem
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) AS total 
                FROM tbChamada 
                WHERE IdMatricula = ? AND Dia = ? AND Mes = ? AND Ano = ?
            ");
            $checkStmt->execute([$idMatricula, $dia, $mes, $ano]);
            $total = $checkStmt->fetchColumn();

            if ($total == 1) {
                // Só deleta se houver exatamente 1 registro
                $stmt = $pdo->prepare("
                    DELETE FROM tbChamada 
                    WHERE IdMatricula = ? AND Dia = ? AND Mes = ? AND Ano = ?
                ");
                $stmt->execute([$idMatricula, $dia, $mes, $ano]);

                echo json_encode(['success' => true, 'message' => 'Registro removido com sucesso.']);
            } elseif ($total > 1) {
                echo json_encode(['success' => false, 'message' => 'Erro: há mais de um registro com esses dados. Nenhuma exclusão foi feita.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhum registro encontrado para remover.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover registro: ' . $e->getMessage()]);
        }
    } elseif ($action === 'deleteAulasMultiplo') {
        $idMatricula = $_POST['IdMatricula'] ?? null;
        $datasSelecionadas = $_POST['datasSelecionadas'] ?? [];

        if (!$idMatricula || empty($datasSelecionadas)) {
            echo json_encode(['success' => false, 'message' => 'Dados insuficientes para excluir aulas.']);
            exit;
        }

        $erros = [];
        $sucesso = [];

        foreach ($datasSelecionadas as $dataSelecionada) {
            list($dia, $mes, $ano) = explode('/', $dataSelecionada);

            try {
                // Verifica se existe registro
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) AS total 
                    FROM tbChamada 
                    WHERE IdMatricula = ? AND Dia = ? AND Mes = ? AND Ano = ?
                ");
                $checkStmt->execute([$idMatricula, $dia, $mes, $ano]);
                $total = $checkStmt->fetchColumn();

                if ($total == 1) {
                    // Deleta apenas se existir 1
                    $stmt = $pdo->prepare("
                        DELETE FROM tbChamada 
                        WHERE IdMatricula = ? AND Dia = ? AND Mes = ? AND Ano = ?
                    ");
                    $stmt->execute([$idMatricula, $dia, $mes, $ano]);
                    $sucesso[] = $dataSelecionada;
                } elseif ($total > 1) {
                    $erros[] = "Mais de um registro encontrado para a data $dataSelecionada.";
                } else {
                    $erros[] = "Nenhum registro encontrado para a data $dataSelecionada.";
                }
            } catch (PDOException $e) {
                $erros[] = "Erro ao excluir a data $dataSelecionada: " . $e->getMessage();
            }
        }

        if (empty($sucesso)) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma aula foi removida. ' . implode(' ', $erros)]);
        } elseif (!empty($erros)) {
            echo json_encode(['success' => false, 'message' => 'Algumas aulas foram removidas, mas houve problemas: ' . implode(' ', $erros)]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Todas as aulas selecionadas foram removidas com sucesso.']);
        }
    } elseif ($_POST['action'] === 'matricular-desmatricular') {
        $idAluno = $_POST['idAluno'];

        $stmt = $pdo->prepare("SELECT Habilitado, IdCurso FROM tbMatricula WHERE IdUsuario = ? LIMIT 1");
        $stmt->execute([$idAluno]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $novoStatus = $dados['Habilitado'] == 1 ? 0 : 1;
            $stmtUpdate = $pdo->prepare("UPDATE tbMatricula SET Habilitado = ? WHERE IdUsuario = ? AND IdCurso = ?");
            $stmtUpdate->execute([$novoStatus, $idAluno, $dados['IdCurso']]);

            echo json_encode(['success' => true, 'novoStatus' => $novoStatus]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Matrícula não encontrada.']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}

// $arquivo = fopen("erro.txt", "a");

// // Verifica se o arquivo foi aberto com sucesso
// if ($arquivo) {
//     // Obtém a data e hora atual
//     $dataHora = date("Y-m-d H:i:s");

//     // Grava a data e hora, e os dados do POST no arquivo
//     fwrite($arquivo, "[$dataHora] Dados recebidos via POST:\n");
//     fwrite($arquivo, print_r($_POST, true));

//     fwrite($arquivo, "\n-------------------\n");
// Comentário ajustado para UTF-8.
//     $dataString = serialize($data);
//     fwrite($arquivo, $dataString);

//     fwrite($arquivo, "\n-------------------\n");

//     // Fecha o arquivo
//     fclose($arquivo);
// } else {
// Comentário ajustado para UTF-8.
//     echo "Erro ao abrir o arquivo erro.txt";
// }






