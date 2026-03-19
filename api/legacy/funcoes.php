<?php
function verificaHorarioPermissao($pdo, $idPermissao) {
    $diaAtual = date('D'); // Mon, Tue...
    $horaAtual = (int)date('H'); // 0 a 23
    $mapaDias = ['Sun' => 'Dom', 'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua', 'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sab'];
    $diaSemana = $mapaDias[$diaAtual];

    $sqlHorario = "SELECT 1 FROM tbPermissaoHorario WHERE IdPermissao = ? AND DiaSemana = ? AND Hora = ?";
    $stmt = $pdo->prepare($sqlHorario);
    $stmt->execute([$idPermissao, $diaSemana, $horaAtual]);

    return $stmt->rowCount() > 0;
}
?>