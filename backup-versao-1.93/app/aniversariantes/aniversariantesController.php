<?php
// aniversariantesController.php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'msg' => 'Acesso não autorizado.']);
    exit();
}

header('Content-Type: application/json');
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$action = $_POST['action'] ?? '';

if ($action === 'buscarAniversariantes') {
    $mes = $_POST['mes'] ?? '';

    if (!is_numeric($mes) || $mes < 1 || $mes > 12) {
        echo json_encode(['status' => 'erro', 'msg' => 'Mês inválido.']);
        exit();
    }

    // Garante 2 dígitos para o mês
    $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

    try {
        $stmt = $pdo->prepare("
                                SELECT a.IdUsuario, a.Nome, a.Apelido, a.Foto, a.Nascimento,
                                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.Nascimento, '/', 1), '/', -1) AS dia,
                                    SUBSTRING_INDEX(SUBSTRING_INDEX(a.Nascimento, '/', 2), '/', -1) AS mes,
                                    SUBSTRING_INDEX(a.Nascimento, '/', -1) AS ano_nasc,
                                    c.NomeCurso,
                                    t.NomeTurma
                                FROM tbAluno a
                                LEFT JOIN tbMatricula m ON a.IdUsuario = m.IdUsuario
                                LEFT JOIN tbCurso c ON m.IdCurso = c.IdCurso
                                LEFT JOIN tbTurma t ON m.IdTurma = t.IdTurma
                                WHERE a.Habilitado = 1
                                HAVING mes = ?
                                ORDER BY CAST(dia AS UNSIGNED)
                            ");

        $stmt->execute([$mes]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $base_url = $_SESSION['BASE_URL'] . '/assets/img/fotos/';
        $anoAtual = date('Y');

        $aniversariantes = array_map(function ($item) use ($base_url, $anoAtual) {
            $nome = trim($item['Nome']);
            $apelido = trim($item['Apelido'] ?? '');
            $foto = $item['Foto'];
            $nascimento = "{$item['dia']}/{$item['mes']}";

            // Calcula a idade no aniversário
            $ano_nasc = (int) $item['ano_nasc'];
            $idade = $anoAtual - $ano_nasc;

            // Considera se o aniversário ainda vai acontecer este ano
            $dataAniversario = DateTime::createFromFormat('d/m/Y', "{$item['dia']}/{$item['mes']}/$anoAtual");
            $hoje = new DateTime();

            if ($dataAniversario < $hoje) {
                // já passou este ano, então a idade atual já é válida
            } else {
                // ainda vai fazer, então incrementa a idade
                $idade++;
            }

            return [
                'Nome' => ($apelido ? "($apelido) " : "") . $nome,
                'Foto' => $foto,
                'Nascimento' => $nascimento,
                'Idade' => $idade,
                'Curso' => $item['NomeCurso'] ?? '',
                'Turma' => $item['NomeTurma'] ?? ''
            ];
            
        }, $dados);

        echo json_encode([
            'status' => 'ok',
            'dados' => $aniversariantes
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'erro',
            'msg' => 'Erro ao buscar aniversariantes: ' . $e->getMessage()
        ]);
    }
    exit();
} else {
    echo json_encode(['status' => 'erro', 'msg' => 'Ação inválida.']);
    exit();
}
