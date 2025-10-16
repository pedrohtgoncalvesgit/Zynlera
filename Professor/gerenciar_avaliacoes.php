<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');
include_once('restricao_acesso_professor.php');

if (!isset($_GET['id_disc_turma'])) {
    header("Location: gerenciar_turmas.php");
    exit();
}

$id_disc_turma = $_GET['id_disc_turma'];

// Salvar nova avaliação
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];
    $peso = $_POST['peso'];
    $data_avaliacao = $_POST['data_avaliacao'];

    $query_insert_avaliacao = "INSERT INTO avaliacoes (id_disc_turma, titulo, peso, data_avaliacao) VALUES (?, ?, ?, ?)";
    $stmt_insert_avaliacao = $conexao->prepare($query_insert_avaliacao);
    $stmt_insert_avaliacao->bind_param("isds", $id_disc_turma, $titulo, $peso, $data_avaliacao);
    $stmt_insert_avaliacao->execute();
}

// Buscar avaliações existentes
$query_avaliacoes = "SELECT id_avaliacao, titulo, peso, data_avaliacao FROM avaliacoes WHERE id_disc_turma = ? ORDER BY data_avaliacao DESC";
$stmt_avaliacoes = $conexao->prepare($query_avaliacoes);
$stmt_avaliacoes->bind_param("i", $id_disc_turma);
$stmt_avaliacoes->execute();
$resultado_avaliacoes = $stmt_avaliacoes->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Avaliações</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include 'menu_professor.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center">Gerenciar Avaliações</h2>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="mb-0">Nova Avaliação</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="col">
                            <input type="text" name="titulo" class="form-control" placeholder="Título da Avaliação" required>
                        </div>
                        <div class="col">
                            <input type="number" step="0.01" name="peso" class="form-control" placeholder="Peso" required>
                        </div>
                        <div class="col">
                            <input type="date" name="data_avaliacao" class="form-control" required>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-primary">Criar Avaliação</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h3 class="mt-5">Avaliações Criadas</h3>
        <?php if ($resultado_avaliacoes->num_rows > 0) : ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Peso</th>
                    <th>Data de Criação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($avaliacao = $resultado_avaliacoes->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($avaliacao['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($avaliacao['peso']); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($avaliacao['data_avaliacao']))); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
            <div class="alert alert-info mt-3">Nenhuma avaliação criada para esta turma/disciplina.</div>
        <?php endif; ?>

        <a href="gerenciar_turmas.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>
</body>
</html>
