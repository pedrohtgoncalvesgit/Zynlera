<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');
include_once('restricao_acesso_professor.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

$id_professor = $_SESSION['id_professor'];
$mensagem_sucesso = ""; // Variável para armazenar mensagens de sucesso

$query_turmas = "SELECT t.id_turma, t.nome_turma, c.nome_curso, d.nome_disciplina, dt.id_disc_turma
                 FROM turmas t
                 JOIN cursos c ON t.id_curso = c.id_curso
                 JOIN disciplinas_turmas dt ON t.id_turma = dt.id_turma
                 JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
                 WHERE dt.id_professor = ?";

$stmt = $conexao->prepare($query_turmas);
$stmt->bind_param("i", $id_professor);
$stmt->execute();
$resultado_turmas = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_notas'])) {
    $id_avaliacao = $_POST['id_avaliacao'];
    $notas = $_POST['notas'];

    $query_insert_nota = "INSERT INTO notas (id_avaliacao, id_aluno, valor) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
    $stmt_insert_nota = $conexao->prepare($query_insert_nota);

    foreach ($notas as $id_aluno => $valor) {
        if (trim($valor) !== '') { // Verifica se o valor não está vazio
            $stmt_insert_nota->bind_param("iid", $id_avaliacao, $id_aluno, $valor);
            $stmt_insert_nota->execute();
        }
    }
    $mensagem_sucesso = "Notas salvas com sucesso!";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_faltas'])) {
    $id_disc_turma = $_GET['id_disc_turma'];
    $data_aula = $_POST['data_aula'];
    $faltas = $_POST['faltas'];

    // Criar aula se não existir
    $query_insert_aula = "INSERT IGNORE INTO aulas (id_disc_turma, data_aula) VALUES (?, ?)";
    $stmt_insert_aula = $conexao->prepare($query_insert_aula);
    $stmt_insert_aula->bind_param("is", $id_disc_turma, $data_aula);
    $stmt_insert_aula->execute();

    // Buscar id_aula
    $query_aula = "SELECT id_aula FROM aulas WHERE id_disc_turma = ? AND data_aula = ?";
    $stmt_aula = $conexao->prepare($query_aula);
    $stmt_aula->bind_param("is", $id_disc_turma, $data_aula);
    $stmt_aula->execute();
    $resultado_aula = $stmt_aula->get_result();
    $aula = $resultado_aula->fetch_assoc();
    $id_aula = $aula['id_aula'];

    // Salvar faltas
    $query_insert_falta = "INSERT INTO frequencias (id_aula, id_aluno, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)";
    $stmt_insert_falta = $conexao->prepare($query_insert_falta);

    foreach ($faltas as $id_aluno => $status) {
        $stmt_insert_falta->bind_param("iis", $id_aula, $id_aluno, $status);
        $stmt_insert_falta->execute();
    }
    $mensagem_sucesso = "Frequência salva com sucesso!";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif; margin: 0; background-color: #f4f7fa;
            color: #333; display: flex; flex-direction: column; min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(90deg, #0056b3, #007bff); color: white;
            padding: 10px 30px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo { font-size: 1.5em; font-weight: 700; }
        
        .main-nav a {
            color: white; text-decoration: none; margin-left: 20px;
            font-weight: 500; opacity: 0.9; transition: opacity 0.3s;
        }
        .main-nav a:hover { opacity: 1; }

        .container {
            flex: 1; padding: 30px; max-width: 1200px;
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }
        
        h1 { font-size: 1.8em; color: #0056b3; margin-bottom: 20px; }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
            margin-bottom: 30px;
        }

        .content-card h2 {
            font-size: 1.5em; color: #0056b3; margin-top: 0;
            margin-bottom: 20px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }
        
        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-radius: 8px;
            overflow: hidden;
        }

        .data-table th, .data-table td {
            border: 1px solid #e0e0e0; padding: 12px 15px; text-align: left;
            vertical-align: middle;
        }

        .data-table thead th {
            background-color: #f0f8ff; color: #333; font-weight: 600;
            text-transform: uppercase; font-size: 0.9em;
        }

        .data-table tbody tr:nth-child(even) { background-color: #fdfdfd; }
        .data-table tbody tr:hover { background-color: #eef7ff; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: #555;
        }
        .form-control {
            width: 100%; padding: 12px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
        }

        .btn {
            padding: 8px 15px; text-decoration: none; border-radius: 6px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-sm { font-size: 0.8em; padding: 6px 12px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-info { background-color: #17a2b8; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        
        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-warning, .no-data-message {
            background-color: #fff3cd; color: #856404; padding: 15px;
            border: 1px solid #ffeeba; border-radius: 8px; margin-top: 20px;
        }
        .alert-warning a { color: #856404; font-weight: bold; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do Professor</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Minhas Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <h1>Minhas Turmas</h1>

        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h2><i class="fa-solid fa-list-check"></i> Turmas e Disciplinas Lecionadas</h2>
            <?php if ($resultado_turmas->num_rows > 0) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Turma</th>
                        <th>Curso</th>
                        <th>Disciplina</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($turma = $resultado_turmas->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($turma['nome_turma']); ?></td>
                            <td><?php echo htmlspecialchars($turma['nome_curso']); ?></td>
                            <td><?php echo htmlspecialchars($turma['nome_disciplina']); ?></td>
                            <td>
                                <a href="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $turma['id_turma']; ?>&id_disc_turma=<?php echo $turma['id_disc_turma']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-users"></i> Alunos</a>
                                <a href="gerenciar_avaliacoes.php?id_disc_turma=<?php echo $turma['id_disc_turma']; ?>" class="btn btn-info btn-sm"><i class="fa-solid fa-clipboard-list"></i> Avaliações</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else : ?>
                <div class="no-data-message">Nenhuma turma encontrada para este professor.</div>
            <?php endif; ?>
        </div>

        <?php
        if (isset($_GET['view_students']) && $_GET['view_students'] == 'true' && isset($_GET['id_turma']) && isset($_GET['id_disc_turma'])) {
            $id_turma_alunos = $_GET['id_turma'];
            $id_disc_turma = $_GET['id_disc_turma'];

            // Buscar alunos da turma
            $query_alunos = "SELECT a.id_aluno, u.nome_completo
                             FROM alunos a
                             JOIN usuarios u ON a.id_usuario = u.id_usuario
                             JOIN matriculas m ON a.id_aluno = m.id_aluno
                             WHERE m.id_turma = ? AND m.situacao = 'ativa'";
            $stmt_alunos = $conexao->prepare($query_alunos);
            $stmt_alunos->bind_param("i", $id_turma_alunos);
            $stmt_alunos->execute();
            $resultado_alunos = $stmt_alunos->get_result();

            // Buscar avaliações
            $query_avaliacoes = "SELECT id_avaliacao, titulo FROM avaliacoes WHERE id_disc_turma = ? ORDER BY titulo";
            $stmt_avaliacoes = $conexao->prepare($query_avaliacoes);
            $stmt_avaliacoes->bind_param("i", $id_disc_turma);
            $stmt_avaliacoes->execute();
            $resultado_avaliacoes = $stmt_avaliacoes->get_result();
        ?>

        <div class="content-card">
            <h2><i class="fa-solid fa-marker"></i> Lançar Notas</h2>
            <?php if ($resultado_avaliacoes->num_rows > 0) : ?>
                <form method="POST" action="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $id_turma_alunos; ?>&id_disc_turma=<?php echo $id_disc_turma; ?>">
                    <div class="form-group">
                        <label for="id_avaliacao">Selecione a Avaliação</label>
                        <select name="id_avaliacao" id="id_avaliacao" class="form-control">
                            <?php while ($avaliacao = $resultado_avaliacoes->fetch_assoc()) : ?>
                                <option value="<?php echo $avaliacao['id_avaliacao']; ?>"><?php echo htmlspecialchars($avaliacao['titulo']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Nota (0.00 a 10.00)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($aluno = $resultado_alunos->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="10" name="notas[<?php echo $aluno['id_aluno']; ?>]" class="form-control" placeholder="Ex: 8.50">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="salvar_notas" class="btn btn-primary" style="margin-top: 20px;">Salvar Notas</button>
                </form>
            <?php else : ?>
                <div class="alert-warning">Nenhuma avaliação encontrada para esta disciplina. Por favor, <a href="gerenciar_avaliacoes.php?id_disc_turma=<?php echo $id_disc_turma; ?>">crie uma avaliação</a> antes de lançar notas.</div>
            <?php endif; ?>
        </div>

        <?php
            // Reset result set pointer para reutilizar a lista de alunos
            $resultado_alunos->data_seek(0);
        ?>

        <div class="content-card">
            <h2><i class="fa-solid fa-calendar-check"></i> Lançar Frequência</h2>
            <form method="POST" action="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $id_turma_alunos; ?>&id_disc_turma=<?php echo $id_disc_turma; ?>">
                <div class="form-group">
                    <label for="data_aula">Selecione a Data da Aula</label>
                    <input type="date" name="data_aula" id="data_aula" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($aluno = $resultado_alunos->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                <td>
                                    <select name="faltas[<?php echo $aluno['id_aluno']; ?>]" class="form-control">
                                        <option value="Presente">Presente</option>
                                        <option value="Falta">Falta</option>
                                        <option value="Justificada">Justificada</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" name="salvar_faltas" class="btn btn-primary" style="margin-top: 20px;">Salvar Frequência</button>
            </form>
        </div>
        <?php
        }
        ?>
    </main>

</body>
</html>