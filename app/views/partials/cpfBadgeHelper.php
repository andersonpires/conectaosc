<?php
declare(strict_types=1);

/**
 * Helper compartilhado do badge "Ver CPF".
 *
 * Regra de pendência (beneficiários ativos):
 *  - CPF preenchido e inválido (dígitos verificadores): pendente, em qualquer idade.
 *  - CPF em branco: pendente apenas para 18+ anos; menores de 18 podem ficar sem CPF.
 *  - Nascimento ausente/ilegível com CPF em branco: pendente (sem idade não há isenção).
 */

if (!function_exists('conectaosc_cpf_valido')) {
    function conectaosc_cpf_valido(string $cpf): bool
    {
        $cpf = (string) preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $dv = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $dv) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('conectaosc_idade_por_nascimento')) {
    function conectaosc_idade_por_nascimento(?string $nascimento): ?int
    {
        $nascimento = trim((string) $nascimento);
        if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $nascimento, $m)) {
            return null;
        }
        $dn = DateTime::createFromFormat('!d/m/Y', $nascimento);
        if (!($dn instanceof DateTime) || $dn->format('d/m/Y') !== $nascimento) {
            return null;
        }
        return $dn->diff(new DateTime('today'))->y;
    }
}

if (!function_exists('conectaosc_cpf_pendente')) {
    function conectaosc_cpf_pendente(?string $cpf, ?string $nascimento): bool
    {
        $digitos = (string) preg_replace('/\D/', '', (string) $cpf);
        if ($digitos === '') {
            $idade = conectaosc_idade_por_nascimento($nascimento);
            return $idade === null || $idade >= 18;
        }
        return !conectaosc_cpf_valido($digitos);
    }
}

if (!function_exists('conectaosc_cpf_badge')) {
    function conectaosc_cpf_badge(?string $cpf, ?string $nascimento, string $urlCadastro = ''): string
    {
        if (!conectaosc_cpf_pendente($cpf, $nascimento)) {
            return '';
        }
        $titulo = 'CPF em branco ou inválido. Clique para corrigir.';
        if ($urlCadastro !== '') {
            return '<a href="' . htmlspecialchars($urlCadastro, ENT_QUOTES, 'UTF-8') . '" class="badge bg-danger text-decoration-none" title="' . $titulo . '">Ver CPF</a>';
        }
        return '<span class="badge bg-danger" title="CPF em branco ou inválido.">Ver CPF</span>';
    }
}
