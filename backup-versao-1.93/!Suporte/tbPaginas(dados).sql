-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 13/04/2025 às 12:46
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mwtech63_matricula`
--

--
-- Despejando dados para a tabela `tbPaginas`
--

INSERT INTO `tbPaginas` (`IdPagina`, `NomePagina`, `DescricaoPagina`, `TipoApp`, `ArquivoPHP`) VALUES
(1, 'Chamada', 'Permite realizar a chamada', 'Alunos', 'listTipoChamada.php'),
(2, 'CRM', 'Gestão de relacionamento com alunos', 'CRM', 'crm.php'),
(3, 'Alerta', 'Define cores e quantidades para alertas', 'Configurações', 'alerta.php'),
(4, 'Listar permissão', 'Veja as permissões criadas', 'Configurações', 'listPermissao.php'),
(5, 'Cadastro alunos', NULL, 'Alunos', 'formBeneficiario.php'),
(6, 'Listagem simples de alunos', 'Lista dados básicos do aluno', 'Alunos', 'listagemSBenef.php'),
(7, 'Dados dos alunos', 'Lista todos os dados cadastrados', 'Alunos', 'listagemBeneficiarios.php'),
(8, 'Aniversariantes', NULL, 'Alunos', 'aniversariantes.php'),
(9, 'Gerenciar Cursos', 'Cadastra ou edita cursos', 'Cursos e turmas', 'formCurso.php'),
(10, 'Gerenciar Turmas', 'Cadastra ou edita turmas', 'Cursos e turmas', 'formTurma.php'),
(11, 'Matricular', NULL, 'Cursos e turmas', 'listagemSBenef.php?msg=%27Selecione%20as%20pessoas%20e%20clique%20em%20Matricular%20Aluno%27'),
(12, 'Listar matrículas', NULL, 'Cursos e turmas', 'listagemMatriculas.php'),
(13, 'Cadastro colaboradores', 'Cadastra ou edita colaboradores', 'Colaboradores', 'formColaborador.php'),
(14, 'Cadastrar permissão', 'Cadastra ou edita permissões', 'Colaboradores', 'formPermissao.php'),
(16, 'Cadastro de páginas', 'Cadastra ou edita páginas', 'Configurações', 'formPaginas.php'),
(17, 'Inscrição eventos', 'Cadastra ou edita eventos', 'Eventos', 'formInscricao.php'),
(18, 'Listar inscritos eventos', 'Lista inscritos', 'Eventos', 'listagemInscritos.php'),
(19, 'Perfil pessoal', 'Edita dados pessoais do usuário', 'Perfil', 'perfil.php'),
(20, 'Relatório frequência', '', 'Relatórios', 'relFrequencia.php'),
(21, 'Relatório Matriculados', 'Relatório com dados pessoais', 'Relatórios', 'relMatriculados.php'),
(22, 'Logar novamente', 'Realiza o auto login', 'Perfil', 'login.php'),
(23, 'Configurações de sistema', 'Cadastra ou edita dados do sistema', 'Configurações', 'formConfig.php'),
(24, 'Edição alunos', 'Cadastra ou edita dados dos alunos', 'Alunos', 'formEditarBenef.php');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
