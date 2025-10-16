<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');

if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['papel']) != 'aluno') {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Buscar id_aluno
$query_aluno = "SELECT id_aluno FROM alunos WHERE id_usuario = ?";
$stmt_aluno = $conexao->prepare($query_aluno);
$stmt_aluno->bind_param("i", $id_usuario);
$stmt_aluno->execute();
$resultado_aluno = $stmt_aluno->get_result();
$aluno = $resultado_aluno->fetch_assoc();
$id_aluno = $aluno['id_aluno'];

// Buscar notas
$query_notas = "SELECT d.nome_disciplina, av.titulo, n.valor
                FROM notas n
                JOIN avaliacoes av ON n.id_avaliacao = av.id_avaliacao
                JOIN disciplinas_turmas dt ON av.id_disc_turma = dt.id_disc_turma
                JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
                WHERE n.id_aluno = ?
                ORDER BY d.nome_disciplina, av.titulo";
$stmt_notas = $conexao->prepare($query_notas);
$stmt_notas->bind_param("i", $id_aluno);
$stmt_notas->execute();
$resultado_notas = $stmt_notas->get_result();

// Buscar faltas
$query_faltas = "SELECT d.nome_disciplina, a.data_aula, f.status
                 FROM frequencias f
                 JOIN aulas a ON f.id_aula = a.id_aula
                 JOIN disciplinas_turmas dt ON a.id_disc_turma = dt.id_disc_turma
                 JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
                 WHERE f.id_aluno = ? AND f.status = 'Falta'
                 ORDER BY d.nome_disciplina, a.data_aula";
$stmt_faltas = $conexao->prepare($query_faltas);
$stmt_faltas->bind_param("i", $id_aluno);
$stmt_faltas->execute();
$resultado_faltas = $stmt_faltas->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notas e Faltas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Minhas Notas e Faltas</h2>

        <h3 class="mt-5">Notas</h3>
        <?php if ($resultado_notas->num_rows > 0) : ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Avaliação</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($nota = $resultado_notas->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($nota['nome_disciplina']); ?></td>
                        <td><?php echo htmlspecialchars($nota['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($nota['valor']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
            <div class="alert alert-info mt-3">Nenhuma nota lançada até o momento.</div>
        <?php endif; ?>

        <h3 class="mt-5">Faltas</h3>
        <?php if ($resultado_faltas->num_rows > 0) : ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Data da Aula</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($falta = $resultado_faltas->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($falta['nome_disciplina']); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($falta['data_aula']))); ?></td>
                        <td><?php echo htmlspecialchars($falta['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
            <div class="alert alert-info mt-3">Nenhuma falta registrada até o momento.</div>
        <?php endif; ?>

        <a href="../dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>
</body>
</html>
