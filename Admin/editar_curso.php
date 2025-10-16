<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_curso = $nome_curso = $descricao = "";
$nome_curso_err = $erro_geral = "";

// 1. Carregar dados
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_curso_param = trim($_GET["id"]);

    $sql = "SELECT id_curso, nome_curso, descricao FROM cursos WHERE id_curso = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_curso);
        $param_id_curso = $id_curso_param;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_curso = $row["id_curso"];
                $nome_curso = $row["nome_curso"];
                $descricao = $row["descricao"];
            } else {
                header("location: gerenciar_cursos.php?erro=nao_encontrado");
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: gerenciar_cursos.php?erro=id_faltando");
    exit();
}


// 2. Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_curso = $_POST["id_curso"];

    if (empty(trim($_POST["nome_curso"]))) { 
        $nome_curso_err = "Insira o nome do curso."; 
    } else { 
        $nome_curso = trim($_POST["nome_curso"]); 
    }
    $descricao = trim($_POST["descricao"]);

    if (empty($nome_curso_err)) {
        
        $sql = "UPDATE cursos SET nome_curso = ?, descricao = ? WHERE id_curso = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $param_nome, $param_descricao, $param_id_curso);
            $param_nome = $nome_curso;
            $param_descricao = $descricao;
            $param_id_curso = $id_curso;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_cursos.php?sucesso=edicao");
                exit;
            } else {
                $erro_geral = "Erro ao atualizar curso (Nome duplicado?): " . mysqli_error($link);
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
    <title>Editar Curso - Administrador</title>
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
        <h2>Editar Curso: <?php echo htmlspecialchars($nome_curso); ?></h2>

        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_curso; ?>" method="post">
            <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">

            <div class="form-group">
                <label>Nome do Curso</label>
                <input type="text" name="nome_curso" class="form-control" value="<?php echo htmlspecialchars($nome_curso); ?>">
                <span class="alert"><?php echo $nome_curso_err; ?></span>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control"><?php echo htmlspecialchars($descricao); ?></textarea>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Salvar Alterações">
                <a href="gerenciar_cursos.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>