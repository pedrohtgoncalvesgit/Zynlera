<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// Buscar cursos para o dropdown
$sql_cursos = "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso";
$result_cursos = mysqli_query($link, $sql_cursos);

$nome_turma = $ano = $semestre = $id_curso = "";
$nome_turma_err = $ano_err = $semestre_err = $id_curso_err = $erro_geral = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["nome_turma"]))) {
        $nome_turma_err = "Insira o nome da turma.";
    } else {
        $nome_turma = trim($_POST["nome_turma"]);
    }

    if (empty(trim($_POST["ano"]))) {
        $ano_err = "Insira o ano.";
    } else {
        $ano = trim($_POST["ano"]);
    }

    if (empty(trim($_POST["semestre"]))) {
        $semestre_err = "Insira o semestre.";
    } else {
        $semestre = trim($_POST["semestre"]);
    }

    if (empty($_POST["id_curso"])) {
        $id_curso_err = "Selecione o curso.";
    } else {
        $id_curso = $_POST["id_curso"];
    }

    if (empty($nome_turma_err) && empty($ano_err) && empty($semestre_err) && empty($id_curso_err)) {
        $sql = "INSERT INTO turmas (nome_turma, ano, semestre, id_curso) VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $nome_turma, $ano, $semestre, $id_curso);

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_turmas.php");
                exit();
            } else {
                $erro_geral = "Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Nova Turma</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Cadastrar Nova Turma</h2>
        <p>Preencha o formulário para criar uma nova turma.</p>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Nome da Turma</label>
                <input type="text" name="nome_turma" class="form-control <?php echo (!empty($nome_turma_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nome_turma; ?>">
                <span class="invalid-feedback"><?php echo $nome_turma_err; ?></span>
            </div>
            <div class="form-group">
                <label>Ano</label>
                <input type="number" name="ano" class="form-control <?php echo (!empty($ano_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $ano; ?>">
                <span class="invalid-feedback"><?php echo $ano_err; ?></span>
            </div>
            <div class="form-group">
                <label>Semestre</label>
                <input type="number" name="semestre" class="form-control <?php echo (!empty($semestre_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $semestre; ?>">
                <span class="invalid-feedback"><?php echo $semestre_err; ?></span>
            </div>
            <div class="form-group">
                <label>Curso</label>
                <select name="id_curso" class="form-control <?php echo (!empty($id_curso_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Selecione...</option>
                    <?php while($curso = mysqli_fetch_assoc($result_cursos)) : ?>
                        <option value="<?php echo $curso['id_curso']; ?>" <?php echo ($id_curso == $curso['id_curso']) ? 'selected' : ''; ?>><?php echo $curso['nome_curso']; ?></option>
                    <?php endwhile; ?>
                </select>
                <span class="invalid-feedback"><?php echo $id_curso_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Cadastrar">
                <a href="gerenciar_turmas.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>