<?php
declare(strict_types=1);

namespace BackEnd\Services;

use BackEnd\Repositories\PlanoCursoRepository;
use InvalidArgumentException;

final class PlanoCursoService
{
    public function __construct(private readonly PlanoCursoRepository $repository)
    {
    }

    public function list(int $idCurso = 0): array
    {
        return $this->repository->listAll($idCurso);
    }

    public function resumoCursos(string $estado = 'todos'): array
    {
        return $this->repository->listResumoCursos($estado);
    }

    public function show(int $idPlanoCurso): ?array
    {
        return $this->repository->findById($idPlanoCurso);
    }

    public function create(array $payload, int $idColaborador): int
    {
        $idCurso = (int) ($payload['IdCurso'] ?? 0);
        if ($idCurso <= 0) {
            throw new InvalidArgumentException('IdCurso é obrigatório');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            throw new InvalidArgumentException('NomePlano é obrigatório');
        }

        if ($idColaborador <= 0) {
            throw new InvalidArgumentException('Usuário inválido');
        }

        return $this->repository->create([
            'IdCurso' => $idCurso,
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? null),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? null),
            'CriadoPor' => $idColaborador,
            'AtualizadoPor' => $idColaborador,
        ]);
    }

    public function update(int $idPlanoCurso, array $payload, int $idColaborador): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }

        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? $plano['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            throw new InvalidArgumentException('NomePlano é obrigatório');
        }

        return $this->repository->update($idPlanoCurso, [
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? $plano['Versao'] ?? null),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? $plano['Descricao'] ?? null),
            'AtualizadoPor' => $idColaborador > 0 ? $idColaborador : null,
        ]);
    }

    public function delete(int $idPlanoCurso, int $idColaborador): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        if ($this->repository->countVinculosAtivos($idPlanoCurso) > 0) {
            throw new InvalidArgumentException('Plano possui turmas vinculadas');
        }

        return $this->repository->softDelete($idPlanoCurso, $idColaborador);
    }

    public function deleteImpact(int $idPlanoCurso): array
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $impact = $this->repository->deleteImpact($idPlanoCurso);
        return [
            'IdPlanoCurso' => $idPlanoCurso,
            'NomePlano' => (string) ($plano['NomePlano'] ?? ''),
            'QtdAulas' => (int) ($impact['QtdAulas'] ?? 0),
            'QtdVinculos' => (int) ($impact['QtdVinculos'] ?? 0),
            'QtdVinculosAtivos' => (int) ($impact['QtdVinculosAtivos'] ?? 0),
            'QtdTurmas' => (int) ($impact['QtdTurmas'] ?? 0),
            'QtdCronogramas' => (int) ($impact['QtdCronogramas'] ?? 0),
            'QtdCronogramasAgendados' => (int) ($impact['QtdCronogramasAgendados'] ?? 0),
        ];
    }

    public function deletePermanente(int $idPlanoCurso, bool $forcarExclusao): bool
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano inválido');
        }
        $plano = $this->repository->findById($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $impact = $this->deleteImpact($idPlanoCurso);
        $temDependencias = ($impact['QtdVinculos'] > 0) || ($impact['QtdCronogramas'] > 0);
        if ($temDependencias && !$forcarExclusao) {
            throw new InvalidArgumentException('Plano possui turmas vinculadas e cronograma planejado. Confirme a exclusão forçada.');
        }

        return $this->repository->hardDelete($idPlanoCurso);
    }

    public function duplicate(int $idPlanoCurso, array $payload, int $idColaborador): int
    {
        $origem = $this->repository->findById($idPlanoCurso);
        if (!$origem) {
            throw new InvalidArgumentException('Plano de origem não encontrado');
        }

        $nomePlano = trim((string) ($payload['NomePlano'] ?? ''));
        if ($nomePlano === '') {
            $nomePlano = (string) ($origem['NomePlano'] ?? 'Plano duplicado') . ' (Cópia)';
        }

        $novoId = $this->repository->create([
            'IdCurso' => (int) ($origem['IdCurso'] ?? 0),
            'NomePlano' => $nomePlano,
            'Versao' => $this->nullableString($payload['Versao'] ?? ($origem['Versao'] ?? null)),
            'Descricao' => $this->nullableString($payload['Descricao'] ?? ($origem['Descricao'] ?? null)),
            'CriadoPor' => $idColaborador,
            'AtualizadoPor' => $idColaborador,
        ]);

        $aulasOrigem = $this->repository->listAulas($idPlanoCurso);
        foreach ($aulasOrigem as $aula) {
            $this->repository->createAula([
                'IdPlanoCurso' => $novoId,
                'OrdemAula' => (int) ($aula['OrdemAula'] ?? 0),
                'NomeAula' => trim((string) ($aula['NomeAula'] ?? 'Aula')),
                'Descricao' => $this->nullableString($aula['Descricao'] ?? null),
                'DuracaoMinutos' => (int) ($aula['DuracaoMinutos'] ?? 0),
                'Categoria' => $this->nullableString($aula['Categoria'] ?? null),
                'Recursos' => $this->nullableString($aula['Recursos'] ?? null),
                'Materiais' => $this->nullableString($aula['Materiais'] ?? null),
                'Observacoes' => $this->nullableString($aula['Observacoes'] ?? null),
            ]);
        }

        return $novoId;
    }

    public function listAulas(int $idPlanoCurso): array
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }
        return $this->repository->listAulas($idPlanoCurso);
    }

    public function createAula(int $idPlanoCurso, array $payload): int
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $nomeAula = trim((string) ($payload['NomeAula'] ?? ''));
        if ($nomeAula === '') {
            throw new InvalidArgumentException('NomeAula é obrigatório');
        }

        $duracao = (int) ($payload['DuracaoMinutos'] ?? 0);
        if ($duracao <= 0) {
            throw new InvalidArgumentException('DuracaoMinutos deve ser maior que zero');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $this->repository->normalizeDisabledOrdensByPlano($idPlanoCurso);
            $ordem = isset($payload['OrdemAula']) ? (int) $payload['OrdemAula'] : 0;
            if ($ordem <= 0) {
                $ordem = $this->repository->nextOrdemAula($idPlanoCurso);
            }

            $idAula = $this->repository->createAula([
                'IdPlanoCurso' => $idPlanoCurso,
                'OrdemAula' => $ordem,
                'NomeAula' => $nomeAula,
                'Descricao' => $this->nullableString($payload['Descricao'] ?? null),
                'DuracaoMinutos' => $duracao,
                'Categoria' => $this->nullableString($payload['Categoria'] ?? null),
                'Recursos' => $this->nullableString($payload['Recursos'] ?? null),
                'Materiais' => $this->nullableString($payload['Materiais'] ?? null),
                'Observacoes' => $this->nullableString($payload['Observacoes'] ?? null),
            ]);

            $vinculos = $this->repository->listVinculosAtivosByPlano($idPlanoCurso);
            foreach ($vinculos as $vinculo) {
                $idTurmaPlanoCurso = (int) ($vinculo['IdTurmaPlanoCurso'] ?? 0);
                if ($idTurmaPlanoCurso <= 0) {
                    continue;
                }
                $this->repository->createPendenteCronogramaIfMissing($idTurmaPlanoCurso, $idAula);
            }

            $pdo->commit();
            return $idAula;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateAula(int $idPlanoCursoAula, array $payload): bool
    {
        $aula = $this->repository->findAulaById($idPlanoCursoAula);
        if (!$aula) {
            throw new InvalidArgumentException('Aula não encontrada');
        }

        $nomeAula = trim((string) ($payload['NomeAula'] ?? $aula['NomeAula'] ?? ''));
        if ($nomeAula === '') {
            throw new InvalidArgumentException('NomeAula é obrigatório');
        }

        $duracao = (int) ($payload['DuracaoMinutos'] ?? $aula['DuracaoMinutos'] ?? 0);
        if ($duracao <= 0) {
            throw new InvalidArgumentException('DuracaoMinutos deve ser maior que zero');
        }

        return $this->repository->updateAula($idPlanoCursoAula, [
            'NomeAula' => $nomeAula,
            'Descricao' => $this->nullableString($payload['Descricao'] ?? $aula['Descricao'] ?? null),
            'DuracaoMinutos' => $duracao,
            'Categoria' => $this->nullableString($payload['Categoria'] ?? $aula['Categoria'] ?? null),
            'Recursos' => $this->nullableString($payload['Recursos'] ?? $aula['Recursos'] ?? null),
            'Materiais' => $this->nullableString($payload['Materiais'] ?? $aula['Materiais'] ?? null),
            'Observacoes' => $this->nullableString($payload['Observacoes'] ?? $aula['Observacoes'] ?? null),
        ]);
    }

    public function deleteAula(int $idPlanoCursoAula): bool
    {
        $aula = $this->repository->findAulaById($idPlanoCursoAula);
        if (!$aula) {
            throw new InvalidArgumentException('Aula não encontrada');
        }
        return $this->repository->softDeleteAula($idPlanoCursoAula);
    }

    public function reorderAulas(int $idPlanoCurso, array $payload): array
    {
        if ($idPlanoCurso <= 0 || !$this->repository->findById($idPlanoCurso)) {
            throw new InvalidArgumentException('Plano não encontrado');
        }

        $modo = strtoupper(trim((string) ($payload['Modo'] ?? 'B')));
        if ($modo !== 'A' && $modo !== 'B') {
            throw new InvalidArgumentException('Modo inválido. Use A ou B');
        }

        $ids = $payload['Aulas'] ?? [];
        if (!is_array($ids) || $ids === []) {
            throw new InvalidArgumentException('Lista de aulas é obrigatória');
        }

        $idsOrdenados = array_values(array_unique(array_map('intval', $ids)));
        if (count($idsOrdenados) !== count($ids)) {
            throw new InvalidArgumentException('Lista de aulas contém IDs duplicados');
        }

        $aulasAtuais = $this->repository->listAulas($idPlanoCurso);
        $idsAtuais = array_map(static fn(array $a): int => (int) $a['IdPlanoCursoAula'], $aulasAtuais);
        sort($idsAtuais);
        $idsComparacao = $idsOrdenados;
        sort($idsComparacao);
        if ($idsAtuais !== $idsComparacao) {
            throw new InvalidArgumentException('Lista de aulas inconsistente com o plano');
        }

        $pdo = $this->repository->pdo();
        $pdo->beginTransaction();
        try {
            $this->repository->normalizeDisabledOrdensByPlano($idPlanoCurso);

            // Atualiza em duas fases para evitar colisão da unique (IdPlanoCurso, OrdemAula).
            foreach ($idsOrdenados as $index => $idAula) {
                $this->repository->updateOrdemAula($idAula, -100000 - $index);
            }
            foreach ($idsOrdenados as $index => $idAula) {
                $this->repository->updateOrdemAula($idAula, $index + 1);
            }

            $slotsAtualizados = 0;
            if ($modo === 'A') {
                $vinculos = $this->repository->listVinculosAtivosByPlano($idPlanoCurso);
                foreach ($vinculos as $vinculo) {
                    $idTurmaPlanoCurso = (int) ($vinculo['IdTurmaPlanoCurso'] ?? 0);
                    if ($idTurmaPlanoCurso <= 0) {
                        continue;
                    }

                    $slots = $this->repository->listCronogramaSlotsByVinculo($idTurmaPlanoCurso);
                    $limite = min(count($slots), count($idsOrdenados));
                    for ($i = 0; $i < $limite; $i++) {
                        $idCronograma = (int) ($slots[$i]['IdCronogramaAula'] ?? 0);
                        $novoIdAula = (int) $idsOrdenados[$i];
                        if ($idCronograma <= 0 || $novoIdAula <= 0) {
                            continue;
                        }
                        if ((int) ($slots[$i]['IdPlanoCursoAula'] ?? 0) === $novoIdAula) {
                            continue;
                        }
                        if ($this->repository->updateCronogramaAulaRef($idCronograma, $novoIdAula)) {
                            $slotsAtualizados++;
                        }
                    }
                }
            }

            $pdo->commit();
            return [
                'modo' => $modo,
                'aulas_reordenadas' => count($idsOrdenados),
                'slots_atualizados' => $slotsAtualizados,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function exportPdf(int $idPlanoCurso): array
    {
        if ($idPlanoCurso <= 0) {
            throw new InvalidArgumentException('Plano invalido');
        }

        $plano = $this->repository->findByIdForPdf($idPlanoCurso);
        if (!$plano) {
            throw new InvalidArgumentException('Plano nao encontrado');
        }

        $aulas = $this->repository->listAulas($idPlanoCurso);

        $todosRaw = [];
        try {
            $todosRaw = $this->repository->listTodosByPlano($idPlanoCurso);
        } catch (\Throwable) {
            $todosRaw = [];
        }

        $anexosRaw = [];
        try {
            $anexosRaw = $this->repository->listAulaAnexosByPlano($idPlanoCurso);
        } catch (\Throwable) {
            $anexosRaw = [];
        }

        $todosPorAula = [];
        foreach ($todosRaw as $row) {
            $idAula = (int) ($row['IdPlanoCursoAula'] ?? 0);
            if ($idAula <= 0) {
                continue;
            }
            if (!isset($todosPorAula[$idAula])) {
                $todosPorAula[$idAula] = [];
            }
            $todosPorAula[$idAula][] = $row;
        }

        $anexosPorAula = [];
        foreach ($anexosRaw as $row) {
            $idAula = (int) ($row['IdPlanoCursoAula'] ?? 0);
            if ($idAula <= 0) {
                continue;
            }
            if (!isset($anexosPorAula[$idAula])) {
                $anexosPorAula[$idAula] = [];
            }
            $anexosPorAula[$idAula][] = $row;
        }

        $root = dirname(__DIR__, 2);
        require_once $root . '/api/lib/tcpdf/tcpdf.php';

        $configPdf = $this->repository->loadPdfHeaderConfig();
        $logoSistemaRaw = trim((string) ($configPdf['LogoImpressao'] ?? ''));
        if ($logoSistemaRaw === '') {
            $logoSistemaRaw = 'logos/LogoImpressao.jpg';
        }

        $logoSistemaPath = $this->resolveAssetImagePath($logoSistemaRaw, $root);
        $logoProjetoPath = $this->resolveAssetImagePath((string) ($plano['LogoProjeto'] ?? ''), $root);

        $pdf = new class extends \TCPDF {
            public string $logoSistemaPath = '';
            public string $logoProjetoPath = '';
            public string $footerText = '';
            public float $logoHeightMm = 20;
            public float $headerTopMm = 8;
            public float $headerLineGapMm = 2;

            public function Header(): void
            {
                $logoTop = $this->headerTopMm;

                if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
                    $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
                }

                if ($this->logoProjetoPath !== '' && is_file($this->logoProjetoPath)) {
                    $rightX = $this->getPageWidth() - 15 - 35;
                    $this->Image($this->logoProjetoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
                }

                $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
                $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
            }

            public function Footer(): void
            {
                $this->SetY(-14);
                $lineY = $this->GetY();
                $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
                $this->SetY($lineY + 1);
                $this->SetFont('helvetica', '', 8);
                $texto = $this->footerText . ' | Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages();
                $this->Cell(0, 5, $texto, 0, 0, 'C');
            }
        };

        $pdf->logoSistemaPath = $logoSistemaPath;
        $pdf->logoProjetoPath = $logoProjetoPath;
        $pdf->footerText = 'Conecta OSC - Planejamento de curso';

        $pdf->SetCreator('Conecta OSC');
        $pdf->SetAuthor('Conecta OSC');
        $pdf->SetTitle('Planejamento - ' . (string) ($plano['NomePlano'] ?? ''));
        $pdf->SetSubject('Planejamento pedagógico');
        $pdf->SetMargins(15, 36, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10);

        $nomePlano = (string) ($plano['NomePlano'] ?? '');
        $nomeCurso = (string) ($plano['NomeCurso'] ?? '');
        $versao = (string) ($plano['Versao'] ?? '');
        $descricaoPlano = trim((string) ($plano['Descricao'] ?? ''));
        $nomeProjeto = (string) ($plano['NomeProjeto'] ?? '');

        $html = '<h1 style="font-size:18px; margin:0 0 6px 0;">Planejamento do curso</h1>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<tr><td width="28%"><b>Plano</b></td><td width="72%">' . $this->escHtml($nomePlano) . '</td></tr>';
        $html .= '<tr><td width="28%"><b>Curso</b></td><td width="72%">' . $this->escHtml($nomeCurso) . '</td></tr>';
        $html .= '<tr><td width="28%"><b>Versão</b></td><td width="72%">' . $this->escHtml($versao !== '' ? $versao : '-') . '</td></tr>';
        $html .= '<tr><td width="28%"><b>Projeto</b></td><td width="72%">' . $this->escHtml($nomeProjeto !== '' ? $nomeProjeto : '-') . '</td></tr>';
        $html .= '</table>';
        if ($descricaoPlano !== '') {
            $html .= '<p style="margin-top:8px;"><b>Descrição do plano:</b><br>' . nl2br($this->escHtml($descricaoPlano)) . '</p>';
        }
        $html .= '<p style="margin-top:8px;"><b>Total de aulas:</b> ' . count($aulas) . '</p>';
        $pdf->writeHTML($html, true, false, true, false, '');

        foreach ($aulas as $index => $aula) {
            $idAula = (int) ($aula['IdPlanoCursoAula'] ?? 0);
            $topicos = $todosPorAula[$idAula] ?? [];
            $anexos = $anexosPorAula[$idAula] ?? [];

            $pdf->Ln(1);
            $pdf->SetFont('dejavusans', 'B', 12);
            $tituloAula = ($index + 1) . '. ' . trim((string) ($aula['NomeAula'] ?? 'Aula'));
            $pdf->Cell(0, 7, $tituloAula, 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 10);

            $duracao = (int) ($aula['DuracaoMinutos'] ?? 0);
            $categoria = trim((string) ($aula['Categoria'] ?? ''));
            $descricaoAula = trim((string) ($aula['Descricao'] ?? ''));
            $recursos = trim((string) ($aula['Recursos'] ?? ''));
            $materiais = trim((string) ($aula['Materiais'] ?? ''));
            $observacoes = trim((string) ($aula['Observacoes'] ?? ''));

            $htmlAula = '<table border="1" cellpadding="4" cellspacing="0">';
            $htmlAula .= '<tr><td width="28%"><b>Duração (min)</b></td><td width="72%">' . $this->escHtml((string) $duracao) . '</td></tr>';
            $htmlAula .= '<tr><td width="28%"><b>Categoria</b></td><td width="72%">' . $this->escHtml($categoria !== '' ? $categoria : '-') . '</td></tr>';
            $htmlAula .= '</table>';

            if ($descricaoAula !== '') {
                $htmlAula .= '<p><b>Descrição:</b><br>' . nl2br($this->escHtml($descricaoAula)) . '</p>';
            }
            if ($recursos !== '') {
                $htmlAula .= '<p><b>Recursos:</b><br>' . nl2br($this->escHtml($recursos)) . '</p>';
            }
            if ($materiais !== '') {
                $htmlAula .= '<p><b>Materiais:</b><br>' . nl2br($this->escHtml($materiais)) . '</p>';
            }
            if ($observacoes !== '') {
                $htmlAula .= '<p><b>Observações:</b><br>' . nl2br($this->escHtml($observacoes)) . '</p>';
            }

            if ($topicos !== []) {
                $htmlAula .= '<p style="margin-top:6px;"><b>Tópicos da aula</b></p>';
                $htmlAula .= '<table border="1" cellpadding="4" cellspacing="0">';
                $htmlAula .= '<tr style="background-color:#f4f6fb;"><th width="8%"><b>#</b></th><th width="16%"><b>Status</b></th><th width="43%"><b>Item</b></th><th width="12%"><b>Cor</b></th><th width="21%"><b>Concluído por</b></th></tr>';
                foreach ($topicos as $idxTopico => $topico) {
                    $concluido = (int) ($topico['Concluido'] ?? 0) === 1;
                    $status = $concluido ? 'Concluído' : 'Pendente';
                    $textoTopico = trim((string) ($topico['TextoTopico'] ?? ''));
                    $corHex = strtoupper(trim((string) ($topico['CorHex'] ?? '#FDE68A')));
                    $nomeConcluido = trim((string) (($topico['NomeConcluidoPor'] ?? '') . ' ' . ($topico['SobrenomeConcluidoPor'] ?? '')));
                    $dataConclusao = $this->formatDateTime((string) ($topico['DataConclusao'] ?? ''));
                    $colConcluido = $nomeConcluido !== '' ? $nomeConcluido : '-';
                    if ($dataConclusao !== '' && $concluido) {
                        $colConcluido .= ' (' . $dataConclusao . ')';
                    }

                    $htmlAula .= '<tr>';
                    $htmlAula .= '<td width="8%">' . ($idxTopico + 1) . '</td>';
                    $htmlAula .= '<td width="16%">' . $this->escHtml($status) . '</td>';
                    $htmlAula .= '<td width="43%">' . $this->escHtml($textoTopico) . '</td>';
                    $htmlAula .= '<td width="12%">' . $this->escHtml($corHex) . '</td>';
                    $htmlAula .= '<td width="21%">' . $this->escHtml($colConcluido) . '</td>';
                    $htmlAula .= '</tr>';
                }
                $htmlAula .= '</table>';
            } else {
                $htmlAula .= '<p style="margin-top:6px;"><b>Tópicos da aula:</b> Sem itens cadastrados.</p>';
            }

            if ($anexos !== []) {
                $htmlAula .= '<p style="margin-top:6px;"><b>Anexos da aula</b></p><ul>';
                foreach ($anexos as $anexo) {
                    $nomeAnexo = trim((string) ($anexo['NomeArquivo'] ?? $anexo['NomeOriginal'] ?? $anexo['NomeFisico'] ?? 'Arquivo'));
                    $descricaoAnexo = trim((string) ($anexo['Descricao'] ?? ''));
                    $linha = $nomeAnexo;
                    if ($descricaoAnexo !== '') {
                        $linha .= ' - ' . $descricaoAnexo;
                    }
                    $htmlAula .= '<li>' . $this->escHtml($linha) . '</li>';
                }
                $htmlAula .= '</ul>';
            } else {
                $htmlAula .= '<p style="margin-top:6px;"><b>Anexos da aula:</b> Sem anexos.</p>';
            }

            $pdf->writeHTML($htmlAula, true, false, true, false, '');
        }

        $content = $pdf->Output('', 'S');
        if (!is_string($content) || $content === '') {
            throw new \RuntimeException('Falha ao gerar PDF');
        }

        $nomePlanoSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($plano['NomePlano'] ?? 'plano'));
        $nomePlanoSlug = trim((string) $nomePlanoSlug, '_');
        if ($nomePlanoSlug === '') {
            $nomePlanoSlug = 'plano';
        }
        $filename = 'planejamento_' . $idPlanoCurso . '_' . $nomePlanoSlug . '.pdf';

        return [
            'filename' => $filename,
            'content' => $content,
        ];
    }

    private function resolveAssetImagePath(string $rawPath, string $rootPath): string
    {
        $rawPath = trim($rawPath);
        if ($rawPath === '') {
            return '';
        }

        $normalized = str_replace('\\', '/', $rawPath);
        $normalized = ltrim($normalized, '/');

        if (preg_match('/^[a-zA-Z]:[\\\\\/]/', $rawPath) === 1 && is_file($rawPath)) {
            return $rawPath;
        }
        if ((str_starts_with($rawPath, '/') || str_starts_with($rawPath, '\\')) && is_file($rawPath)) {
            return $rawPath;
        }

        $candidates = [
            rtrim($rootPath, '/\\') . '/app/assets/img/' . $normalized,
            rtrim($rootPath, '/\\') . '/assets/img/' . $normalized,
            rtrim($rootPath, '/\\') . '/app/assets/' . $normalized,
            rtrim($rootPath, '/\\') . '/' . $normalized,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function escHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }
        return date('d/m/Y H:i', $ts);
    }
    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
