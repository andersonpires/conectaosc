-- Migration 20260216: Garantir URL do cadastro de paginas
UPDATE tbPaginas
SET ArquivoPHP = 'formPaginas.php'
WHERE
    ArquivoPHP LIKE '%formPaginas.php%'
    OR NomePagina LIKE '%Cadastro de pagina%';
