<?php
require_once 'restrição_acesso.php';
require_once '../conexão.php';

$id_turma = $id_curso = $nome_turma = $ano = $semestre = "";
$nome_turma_err = $id_curso_err = $ano_err = $semestre_err = $erro_geral = "";

// Consulta para buscar todos os cursos (necessário para o <select>)
$cursos_result = mysqli_query($link, "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso");

// 1. Carregar dados
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_turma_param = trim($_GET["id"]);

    $sql = "SELECT id_turma, id_curso, nome_turma, ano, semestre FROM turmas WHERE id_turma = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_turma);
        $param_id_turma = $id_turma_param;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_turma = $row["id_turma"];
                $id_curso = $row["id_curso"];
                $nome_turma = $row["nome_turma"];
                $ano = $row["ano"];
                $semestre = $row["semestre"];
            } else {
                header("location: gerenciar_turmas.php?erro=nao_encontrado");
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: gerenciar_turmas.php?erro=id_faltando");
    exit();
}


// 2. Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_turma = $_POST["id_turma"];
    
    $id_curso = trim($_POST["id_curso"]);
    $nome_turma = trim($_POST["nome_turma"]);
    $ano = trim($_POST["ano"]);
    $semestre = trim($_POST["semestre"]);

    // Validação
    if (empty($id_curso) || $id_curso == '0') { $id_curso_err = "Selecione o curso."; }
    if (empty($nome_turma)) { $nome_turma_err = "Insira o nome da turma."; }
    if (empty($ano) || !is_numeric($ano) || strlen($ano) != 4) { $ano_err = "Ano inválido."; }
    if (empty($semestre) || !in_array($semestre, [1, 2])) { $semestre_err = "Semestre inválido."; }

    if (empty($id_curso_err) && empty($nome_turma_err) && empty($ano_err) && empty($semestre_err)) {
        
        $sql = "UPDATE turmas SET id_curso = ?, nome_turma = ?, ano = ?, semestre = ? WHERE id_turma = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isisi", $param_id_curso, $param_nome_turma, $param_ano, $param_semestre, $param_id_turma);
            $param_id_curso = $id_curso;
            $param_nome_turma = $nome_turma;
            $param_ano = $ano;
            $param_semestre = $semestre;
            $param_id_turma = $id_turma;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_turmas.php?sucesso=edicao");
                exit;
            } else {
                // Erro pode ser devido à restrição UNIQUE (id_curso, nome_turma, ano, semestre)
                $erro_geral = "Erro ao atualizar turma. A combinação pode já existir: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $erro_geral = "Erro de preparação: " . mysqli_error($link);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Turma - Administrador</title>
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .alert { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu_admin.php'; ?>
        <h2>Editar Turma</h2>

        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_turma; ?>" method="post">
            <input type="hidden" name="id_turma" value="<?php echo $id_turma; ?>">

            <div class="form-group">
                <label>Curso Vinculado</label>
                <select name="id_curso" class="form-control">
                    <option value="0">Selecione o Curso</option>
                    <?php 
                    // Volta o ponteiro do resultado para o início para reusar na edição
                    mysqli_data_seek($cursos_result, 0); 
                    while($row = mysqli_fetch_assoc($cursos_result)): ?>
                        <option value="<?php echo $row['id_curso']; ?>" <?php echo ($id_curso == $row['id_curso']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nome_curso']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <span class="alert"><?php echo $id_curso_err; ?></span>
            </div>

            <div class="form-group">
                <label>Nome da Turma</label>
                <input type="text" name="nome_turma" class="form-control" value="<?php echo htmlspecialchars($nome_turma); ?>">
                <span class="alert"><?php echo $nome_turma_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Ano</label>
                <input type="number" name="ano" class="form-control" value="<?php echo htmlspecialchars($ano); ?>">
                <span class="alert"><?php echo $ano_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Semestre</label>
                <select name="semestre" class="form-control">
                    <option value="">Selecione</option>
                    <option value="1" <?php echo ($semestre == 1) ? 'selected' : ''; ?>>1º Semestre</option>
                    <option value="2" <?php echo ($semestre == 2) ? 'selected' : ''; ?>>2º Semestre</option>
                </select>
                <span class="alert"><?php echo $semestre_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Salvar Alterações">
                <a href="gerenciar_turmas.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>