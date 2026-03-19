<?php
session_start();
require_once __DIR__ . '/../../conectabd/conexao.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'listarTarefas':
        try {
            $stmt = $pdo->query("SELECT t.IdTarefa, t.IdUsuario, t.DescricaoTarefa, t.DataHoraExecucao,
                            t.Notificado, t.IdColaborador, t.Status,
                            u.Nome AS NomeColaborador, a.Nome AS NomeAluno
                     FROM tbTarefa t
                     JOIN tbUser u ON t.IdColaborador = u.IdColaborador
                     JOIN tbAluno a ON t.IdUsuario = a.IdUsuario
                     ORDER BY t.DataHoraExecucao DESC");

            $tarefas = [];
            $hoje = new DateTime();
            $hoje->setTime(0, 0);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dataExecucao = new DateTime($row['DataHoraExecucao']);
                $cor = 'branco';
                if ($dataExecucao < $hoje) {
                    $cor = 'vermelho';
                } elseif ($dataExecucao->format('Y-m-d') === $hoje->format('Y-m-d')) {
                    $cor = 'amarelo';
                }
                $row['cor'] = $cor;
                $tarefas[] = $row;
            }

            echo json_encode(['status' => 'ok', 'tarefas' => $tarefas]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
        }
        break;

    case 'marcarFeito':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE tbTarefa SET Status = 'feito' WHERE IdTarefa = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'ID inválido']);
        }
        break;

    case 'excluirTarefa':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM tbTarefa WHERE IdTarefa = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'ID inválido']);
        }
        break;

    case 'buscarAlunosFaltosos':
        $idCurso = $_POST['curso'] ?? '';
        $idTurma = $_POST['turma'] ?? '';
        $minFaltas = intval($_POST['faltas'] ?? 0);
        $dias = intval($_POST['dias'] ?? 0);

        $params = [];
        $where = "WHERE c.falta = 1";

        if (!empty($idCurso) && $idCurso != '0') {
            $where .= " AND t.IdCurso = ?";
            $params[] = $idCurso;
        }

        if (!empty($idTurma)) {
            $where .= " AND t.IdTurma = ?";
            $params[] = $idTurma;
        }

        if ($dias > 0) {
            $dataInicio = (new DateTime())->modify("-$dias days")->format('Y-m-d');
            $where .= " AND c.Data >= ?";
            $params[] = $dataInicio;
        }

        $sql = "SELECT a.IdUsuario, a.Nome, a.Apelido, a.Foto, a.Telefone, a.WhatsApp, a.Endereco, a.Bairro, a.Cidade, a.UF,
               cu.NomeCurso, t.NomeTurma, COUNT(c.falta) AS TotalFaltas,
               (SELECT COUNT(*) FROM tbNota n WHERE n.IdUsuario = a.IdUsuario) AS TotalNotas,
               (SELECT COUNT(*) FROM tbTarefa t WHERE t.IdUsuario = a.IdUsuario AND t.Status = 'pendente') AS TotalTarefas
        FROM tbChamada c
        JOIN tbAluno a ON a.IdUsuario = c.IdAluno
        JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
        JOIN tbTurma t ON c.IdTurma = t.IdTurma
        $where
        GROUP BY a.IdUsuario
        HAVING TotalFaltas >= ?
        ORDER BY a.Nome";


        $params[] = ($minFaltas > 0) ? $minFaltas : 1;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'ok', 'alunos' => $alunos]);
        break;

    case 'dadosAluno':
        $idUsuario = $_POST['idUsuario'] ?? null;

        if (!$idUsuario) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'ID do aluno não fornecido']);
            exit;
        }

        $sql = "SELECT a.Foto, a.Nome, a.Apelido, a.Telefone, a.WhatsApp, a.Endereco, a.Bairro, a.Cidade, a.UF,
                           cu.NomeCurso, t.NomeTurma,
                           (SELECT COUNT(*) FROM tbChamada ch WHERE ch.IdAluno = a.IdUsuario AND ch.falta = 1) AS TotalFaltas
                    FROM tbAluno a
                    LEFT JOIN tbCurso cu ON cu.IdCurso = (
                        SELECT IdCurso FROM tbMatricula m WHERE m.IdUsuario = a.IdUsuario ORDER BY m.IdMatricula DESC LIMIT 1
                    )
                    LEFT JOIN tbTurma t ON t.IdTurma = (
                        SELECT IdTurma FROM tbMatricula m WHERE m.IdUsuario = a.IdUsuario ORDER BY m.IdMatricula DESC LIMIT 1
                    )
                    WHERE a.IdUsuario = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idUsuario]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            echo json_encode(['status' => 'ok', 'aluno' => $aluno]);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Aluno não encontrado']);
        }
        break;

    case 'listarNotasAluno':
        $idUsuario = $_POST['idUsuario'] ?? 0;
        if (!$idUsuario) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'ID do aluno inválido']);
            break;
        }

        $stmt = $pdo->prepare("
                SELECT n.IdNota, n.TextoNota, n.DataHoraCriacao, u.Nome AS NomeColaborador
                FROM tbNota n
                JOIN tbUser u ON n.IdColaborador = u.IdColaborador
                WHERE n.IdUsuario = ?
                ORDER BY n.DataHoraCriacao DESC
            ");
        $stmt->execute([$idUsuario]);
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'ok', 'notas' => $notas]);
        break;

    case 'uploadImagemNota':
        // Permite origens específicas se for usar CORS (opcional)
        $accepted_origins = [
            "http://localhost",
            "http://127.0.0.1",
            "http://localhost:8080", // adicione outras origens conforme necessário
            $_SERVER['HTTP_ORIGIN'] ?? ''
        ];

        if (isset($_SERVER['HTTP_ORIGIN']) && !in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
            header("HTTP/1.1 403 Origin Denied");
            exit;
        }

        // Pasta de destino
        $dir = __DIR__ . '/../../assets/img/notas/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);

        // Upload
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum arquivo enviado']);
            exit;
        }

        $arquivo = $_FILES['file'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extPermitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extensao, $extPermitidas)) {
            http_response_code(400);
            echo json_encode(['error' => 'Extensão de arquivo inválida']);
            exit;
        }

        // Gera nome único
        $nome = uniqid('nota_') . '.' . $extensao;
        $destino = $dir . $nome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao mover a imagem']);
            exit;
        }

        // Monta URL absoluta correta
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $urlFinal = $protocolo . $host . $_SESSION['BASE_URL'] . '/assets/img/notas/' . $nome;

        // Limpa qualquer saída anterior
        if (ob_get_length()) ob_end_clean();

        // Envia o JSON corretamente formatado
        echo json_encode(['location' => $urlFinal], JSON_UNESCAPED_SLASHES);
        break;

    case 'salvarNota':
        $idUsuario = $_POST['idUsuario'] ?? null;
        $textoNota = $_POST['textoNota'] ?? '';
        $idColaborador = $_SESSION['Cod'] ?? null;

        if (!$idUsuario) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Sem Id Usuário']);
            break;
        }
        if (!$idColaborador) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Sem Id Colaborador']);
            break;
        }
        if (empty($textoNota)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Sem texto da nota']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO tbNota (IdUsuario, IdColaborador, TextoNota) VALUES (?, ?, ?)");
        $sucesso = $stmt->execute([$idUsuario, $idColaborador, $textoNota]);

        $stmt2 = $pdo->prepare("SELECT Nome FROM tbAluno WHERE IdUsuario=?");
        $stmt2->execute([$idUsuario]);
        $aluno = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($sucesso && $aluno) {
            require_once 'notificarUsuario.php';
            $mensagemNotificacao =  notificarUsuario($pdo, $idColaborador, 'nota', $aluno['Nome']); // Chama notificação
        }
        echo json_encode(['status' => $sucesso ? 'ok' : 'erro', 'mensagem' => $mensagemNotificacao ?? '']);
        break;

    case 'salvarTarefa':
        $idUsuario = $_POST['idUsuario'] ?? null;
        $descricaoTarefa = $_POST['descricaoTarefa'] ?? '';
        $dataExecucao = $_POST['dataExecucao'] ?? '';
        $idColaborador = $_SESSION['Cod'] ?? null;

        if (!$idUsuario || !$idColaborador || empty($descricaoTarefa) || empty($dataExecucao)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO tbTarefa (IdUsuario, IdColaborador, DescricaoTarefa, DataHoraExecucao) VALUES (?, ?, ?, ?)");
        $sucesso = $stmt->execute([$idUsuario, $idColaborador, $descricaoTarefa, $dataExecucao]);

        // Busca nome do aluno
        $stmt2 = $pdo->prepare("SELECT Nome FROM tbAluno WHERE IdUsuario = ?");
        $stmt2->execute([$idUsuario]);
        $aluno = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($sucesso && $aluno) {
            // require_once 'notificarUsuario.php';
            // $mensagemNotificacao = notificarUsuario($pdo, $idColaborador, 'tarefa', $aluno['Nome']);
        }

        echo json_encode(['status' => $sucesso ? 'ok' : 'erro', 'mensagem' => $mensagemNotificacao ?? '']);
        break;


    case 'dadosColaborador':
        $id = $_SESSION['Cod'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("SELECT Email, WhatsApp FROM tbUser WHERE IdColaborador = ?");
            $stmt->execute([$id]);
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'ok', 'colaborador' => $colaborador]);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado']);
        }
        break;

    case 'getPreferenciasNotificacao':
        $id = $_SESSION['Cod'] ?? 0;

        if (!$id) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador não identificado']);
            break;
        }

        // Busca email e zap do user
        $stmt = $pdo->prepare("SELECT Email, WhatsApp FROM tbUser WHERE IdColaborador = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Busca ou cria a preferência
        $stmt = $pdo->prepare("SELECT * FROM tbNotificacao WHERE IdColaborador = ?");
        $stmt->execute([$id]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prefs) {
            // Cria com tudo desmarcado
            $pdo->prepare("INSERT INTO tbNotificacao (IdColaborador) VALUES (?)")->execute([$id]);
            $prefs = [
                'Nota_Email' => 0,
                'Nota_WhatsApp' => 0,
                'Tarefa_Email' => 0,
                'Tarefa_WhatsApp' => 0
            ];
        }

        echo json_encode([
            'status' => 'ok',
            'preferencias' => array_merge($prefs, $usuario)
        ]);
        break;

    case 'salvarPreferenciasNotificacao':
        $id = $_SESSION['Cod'] ?? 0;
        if (!$id) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Colaborador não identificado']);
            break;
        }

        $nota_email = $_POST['nota_email'] ?? 0;
        $nota_zap = $_POST['nota_whatsapp'] ?? 0;
        $tarefa_email = $_POST['tarefa_email'] ?? 0;
        $tarefa_zap = $_POST['tarefa_whatsapp'] ?? 0;

        // Garante que a linha exista
        $pdo->prepare("INSERT IGNORE INTO tbNotificacao (IdColaborador) VALUES (?)")->execute([$id]);

        $stmt = $pdo->prepare("UPDATE tbNotificacao 
                                       SET Nota_Email = ?, Nota_WhatsApp = ?, 
                                           Tarefa_Email = ?, Tarefa_WhatsApp = ?
                                       WHERE IdColaborador = ?");
        $ok = $stmt->execute([$nota_email, $nota_zap, $tarefa_email, $tarefa_zap, $id]);

        echo json_encode(['status' => $ok ? 'ok' : 'erro']);
        break;

    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
        break;
}
