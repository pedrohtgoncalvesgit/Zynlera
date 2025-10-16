<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_disc_turma = $id_turma = $id_professor = "";
$disciplina_nome = $professor_atual_nome = "";
$erro_geral = "";

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_disc_turma = trim($_GET["id"]);

    // Buscar dados da associação
    $sql = "SELECT dt.id_turma, d.nome_disciplina, dt.id_professor FROM disciplinas_turmas dt JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina WHERE dt.id_disc_turma = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_disc_turma);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_turma = $row['id_turma'];
                $disciplina_nome = $row['nome_disciplina'];
                $id_professor = $row['id_professor'];
            } else {
                $erro_geral = "Nenhum registro encontrado.";
            }
        } else {
            $erro_geral = "Erro ao executar a consulta.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $erro_geral = "Erro ao preparar a consulta.";
    }
} else {
    header("location: gerenciar_turmas.php");
    exit();
}

// Buscar todos os professores
$sql_professores = "SELECT p.id_professor, u.nome_completo FROM professores p JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY u.nome_completo";
$result_professores = mysqli_query($link, $sql_professores);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_professor']) && !empty($_POST['id_professor'])) {
        $novo_id_professor = $_POST['id_professor'];

        $sql_update = "UPDATE disciplinas_turmas SET id_professor = ? WHERE id_disc_turma = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ii", $novo_id_professor, $id_disc_turma);
            if (mysqli_stmt_execute($stmt_update)) {
                header("location: gerenciar_disciplinas_turma.php?id=" . $id_turma);
                exit();
            } else {
                $erro_geral = "Erro ao atualizar o professor.";
            }
            mysqli_stmt_close($stmt_update);
        }
    } else {
        $erro_geral = "Por favor, selecione um professor.";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Professor da Disciplina</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Editar Professor da Disciplina</h2>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
            <div class="form-group">
                <label>Disciplina</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($disciplina_nome); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Professor</label>
                <select name="id_professor" class="form-control">
                    <?php while($professor = mysqli_fetch_assoc($result_professores)): ?>
                        <option value="<?php echo $professor['id_professor']; ?>" <?php echo ($id_professor == $professor['id_professor']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($professor['nome_completo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Salvar">
                <a href="gerenciar_disciplinas_turma.php?id=<?php echo $id_turma; ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
