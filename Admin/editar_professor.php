<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';


// Variáveis para armazenar os dados atuais e erros
$id_professor = $id_usuario = $nome_completo = $email = $registro_funcional = $data_admissao = $ativo = "";
$nome_completo_err = $email_err = $registro_funcional_err = $data_admissao_err = $erro_geral = "";

// 1. Processamento da requisição GET (Carregar dados)
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_professor_param = trim($_GET["id"]);

    // Busca os dados do Professor e do Usuário
    $sql = "SELECT 
                p.id_professor, p.id_usuario, p.registro_funcional, p.data_admissao,
                u.nome_completo, u.email, u.ativo
            FROM professores p
            INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_professor = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_professor);
        $param_id_professor = $id_professor_param;

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_professor = $row["id_professor"];
                $id_usuario = $row["id_usuario"];
                $nome_completo = $row["nome_completo"];
                $email = $row["email"];
                $registro_funcional = $row["registro_funcional"];
                $data_admissao = $row["data_admissao"];
                $ativo = $row["ativo"];
            } else {
                header("location: gerenciar_professores.php?erro=nao_encontrado");
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Se não for POST e não tiver ID na URL
    header("location: gerenciar_professores.php?erro=id_faltando");
    exit();
}


// 2. Processamento da requisição POST (Atualizar dados)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_professor = $_POST["id_professor"];
    $id_usuario = $_POST["id_usuario"];
    
    // Validação dos dados
    if (empty(trim($_POST["nome_completo"]))) { $nome_completo_err = "Insira o nome completo."; } else { $nome_completo = trim($_POST["nome_completo"]); }
    if (empty(trim($_POST["email"]))) { $email_err = "Insira um email."; } else { $email = trim($_POST["email"]); }
    if (empty(trim($_POST["registro_funcional"]))) { $registro_funcional_err = "Insira o registro funcional."; } else { $registro_funcional = trim($_POST["registro_funcional"]); }
    if (empty(trim($_POST["data_admissao"]))) { $data_admissao_err = "Insira a data de admissão."; } else { $data_admissao = trim($_POST["data_admissao"]); }
    $ativo = (isset($_POST["ativo"]) && $_POST["ativo"] == 0) ? 0 : 1;

    if (empty($nome_completo_err) && empty($email_err) && empty($registro_funcional_err) && empty($data_admissao_err)) {
        
        mysqli_begin_transaction($link);
        $sucesso = true;

        // Atualiza a tabela usuarios
        $sql_usuario = "UPDATE usuarios SET nome_completo = ?, email = ?, ativo = ? WHERE id_usuario = ?";
        if ($stmt_usuario = mysqli_prepare($link, $sql_usuario)) {
            mysqli_stmt_bind_param($stmt_usuario, "ssii", $param_nome, $param_email, $param_ativo, $param_id_usuario);
            $param_nome = $nome_completo;
            $param_email = $email;
            $param_ativo = $ativo;
            $param_id_usuario = $id_usuario;

            if (!mysqli_stmt_execute($stmt_usuario)) {
                $erro_geral = "Erro ao atualizar o usuário: " . mysqli_error($link);
                $sucesso = false;
            }
            mysqli_stmt_close($stmt_usuario);
        } else {
            $erro_geral = "Erro de preparação (usuários): " . mysqli_error($link);
            $sucesso = false;
        }

        // Atualiza a tabela professores
        if ($sucesso) {
            $sql_professor = "UPDATE professores SET registro_funcional = ?, data_admissao = ? WHERE id_professor = ?";
            if ($stmt_professor = mysqli_prepare($link, $sql_professor)) {
                mysqli_stmt_bind_param($stmt_professor, "ssi", $param_registro, $param_data_admissao, $param_id_professor);
                $param_registro = $registro_funcional;
                $param_data_admissao = $data_admissao;
                $param_id_professor = $id_professor;

                if (!mysqli_stmt_execute($stmt_professor)) {
                    $erro_geral .= " Erro ao atualizar os dados do professor: " . mysqli_error($link);
                    $sucesso = false;
                }
                mysqli_stmt_close($stmt_professor);
            } else {
                $erro_geral .= " Erro de preparação (professores): " . mysqli_error($link);
                $sucesso = false;
            }
        }

        // Finaliza a transação
        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_professores.php?sucesso=edicao");
            exit;
        } else {
            mysqli_rollback($link);
            // Recarrega os dados para mostrar o erro
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Professor - Administrador</title>
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
        <h2>Editar Professor: <?php echo htmlspecialchars($nome_completo); ?></h2>
        
        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_professor; ?>" method="post">
            <input type="hidden" name="id_professor" value="<?php echo $id_professor; ?>">
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" name="nome_completo" class="form-control" value="<?php echo htmlspecialchars($nome_completo); ?>">
                <span class="alert"><?php echo $nome_completo_err; ?></span>
            </div>
            <div class="form-group">
                <label>Email (Login)</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                <span class="alert"><?php echo $email_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Registro Funcional</label>
                <input type="text" name="registro_funcional" class="form-control" value="<?php echo htmlspecialchars($registro_funcional); ?>">
                <span class="alert"><?php echo $registro_funcional_err; ?></span>
            </div>
            <div class="form-group">
                <label>Data de Admissão</label>
                <input type="date" name="data_admissao" class="form-control" value="<?php echo htmlspecialchars($data_admissao); ?>">
                <span class="alert"><?php echo $data_admissao_err; ?></span>
            </div>

            <div class="form-group">
                <label>Status do Professor</label>
                <select name="ativo" class="form-control">
                    <option value="1" <?php echo ($ativo == 1) ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo ($ativo == 0) ? 'selected' : ''; ?>>Inativo</option>
                </select>
                <p style="font-size: 0.8em; color: #6c757d;">Se inativado, o professor não poderá mais fazer login.</p>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Salvar Alterações">
                <a href="gerenciar_professores.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
mysqli_close($link);
?>