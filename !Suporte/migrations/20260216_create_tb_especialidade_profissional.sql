CREATE TABLE IF NOT EXISTS tb_especialidade_profissional (
    IdEspecialidadeProfissional INT AUTO_INCREMENT PRIMARY KEY,
    IdColaborador INT NOT NULL,
    especialidade_id INT NULL,
    Conselho VARCHAR(30) NULL,
    UF CHAR(2) NULL,
    NumeroRegistro VARCHAR(30) NULL,
    CONSTRAINT fk_esp_prof_colab
        FOREIGN KEY (IdColaborador) REFERENCES tbUser(IdColaborador)
        ON DELETE CASCADE,
    CONSTRAINT fk_esp_prof_espec
        FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_esp_prof_colab ON tb_especialidade_profissional (IdColaborador);
CREATE INDEX idx_esp_prof_espec ON tb_especialidade_profissional (especialidade_id);
