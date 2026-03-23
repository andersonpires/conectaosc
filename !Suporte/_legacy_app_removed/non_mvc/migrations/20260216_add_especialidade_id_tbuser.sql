ALTER TABLE tbUser
    ADD COLUMN especialidade_id INT NULL AFTER profissional_saude;

ALTER TABLE tbUser
    ADD CONSTRAINT fk_tbuser_especialidade
        FOREIGN KEY (especialidade_id) REFERENCES tb_especialidade(id)
        ON DELETE SET NULL;

CREATE INDEX idx_tbuser_especialidade ON tbUser (especialidade_id);
