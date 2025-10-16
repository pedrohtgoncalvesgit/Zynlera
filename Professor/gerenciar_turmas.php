<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');
include_once('restricao_acesso_professor.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}



$id_professor = $_SESSION['id_professor'];

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
        if (!empty($valor)) {
            $stmt_insert_nota->bind_param("iid", $id_avaliacao, $id_aluno, $valor);
            $stmt_insert_nota->execute();
        }
    }
    echo "<div class='alert alert-success'>Notas salvas com sucesso!</div>";
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
    echo "<div class='alert alert-success'>Faltas salvas com sucesso!</div>";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Minhas Turmas</h2>
        <?php if ($resultado_turmas->num_rows > 0) : ?>
        <table class="table table-bordered mt-4">
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
                            <a href="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $turma['id_turma']; ?>&id_disc_turma=<?php echo $turma['id_disc_turma']; ?>" class="btn btn-primary btn-sm">Alunos</a>
                            <a href="gerenciar_avaliacoes.php?id_disc_turma=<?php echo $turma['id_disc_turma']; ?>" class="btn btn-info btn-sm">Avaliações</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
            <div class="alert alert-info mt-4">Nenhuma turma encontrada para este professor.</div>
        <?php endif; ?>
        <a href="menu_professor.php" class="btn btn-secondary">Voltar</a>

        <?php
        if (isset($_GET['view_students']) && $_GET['view_students'] == 'true' && isset($_GET['id_turma']) && isset($_GET['id_disc_turma'])) {
            $id_turma_alunos = $_GET['id_turma'];
            $id_disc_turma = $_GET['id_disc_turma'];

            // Buscar alunos da turma
            $query_alunos = "SELECT a.id_aluno, u.nome_completo
                             FROM alunos a
                             JOIN usuarios u ON a.id_usuario = u.id_usuario
                             JOIN matriculas m ON a.id_aluno = m.id_aluno
                             WHERE m.id_turma = ?";
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
            <hr>
            <h2 class="text-center">Lançar Notas</h2>
            <?php if ($resultado_avaliacoes->num_rows > 0) : ?>
                <form method="POST" action="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $id_turma_alunos; ?>&id_disc_turma=<?php echo $id_disc_turma; ?>">
                    <div class="form-group">
                        <label for="id_avaliacao">Avaliação</label>
                        <select name="id_avaliacao" id="id_avaliacao" class="form-control">
                            <?php while ($avaliacao = $resultado_avaliacoes->fetch_assoc()) : ?>
                                <option value="<?php echo $avaliacao['id_avaliacao']; ?>"><?php echo htmlspecialchars($avaliacao['titulo']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <table class="table table-bordered mt-4">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($aluno = $resultado_alunos->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                    <td>
                                        <input type="number" step="0.01" name="notas[<?php echo $aluno['id_aluno']; ?>]" class="form-control">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="salvar_notas" class="btn btn-primary">Salvar Notas</button>
                </form>
            <?php else : ?>
                <div class="alert alert-warning">Nenhuma avaliação encontrada para esta disciplina. Por favor, <a href="gerenciar_avaliacoes.php?id_disc_turma=<?php echo $id_disc_turma; ?>">crie uma avaliação</a> antes de lançar notas.</div>
            <?php endif; ?>

            <?php
            // Reset result set pointer
            $resultado_alunos->data_seek(0);
            ?>

            <hr>
            <h2 class="text-center">Lançar Faltas</h2>
            <form method="POST" action="gerenciar_turmas.php?view_students=true&id_turma=<?php echo $id_turma_alunos; ?>&id_disc_turma=<?php echo $id_disc_turma; ?>">
                 <div class="form-group">
                    <label for="data_aula">Data da Aula</label>
                    <input type="date" name="data_aula" id="data_aula" class="form-control" required>
                </div>
                <table class="table table-bordered mt-4">
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
                <button type="submit" name="salvar_faltas" class="btn btn-primary">Salvar Faltas</button>
            </form>
        <?php
        }
        ?>
    </div>
</body>
</html>