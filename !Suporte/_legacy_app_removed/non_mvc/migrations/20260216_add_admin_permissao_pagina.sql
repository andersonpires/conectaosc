INSERT INTO tbPermissaoPagina (IdPermissao, Pagina)
SELECT p.IdPermissao, 'app/permissao/permissaoController.php'
FROM tbPermissao p
WHERE p.NomePermissao = 'Administrador'
AND NOT EXISTS (
    SELECT 1 FROM tbPermissaoPagina x
    WHERE x.IdPermissao = p.IdPermissao
      AND x.Pagina = 'app/permissao/permissaoController.php'
);

INSERT INTO tbPermissaoPagina (IdPermissao, Pagina)
SELECT p.IdPermissao, 'app/permissao/listPermissaoController.php'
FROM tbPermissao p
WHERE p.NomePermissao = 'Administrador'
AND NOT EXISTS (
    SELECT 1 FROM tbPermissaoPagina x
    WHERE x.IdPermissao = p.IdPermissao
      AND x.Pagina = 'app/permissao/listPermissaoController.php'
);
