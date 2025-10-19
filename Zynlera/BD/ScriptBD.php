<!-- SCRIPT BANCO DE DADOS -->

<!-- /* ===========================================================
   BANCO DE DADOS: sistema_escolar
   MODELO NORMALIZADO (3FN) - 100% EM PORTUGUÊS
   =========================================================== */

DROP DATABASE IF EXISTS sistema_escolar;
CREATE DATABASE sistema_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_escolar;

-- =========================
-- TABELA: Papéis de usuário
-- =========================
CREATE TABLE papeis (
    id_papel INT AUTO_INCREMENT PRIMARY KEY,
    nome_papel VARCHAR(50) NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- TABELA: Usuários
-- =========================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_papel INT NOT NULL,
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_papel FOREIGN KEY (id_papel) REFERENCES papeis(id_papel)
);

-- =========================
-- TABELA: Alunos
-- =========================
CREATE TABLE alunos (
    id_aluno INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    matricula VARCHAR(20) NOT NULL UNIQUE,
    data_nascimento DATE,
    CONSTRAINT fk_alunos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =========================
-- TABELA: Professores
-- =========================
CREATE TABLE professores (
    id_professor INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    registro_funcional VARCHAR(20) NOT NULL UNIQUE,
    data_admissao DATE,
    CONSTRAINT fk_professores_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =========================
-- TABELA: Cursos
-- =========================
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nome_curso VARCHAR(120) NOT NULL UNIQUE,
    descricao TEXT
);

-- =========================
-- TABELA: Disciplinas
-- =========================
CREATE TABLE disciplinas (
    id_disciplina INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    codigo_disciplina VARCHAR(20) NOT NULL UNIQUE,
    nome_disciplina VARCHAR(120) NOT NULL,
    carga_horaria INT DEFAULT 0,
    CONSTRAINT fk_disciplinas_curso FOREIGN KEY (id_curso) REFERENCES cursos(id_curso)
);

-- =========================
-- TABELA: Turmas
-- =========================
CREATE TABLE turmas (
    id_turma INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    nome_turma VARCHAR(80) NOT NULL,
    ano SMALLINT NOT NULL,
    semestre TINYINT NOT NULL CHECK (semestre IN (1,2)),
    CONSTRAINT fk_turmas_curso FOREIGN KEY (id_curso) REFERENCES cursos(id_curso),
    UNIQUE (id_curso, nome_turma, ano, semestre)
);

-- =========================
-- TABELA: Disciplinas por Turma e Professor
-- =========================
CREATE TABLE disciplinas_turmas (
    id_disc_turma INT AUTO_INCREMENT PRIMARY KEY,
    id_turma INT NOT NULL,
    id_disciplina INT NOT NULL,
    id_professor INT NOT NULL,
    CONSTRAINT fk_disc_turma_turma FOREIGN KEY (id_turma) REFERENCES turmas(id_turma),
    CONSTRAINT fk_disc_turma_disc FOREIGN KEY (id_disciplina) REFERENCES disciplinas(id_disciplina),
    CONSTRAINT fk_disc_turma_prof FOREIGN KEY (id_professor) REFERENCES professores(id_professor),
    UNIQUE (id_turma, id_disciplina)
);

-- =========================
-- TABELA: Matrículas
-- =========================
CREATE TABLE matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_turma INT NOT NULL,
    id_aluno INT NOT NULL,
    situacao ENUM('ativa','trancada','concluída','cancelada') DEFAULT 'ativa',
    data_matricula DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_matricula_turma FOREIGN KEY (id_turma) REFERENCES turmas(id_turma),
    CONSTRAINT fk_matricula_aluno FOREIGN KEY (id_aluno) REFERENCES alunos(id_aluno),
    UNIQUE (id_turma, id_aluno)
);

-- =========================
-- TABELA: Aulas
-- =========================
CREATE TABLE aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    id_disc_turma INT NOT NULL,
    data_aula DATE NOT NULL,
    conteudo VARCHAR(200),
    CONSTRAINT fk_aulas_disc_turma FOREIGN KEY (id_disc_turma) REFERENCES disciplinas_turmas(id_disc_turma),
    UNIQUE (id_disc_turma, data_aula)
);

-- =========================
-- TABELA: Frequências
-- =========================
CREATE TABLE frequencias (
    id_frequencia INT AUTO_INCREMENT PRIMARY KEY,
    id_aula INT NOT NULL,
    id_aluno INT NOT NULL,
    status ENUM('Presente','Falta','Justificada') DEFAULT 'Presente',
    CONSTRAINT fk_freq_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula),
    CONSTRAINT fk_freq_aluno FOREIGN KEY (id_aluno) REFERENCES alunos(id_aluno),
    UNIQUE (id_aula, id_aluno)
);

-- =========================
-- TABELA: Avaliações
-- =========================
CREATE TABLE avaliacoes (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_disc_turma INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    peso DECIMAL(5,2) DEFAULT 1.00,
    data_avaliacao DATE,
    CONSTRAINT fk_avaliacao_disc_turma FOREIGN KEY (id_disc_turma) REFERENCES disciplinas_turmas(id_disc_turma)
);

-- =========================
-- TABELA: Notas
-- =========================
CREATE TABLE notas (
    id_nota INT AUTO_INCREMENT PRIMARY KEY,
    id_avaliacao INT NOT NULL,
    id_aluno INT NOT NULL,
    valor DECIMAL(5,2) CHECK (valor BETWEEN 0 AND 10),
    CONSTRAINT fk_nota_avaliacao FOREIGN KEY (id_avaliacao) REFERENCES avaliacoes(id_avaliacao),
    CONSTRAINT fk_nota_aluno FOREIGN KEY (id_aluno) REFERENCES alunos(id_aluno),
    UNIQUE (id_avaliacao, id_aluno)
);

-- =========================
-- TABELA: Auditoria
-- =========================
CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    acao VARCHAR(80),
    entidade VARCHAR(80),
    id_entidade VARCHAR(80),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
); -->
