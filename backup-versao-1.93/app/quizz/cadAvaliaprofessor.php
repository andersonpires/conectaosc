<?php
// Inclui o arquivo de conexão com o banco de dados
include 'conexao_grava.php';

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define um array com todas as perguntas e seus respectivos IDs na tabela tbAP_Perguntas
    $perguntas = [
        'gestao_tempo_a' => 1,
        'gestao_tempo_b' => 2,
        'individualizacao_ensino_a' => 3,
        'individualizacao_ensino_b' => 4,
        'material_didatico_a' => 5,
        'material_didatico_b' => 6,
        'inclusao_acessibilidade_a' => 7,
        'engajamento_criatividade_a' => 8,
        'correcao_avaliacao_a' => 9,
        'infraestrutura_recursos_a' => 10,
        'pressao_expectativas_a' => 11,
        'equilibrio_pessoal_profissional_a' => 12
    ];

    // Prepara a query SQL para inserção
    $sql = "INSERT INTO tbap_respostas (IdPergunta, IdOpcao) VALUES (?, ?)";
    $stmt = mysqli_prepare($conexao_grava, $sql);

    if ($stmt) {
        $all_success = true; // Para verificar se todas as inserções foram bem-sucedidas

        // Percorre as perguntas e insere cada resposta
        foreach ($perguntas as $key => $idPergunta) {
            if (isset($_POST[$key])) {
                $idOpcao = intval($_POST[$key]); // Obtém o valor selecionado pelo usuário

                // Faz o bind dos parâmetros
                mysqli_stmt_bind_param($stmt, 'ii', $idPergunta, $idOpcao);

                // Executa a instrução e verifica o sucesso
                if (!mysqli_stmt_execute($stmt)) {
                    $all_success = false;
                    $resultado = mysqli_error($conexao_grava);
                    break;
                }
            }
        }

        // Redireciona com base no sucesso ou falha
        if ($all_success) {
            header("Location: professoressucess.php");
            exit;
        } else {
            header("Location: formAvaliaprofessor.php?erro=" . urlencode($resultado));
            exit;
        }
    } else {
        // Captura o erro de preparação e redireciona para o formulário
        $resultado = mysqli_error($conexao_grava);
        header("Location: formAvaliaprofessor.php?erro=" . urlencode($resultado));
        exit;
    }
} else {
    // Redireciona para o formulário caso o método não seja POST
    header("Location: formAvaliaprofessor.php");
    exit;
}
?>
