<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

class PerfilModel
{
    public static function getById(int $idColaborador): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM tbUser WHERE IdColaborador = :IdColaborador");
        $stmt->bindParam(":IdColaborador", $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    public static function update(array $dados, array $files): bool
    {
        global $pdo;

        $idColaborador = isset($dados['IdColaborador']) ? (int) $dados['IdColaborador'] : 0;
        if ($idColaborador <= 0) {
            return false;
        }

        $fotoString = $dados['fotostring'] ?? null;
        $nome = $dados['Nome'] ?? null;
        $sobrenome = $dados['Sobrenome'] ?? null;
        $whatsApp = $dados['WhatsApp'] ?? null;
        $email = $dados['Email'] ?? null;
        $senha = $dados['Senha'] ?? null;
        $cidadeEstado = $dados['CidadeEstado'] ?? null;
        $trabalho = $dados['Trabalho'] ?? null;

        $profissionalSaude = isset($dados['profissional_saude']) && $dados['profissional_saude'] === '1' ? 1 : 0;
        $especialidadeId = $dados['especialidade_id'] ?? null;

        $hashSenha = null;
        if ($senha !== null && $senha !== '') {
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
        }

        $nomeArquivo = $fotoString;
        if (isset($files['foto']) && $files['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = $files['foto'];
            preg_match("/\.(png|jpg|jpeg){1}$/i", $foto["name"], $ext);
            if ($ext == true) {
                $nomeArquivo = md5(uniqid(time())) . "." . $ext[1];
                $caminhoArquivo = $_SESSION['BASE_PATH'] . "/assets/img/fotos/" . $nomeArquivo;
                move_uploaded_file($foto['tmp_name'], $caminhoArquivo);
            }
        }

        $sql = "UPDATE tbUser SET Foto = ?, Nome = ?, Sobrenome = ?, WhatsApp = ?, Email = ?, CidadeEstado = ?, Trabalho = ?, profissional_saude = ?, especialidade_id = ?";
        if ($hashSenha) {
            $sql .= ", Senha = ?";
        }
        $sql .= " WHERE IdColaborador = ?";

        $params = [$nomeArquivo, $nome, $sobrenome, $whatsApp, $email, $cidadeEstado, $trabalho, $profissionalSaude, $especialidadeId];
        if ($hashSenha) {
            $params[] = $hashSenha;
        }
        $params[] = $idColaborador;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return true;
    }
}
