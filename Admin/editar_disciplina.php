<?php
require_once 'restricao_acesso.php';
$link = require_once '../conexao.php';

$id_disciplina = $id_curso = $nome_disciplina = $codigo = $carga_horaria = "";
$id_curso_err = $nome_disciplina_err = $codigo_err = $carga_horaria_err = $erro_geral = "";

// Consulta para buscar todos os cursos (necessário para o <select>)
$cursos_result = mysqli_query($link, "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso");

// 1. Carregar dados
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_disciplina_param = trim($_GET["id"]);

    $sql = "SELECT id_disciplina, id_curso, nome_disciplina, codigo, carga_horaria FROM disciplinas WHERE id_disciplina = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_disciplina);
        $param_id_disciplina = $id_disciplina_param;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_disciplina = $row["id_disciplina"];
                $id_curso = $row["id_curso"];
                $nome_disciplina = $row["nome_disciplina"];
                $codigo = $row["codigo"];
                $carga_horaria = $row["carga_horaria"];
            } else {
                header("location: gerenciar_disciplinas.php?erro=nao_encontrado");
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: gerenciar_disciplinas.php?erro=id_faltando");
    exit();
}


// 2. Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_disciplina = $_POST["id_disciplina"];
    
    $id_curso = trim($_POST["id_curso"]);
    $nome_disciplina = trim($_POST["nome_disciplina"]);
    $codigo = trim($_POST["codigo"]);
    $carga_horaria = trim($_POST["carga_horaria"]);

    // Validação
    if (empty($id_curso) || $id_curso == '0') { $id_curso_err = "Selecione o curso."; }
    if (empty($nome_disciplina)) { $nome_disciplina_err = "Insira o nome da disciplina."; }
    if (empty($codigo)) { $codigo_err = "Insira o código."; }
    if (empty($carga_horaria) || !is_numeric($carga_horaria) || $carga_horaria <= 0) { $carga_horaria_err = "Carga horária inválida."; }

    if (empty($id_curso_err) && empty($nome_disciplina_err) && empty($codigo_err) && empty($carga_horaria_err)) {
        
        $sql = "UPDATE disciplinas SET id_curso = ?, nome_disciplina = ?, codigo = ?, carga_horaria = ? WHERE id_disciplina = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issii", $param_id_curso, $param_nome, $param_codigo, $param_carga, $param_id_disciplina);
            $param_id_curso = $id_curso;
            $param_nome = $nome_disciplina;
            $param_codigo = $codigo;
            $param_carga = $carga_horaria;
            $param_id_disciplina = $id_disciplina;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_disciplinas.php?sucesso=edicao");
                exit;
            } else {
                // Erro pode ser devido à restrição UNIQUE no 'codigo'
                $erro_geral = "Erro ao atualizar disciplina. O código da disciplina pode já existir: " . mysqli_error($link);
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
    <title>Editar Disciplina - Administrador</title>
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
        <h2>Editar Disciplina: <?php echo htmlspecialchars($nome_disciplina); ?></h2>

        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_disciplina; ?>" method="post">
            <input type="hidden" name="id_disciplina" value="<?php echo $id_disciplina; ?>">

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
                <label>Nome da Disciplina</label>
                <input type="text" name="nome_disciplina" class="form-control" value="<?php echo htmlspecialchars($nome_disciplina); ?>">
                <span class="alert"><?php echo $nome_disciplina_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Código da Disciplina (Ex: ADS001)</label>
                <input type="text" name="codigo" class="form-control" value="<?php echo htmlspecialchars($codigo); ?>">
                <span class="alert"><?php echo $codigo_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Carga Horária (em horas)</label>
                <input type="number" name="carga_horaria" class="form-control" value="<?php echo htmlspecialchars($carga_horaria); ?>">
                <span class="alert"><?php echo $carga_horaria_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Salvar Alterações">
                <a href="gerenciar_disciplinas.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>