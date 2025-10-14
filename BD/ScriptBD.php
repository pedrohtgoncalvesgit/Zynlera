<!-- -- =========================
-- Tabela: papeis (3 inserts)
-- =========================
INSERT INTO papeis (nome_papel) VALUES 
('Administrador'),
('Professor'),
('Aluno');

---

-- =========================
-- Tabela: usuarios (3 inserts)
-- Depende de: papeis
-- Senhas fictícias (ex: 'senha123')
-- =========================
-- Adm (id_papel = 1)
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(1, 'Alice Silva (Admin)', 'alice.admin@escola.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);

-- Professor (id_papel = 2)
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(2, 'Dr. Bruno Costa (Prof)', 'bruno.costa@escola.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);

-- Aluno (id_papel = 3)
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(3, 'Carla Oliveira (Aluno)', 'carla.oliveira@aluno.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);

---

-- =========================
-- Tabela: alunos (3 inserts)
-- Depende de: usuarios (id_usuario = 3, 4, 5 - *ajuste se necessário*)
-- Vamos supor que os próximos usuários inseridos sejam Alunos.
-- Para garantir a FK, vou inserir mais dois usuários do tipo 'Aluno'
-- =========================
-- Aluno (id_papel = 3)
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(3, 'Daniel Pereira (Aluno)', 'daniel.pereira@aluno.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(3, 'Elaine Souza (Aluno)', 'elaine.souza@aluno.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(3, 'Pedro (Admin)', 'pedro2004@escola.com', '11111111', 1);



INSERT INTO alunos (id_usuario, matricula, data_nascimento) VALUES 
(3, '20250001', '2005-03-15'), -- Carla Oliveira (id_usuario=3)
(4, '20250002', '2004-11-20'), -- Daniel Pereira (id_usuario=4)
(5, '20250003', '2006-07-01'); -- Elaine Souza (id_usuario=5)

---

-- =========================
-- Tabela: professores (3 inserts)
-- Depende de: usuarios (id_usuario = 2, 6, 7 - *ajuste se necessário*)
-- Vamos supor que os próximos usuários inseridos sejam Professores.
-- Para garantir a FK, vou inserir mais dois usuários do tipo 'Professor'
-- =========================
-- Professor (id_papel = 2)
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(2, 'Dra. Fernanda Lima (Prof)', 'fernanda.lima@escola.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);
INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES 
(2, 'Prof. Gustavo Reis (Prof)', 'gustavo.reis@escola.com', '$2y$10$abcdefghijklmnopqrstuvwxyza', 1);


INSERT INTO professores (id_usuario, registro_funcional, data_admissao) VALUES 
(2, 'PROF001', '2018-08-01'), -- Dr. Bruno Costa (id_usuario=2)
(6, 'PROF002', '2020-02-10'), -- Dra. Fernanda Lima (id_usuario=6)
(7, 'PROF003', '2022-09-01'); -- Prof. Gustavo Reis (id_usuario=7)

---

-- =========================
-- Tabela: cursos (3 inserts)
-- =========================
INSERT INTO cursos (nome_curso, descricao) VALUES 
('Engenharia de Software', 'Focado no desenvolvimento e gestão de sistemas de software.'),
('Ciências Contábeis', 'Prepara para a área de contabilidade, auditoria e finanças.'),
('Design Gráfico', 'Focado na criação de soluções visuais e comunicação.');

---

-- =========================
-- Tabela: disciplinas (3 inserts)
-- Depende de: cursos (id_curso = 1, 2, 3)
-- =========================
INSERT INTO disciplinas (id_curso, codigo_disciplina, nome_disciplina, carga_horaria) VALUES 
(1, 'ES001', 'Algoritmos e Estrutura de Dados', 80),  -- Eng. Software
(2, 'CC101', 'Contabilidade Geral', 60),               -- Ciências Contábeis
(1, 'ES002', 'Banco de Dados Avançado', 80);            -- Eng. Software

---

-- =========================
-- Tabela: turmas (3 inserts)
-- Depende de: cursos (id_curso = 1, 2)
-- =========================
INSERT INTO turmas (id_curso, nome_turma, ano, semestre) VALUES 
(1, 'ES-2025-1A', 2025, 1), -- Turma 1 de Eng. Software
(1, 'ES-2025-1B', 2025, 1), -- Turma 2 de Eng. Software
(2, 'CC-2025-1A', 2025, 1); -- Turma 1 de C. Contábeis

---

-- =========================
-- Tabela: disciplinas_turmas (3 inserts)
-- Depende de: turmas (id_turma = 1, 2, 3), disciplinas (id_disciplina = 1, 2, 3), professores (id_professor = 1, 2, 3)
-- =========================
INSERT INTO disciplinas_turmas (id_turma, id_disciplina, id_professor) VALUES 
(1, 1, 1), -- Algoritmos (ES001) na turma ES-2025-1A com Prof. Bruno Costa
(2, 3, 2), -- BD Avançado (ES002) na turma ES-2025-1B com Prof. Fernanda Lima
(3, 2, 3); -- Contabilidade (CC101) na turma CC-2025-1A com Prof. Gustavo Reis

---

-- =========================
-- Tabela: matriculas (3 inserts)
-- Depende de: turmas (id_turma = 1, 3), alunos (id_aluno = 1, 2, 3)
-- =========================
INSERT INTO matriculas (id_turma, id_aluno, situacao) VALUES 
(1, 1, 'ativa'),      -- Aluno Carla na Turma ES-2025-1A
(3, 2, 'ativa'),      -- Aluno Daniel na Turma CC-2025-1A
(1, 3, 'trancada');   -- Aluno Elaine na Turma ES-2025-1A (trancada)

---

-- =========================
-- Tabela: aulas (3 inserts)
-- Depende de: disciplinas_turmas (id_disc_turma = 1)
-- Vamos focar na primeira disciplina/turma (id_disc_turma = 1)
-- =========================
INSERT INTO aulas (id_disc_turma, data_aula, conteudo) VALUES 
(1, '2025-03-03', 'Introdução à Lógica de Programação'),
(1, '2025-03-05', 'Estruturas Condicionais'),
(1, '2025-03-08', 'Estruturas de Repetição');

---

-- =========================
-- Tabela: frequencias (3 inserts)
-- Depende de: aulas (id_aula = 1, 2, 3), alunos (id_aluno = 1)
-- Focando no Aluno Carla (id_aluno=1)
-- =========================
INSERT INTO frequencias (id_aula, id_aluno, status) VALUES 
(1, 1, 'Presente'),   -- Carla na aula 1
(2, 1, 'Falta'),      -- Carla na aula 2
(3, 1, 'Presente');   -- Carla na aula 3

---

-- =========================
-- Tabela: avaliacoes (3 inserts)
-- Depende de: disciplinas_turmas (id_disc_turma = 1)
-- Focando na primeira disciplina/turma (id_disc_turma = 1)
-- =========================
INSERT INTO avaliacoes (id_disc_turma, titulo, peso, data_avaliacao) VALUES 
(1, 'Prova 1 - Lógica', 4.00, '2025-04-10'),
(1, 'Trabalho Prático - Arrays', 3.00, '2025-05-15'),
(1, 'Exame Final', 3.00, '2025-06-25');

---

-- =========================
-- Tabela: notas (3 inserts)
-- Depende de: avaliacoes (id_avaliacao = 1, 2, 3), alunos (id_aluno = 1)
-- Focando no Aluno Carla (id_aluno=1) nas três avaliações
-- =========================
INSERT INTO notas (id_avaliacao, id_aluno, valor) VALUES 
(1, 1, 8.5),   -- Nota de Carla na Prova 1
(2, 1, 9.0),   -- Nota de Carla no Trabalho Prático
(3, 1, 7.8);   -- Nota de Carla no Exame Final

---

-- =========================
-- Tabela: auditoria (3 inserts)
-- Depende de: usuarios (id_usuario = 1)
-- =========================
INSERT INTO auditoria (id_usuario, acao, entidade, id_entidade) VALUES 
(1, 'CRIAÇÃO', 'alunos', '1'),
(1, 'ATUALIZAÇÃO', 'usuarios', '3'),
(1, 'EXCLUSÃO', 'turmas', '99'); -- Ação simulada de exclusão



-- Exibir conteúdo da tabela papeis
SELECT * FROM papeis;

---

-- Exibir conteúdo da tabela usuarios
SELECT * FROM usuarios;

---

-- Exibir conteúdo da tabela alunos
SELECT * FROM alunos;

---

-- Exibir conteúdo da tabela professores
SELECT * FROM professores;

---

-- Exibir conteúdo da tabela cursos
SELECT * FROM cursos;

---

-- Exibir conteúdo da tabela disciplinas
SELECT * FROM disciplinas;

---

-- Exibir conteúdo da tabela turmas
SELECT * FROM turmas;

---

-- Exibir conteúdo da tabela disciplinas_turmas
SELECT * FROM disciplinas_turmas;

---

-- Exibir conteúdo da tabela matriculas
SELECT * FROM matriculas;

---

-- Exibir conteúdo da tabela aulas
SELECT * FROM aulas;

---

-- Exibir conteúdo da tabela frequencias
SELECT * FROM frequencias;

---

-- Exibir conteúdo da tabela avaliacoes
SELECT * FROM avaliacoes;

---

-- Exibir conteúdo da tabela notas
SELECT * FROM notas;

---

-- Exibir conteúdo da tabela auditoria
SELECT * FROM auditoria; -->