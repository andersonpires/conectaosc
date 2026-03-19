-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/04/2024 às 21:45
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `kriade18_cadface`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `t_colaborador`
--

CREATE TABLE `t_colaborador` (
  `IdColaborador` int(8) NOT NULL,
  `Foto` varchar(255) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Sobrenome` varchar(255) NOT NULL,
  `CPF` varchar(14) NOT NULL,
  `Tipo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Senha` varchar(255) NOT NULL,
  `Habilitado` int(1) NOT NULL,
  `Email_confere` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `t_colaborador`
--

INSERT INTO `t_colaborador` (`IdColaborador`, `Foto`, `Nome`, `Sobrenome`, `CPF`, `Tipo`, `Email`, `Senha`, `Habilitado`, `Email_confere`) VALUES
(1, '3cba8c7dea0f11583885e296f8140187.jpg', 'Anderson', 'Ribeiro Pires', '300.389.128-64', 'Administrador', 'anderson@iteva.org.br', '$2y$10$Vj1ZfIj7oiRrk.5uZQ60bOIXIeY1.vrI1N2EMy6/nOVF8nV4h63FO', 1, 1),
(2, 'b612a136a4902dbe4bd3c18d9a2e7750.png', 'Samara', 'Gomes', '008.414.663-00', 'Administrador', 'samara@faceonline.com.br', '$2y$10$CcDBcjjY.hrIrlwj1h0Usen5HjKYzGeSHUUksPsYyLMnX3BTVnq5u', 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `t_doacoes`
--

CREATE TABLE `t_doacoes` (
  `IdDoacao` int(11) NOT NULL,
  `IdUsuario` int(11) NOT NULL,
  `IdSol` int(11) NOT NULL,
  `Quantidade` decimal(10,0) NOT NULL,
  `vData` varchar(10) NOT NULL,
  `IdDoador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `t_doacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `t_solucoes`
--

CREATE TABLE `t_solucoes` (
  `IdSol` int(11) NOT NULL,
  `Tipo` varchar(50) NOT NULL,
  `NomeSol` varchar(150) NOT NULL,
  `Valor` decimal(10,2) NOT NULL,
  `Descricao` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `t_solucoes`
--

INSERT INTO `t_solucoes` (`IdSol`, `Tipo`, `NomeSol`, `Valor`, `Descricao`) VALUES
(33, 'Produto', 'Frango congelado', 10.00, 'Doação de frango congelado'),
(34, 'Produto', 'Kit mat limpeza', 25.00, '2 litros de desinfetante\r\n1 barra de sabão de coco\r\n2 litros de kiboa'),
(35, 'Serviço', 'Hemograma completo', 15.00, 'Hemograma completo'),
(36, 'Serviço', 'Raio-X', 150.00, 'Exame de Raio-X'),
(41, 'Serviço', 'Dentista', 120.00, 'Serviço de limpeza, extração e abturação');

-- --------------------------------------------------------


--
-- Índices de tabela `t_colaborador`
--
ALTER TABLE `t_colaborador`
  ADD PRIMARY KEY (`IdColaborador`);

--
-- Índices de tabela `t_doacoes`
--
ALTER TABLE `t_doacoes`
  ADD PRIMARY KEY (`IdDoacao`);

--
-
--
-- Índices de tabela `t_solucoes`
--
ALTER TABLE `t_solucoes`
  ADD PRIMARY KEY (`IdSol`);

--

--
-- AUTO_INCREMENT de tabela `t_colaborador`
--
ALTER TABLE `t_colaborador`
  MODIFY `IdColaborador` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `t_doacoes`
--
ALTER TABLE `t_doacoes`
  MODIFY `IdDoacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-
-- AUTO_INCREMENT de tabela `t_solucoes`
--
ALTER TABLE `t_solucoes`
  MODIFY `IdSol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
