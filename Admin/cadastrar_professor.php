<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';


// Define variáveis e inicializa com valores vazios
$nome_completo = $email = $senha = $registro_funcional = $data_admissao = "";
$nome_completo_err = $email_err = $senha_err = $registro_funcional_err = $data_admissao_err = $erro_geral = "";

// ID de Papel para Professor (deve ser o ID correspondente, vamos assumir 2)
$ID_PAPEL_PROFESSOR = 2; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validação dos Campos
    if (empty(trim($_POST["nome_completo"]))) { $nome_completo_err = "Insira o nome completo."; } else { $nome_completo = trim($_POST["nome_completo"]); }
    if (empty(trim($_POST["email"]))) { $email_err = "Insira um email."; } else { $email = trim($_POST["email"]); }
    if (empty(trim($_POST["senha"]))) { $senha_err = "Insira uma senha."; } else { $senha = trim($_POST["senha"]); }
    if (empty(trim($_POST["registro_funcional"]))) { $registro_funcional_err = "Insira o registro funcional."; } else { $registro_funcional = trim($_POST["registro_funcional"]); }
    if (empty(trim($_POST["data_admissao"]))) { $data_admissao_err = "Insira a data de admissão."; } else { $data_admissao = trim($_POST["data_admissao"]); }

    if (empty($nome_completo_err) && empty($email_err) && empty($senha_err) && empty($registro_funcional_err) && empty($data_admissao_err)) {
        
        // Inicia a transação
        mysqli_begin_transaction($link);
        $sucesso = true;

        // Tabela 1: usuarios
        $sql_usuario = "INSERT INTO usuarios (id_papel, nome_completo, email, senha, ativo) VALUES (?, ?, ?, ?, ?)";
        if ($stmt_usuario = mysqli_prepare($link, $sql_usuario)) {
            mysqli_stmt_bind_param($stmt_usuario, "isssi", $param_papel, $param_nome, $param_email, $param_senha, $param_ativo);
            $param_papel = $ID_PAPEL_PROFESSOR;
            $param_nome = $nome_completo;
            $param_email = $email;
            $param_senha = $senha;
            $param_ativo = 1; // Define o usuário como ativo

            if (mysqli_stmt_execute($stmt_usuario)) {
                $id_usuario = mysqli_insert_id($link); 
                mysqli_stmt_close($stmt_usuario);

                // Tabela 2: professores
                $sql_professor = "INSERT INTO professores (id_usuario, registro_funcional, data_admissao) VALUES (?, ?, ?)";
                if ($stmt_professor = mysqli_prepare($link, $sql_professor)) {
                    mysqli_stmt_bind_param($stmt_professor, "iss", $param_id_usuario, $param_registro, $param_data_admissao);
                    $param_id_usuario = $id_usuario;
                    $param_registro = $registro_funcional;
                    $param_data_admissao = $data_admissao;

                    if (!mysqli_stmt_execute($stmt_professor)) {
                        $erro_geral = "Erro ao inserir na tabela professores: " . mysqli_error($link);
                        $sucesso = false;
                    }
                    mysqli_stmt_close($stmt_professor);
                } else {
                    $erro_geral = "Erro de preparação (professores): " . mysqli_error($link);
                    $sucesso = false;
                }
            } else {
                $erro_geral = "Erro ao inserir usuário (Email ou Registro Funcional duplicado?): " . mysqli_error($link);
                $sucesso = false;
            }
        } else {
            $erro_geral = "Erro de preparação (usuários): " . mysqli_error($link);
            $sucesso = false;
        }

        // Finaliza a transação
        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_professores.php?sucesso=cadastro");
            exit;
        } else {
            mysqli_rollback($link);
            // Tentativa de limpar o registro de usuário se o erro ocorreu na tabela 'professores'
            if (isset($id_usuario)) {
                $sql_delete_user = "DELETE FROM usuarios WHERE id_usuario = ?";
                if ($stmt_del = mysqli_prepare($link, $sql_delete_user)) {
                    mysqli_stmt_bind_param($stmt_del, "i", $id_usuario);
                    mysqli_stmt_execute($stmt_del);
                    mysqli_stmt_close($stmt_del);
                }
            }
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Professor - Administrador</title>
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
        <h2>Cadastrar Novo Professor</h2>
        <p>Preencha os dados do novo professor.</p>

        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                <label>Senha Provisória (Login)</label>
                <input type="text" name="senha" class="form-control" value="<?php echo htmlspecialchars($senha); ?>">
                <span class="alert"><?php echo $senha_err; ?></span>
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
                <input type="submit" class="btn" value="Cadastrar Professor">
                <a href="gerenciar_professores.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>