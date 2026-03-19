CREATE TABLE IF NOT EXISTS tbPlanoCurso (
    IdPlanoCurso INT NOT NULL AUTO_INCREMENT,
    IdCurso INT NOT NULL,
    NumeroAula INT NOT NULL,
    NomeAula VARCHAR(255) NOT NULL,
    Detalhamento LONGTEXT NULL,
    Observacoes TEXT NULL,
    StatusRegistro TINYINT NOT NULL DEFAULT 1,
    IdColaboradorCriacao INT NULL,
    IdColaboradorUltimaAlteracao INT NULL,
    DataCriacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    DataUltimaAlteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (IdPlanoCurso),
    UNIQUE KEY uk_tbPlanoCurso_curso_aula (IdCurso, NumeroAula),
    KEY idx_tbPlanoCurso_curso (IdCurso),
    KEY idx_tbPlanoCurso_colab_criacao (IdColaboradorCriacao),
    KEY idx_tbPlanoCurso_colab_alteracao (IdColaboradorUltimaAlteracao),
    CONSTRAINT fk_tbPlanoCurso_curso
        FOREIGN KEY (IdCurso) REFERENCES tbCurso (IdCurso)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCurso_colab_criacao
        FOREIGN KEY (IdColaboradorCriacao) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCurso_colab_alteracao
        FOREIGN KEY (IdColaboradorUltimaAlteracao) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbPlanoCursoArquivo (
    IdPlanoCursoArquivo INT NOT NULL AUTO_INCREMENT,
    IdPlanoCurso INT NOT NULL,
    InformacoesArquivo VARCHAR(255) NOT NULL,
    NomeOriginal VARCHAR(255) NOT NULL,
    NomeSalvo VARCHAR(255) NOT NULL,
    Extensao VARCHAR(20) NOT NULL,
    MimeType VARCHAR(120) NULL,
    TamanhoBytes BIGINT NOT NULL DEFAULT 0,
    CaminhoRelativo VARCHAR(500) NOT NULL,
    DiretorioStorage VARCHAR(255) NOT NULL,
    StatusRegistro TINYINT NOT NULL DEFAULT 1,
    IdColaboradorUpload INT NULL,
    DataUpload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IdPlanoCursoArquivo),
    KEY idx_tbPlanoCursoArquivo_plano (IdPlanoCurso),
    KEY idx_tbPlanoCursoArquivo_colab (IdColaboradorUpload),
    CONSTRAINT fk_tbPlanoCursoArquivo_plano
        FOREIGN KEY (IdPlanoCurso) REFERENCES tbPlanoCurso (IdPlanoCurso)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCursoArquivo_colab
        FOREIGN KEY (IdColaboradorUpload) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbPlanoCursoAgenda (
    IdPlanoCursoAgenda INT NOT NULL AUTO_INCREMENT,
    IdPlanoCurso INT NOT NULL,
    IdTurma INT NOT NULL,
    DataAula DATE NOT NULL,
    AulaPlanejada TINYINT NOT NULL DEFAULT 1,
    AulaExecutada TINYINT NOT NULL DEFAULT 0,
    ExecutadaConformePlanejado TINYINT NULL DEFAULT NULL,
    ObservacoesExecucao TEXT NULL,
    IdColaboradorAgendamento INT NULL,
    IdColaboradorConfirmacao INT NULL,
    DataCriacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    DataUltimaAlteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    DataConfirmacaoExecucao DATETIME NULL,
    PRIMARY KEY (IdPlanoCursoAgenda),
    UNIQUE KEY uk_tbPlanoCursoAgenda_unica (IdPlanoCurso, IdTurma, DataAula),
    KEY idx_tbPlanoCursoAgenda_turma_data (IdTurma, DataAula),
    KEY idx_tbPlanoCursoAgenda_agendamento (IdColaboradorAgendamento),
    KEY idx_tbPlanoCursoAgenda_confirmacao (IdColaboradorConfirmacao),
    CONSTRAINT fk_tbPlanoCursoAgenda_plano
        FOREIGN KEY (IdPlanoCurso) REFERENCES tbPlanoCurso (IdPlanoCurso)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCursoAgenda_turma
        FOREIGN KEY (IdTurma) REFERENCES tbTurma (IdTurma)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCursoAgenda_colab_agendamento
        FOREIGN KEY (IdColaboradorAgendamento) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_tbPlanoCursoAgenda_colab_confirmacao
        FOREIGN KEY (IdColaboradorConfirmacao) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbPlanoCursoHistorico (
    IdPlanoCursoHistorico INT NOT NULL AUTO_INCREMENT,
    Entidade VARCHAR(30) NOT NULL,
    IdRegistro INT NOT NULL,
    Acao VARCHAR(30) NOT NULL,
    CampoAlterado VARCHAR(100) NULL,
    ValorAnterior LONGTEXT NULL,
    ValorNovo LONGTEXT NULL,
    Detalhes LONGTEXT NULL,
    IdColaborador INT NULL,
    DataAcao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (IdPlanoCursoHistorico),
    KEY idx_tbPlanoCursoHistorico_entidade (Entidade, IdRegistro),
    KEY idx_tbPlanoCursoHistorico_colab (IdColaborador),
    CONSTRAINT fk_tbPlanoCursoHistorico_colab
        FOREIGN KEY (IdColaborador) REFERENCES tbUser (IdColaborador)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
