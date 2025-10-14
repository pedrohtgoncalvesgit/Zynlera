<?php
require_once 'restrição_acesso.php';
require_once '../conexão.php';

// Define variáveis e inicializa com valores vazios
$nome_completo = $email = $senha = $matricula = $data_nascimento = "";
$nome_completo_err = $email_err = $senha_err = $matricula_err = $data_nascimento_err = $erro_geral = "";

// ID de Papel para Aluno (deve ser o ID correspondente, vamos assumir 3)
$ID_PAPEL_ALUNO = 3; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validação dos Campos (Requisitos: Nome, Data Nasc., Matrícula) [cite: 38]
    if (empty(trim($_POST["nome_completo"]))) { $nome_completo_err = "Insira o nome completo."; } else { $nome_completo = trim($_POST["nome_completo"]); }
    if (empty(trim($_POST["email"]))) { $email_err = "Insira um email."; } else { $email = trim($_POST["email"]); }
    if (empty(trim($_POST["senha"]))) { $senha_err = "Insira uma senha."; } else { $senha = trim($_POST["senha"]); }
    if (empty(trim($_POST["matricula"]))) { $matricula_err = "Insira a matrícula."; } else { $matricula = trim($_POST["matricula"]); }
    if (empty(trim($_POST["data_nascimento"]))) { $data_nascimento_err = "Insira a data de nascimento."; } else { $data_nascimento = trim($_POST["data_nascimento"]); }

    if (empty($nome_completo_err) && empty($email_err) && empty($senha_err) && empty($matricula_err) && empty($data_nascimento_err)) {
        
        // Inicia a transação
        mysqli_begin_transaction($link);
        $sucesso = true;

        // Tabela 1: usuarios
        $sql_usuario = "INSERT INTO usuarios (id_papel, nome_completo, email, senha) VALUES (?, ?, ?, ?)";
        if ($stmt_usuario = mysqli_prepare($link, $sql_usuario)) {
            mysqli_stmt_bind_param($stmt_usuario, "isss", $param_papel, $param_nome, $param_email, $param_senha);
            $param_papel = $ID_PAPEL_ALUNO;
            $param_nome = $nome_completo;
            $param_email = $email;
            $param_senha = $senha; // Sem criptografia, conforme solicitado

            if (mysqli_stmt_execute($stmt_usuario)) {
                $id_usuario = mysqli_insert_id($link); // Obtém o ID_USUARIO inserido
                mysqli_stmt_close($stmt_usuario);

                // Tabela 2: alunos
                $sql_aluno = "INSERT INTO alunos (id_usuario, matricula, data_nascimento) VALUES (?, ?, ?)";
                if ($stmt_aluno = mysqli_prepare($link, $sql_aluno)) {
                    mysqli_stmt_bind_param($stmt_aluno, "iss", $param_id_usuario, $param_matricula, $param_data_nascimento);
                    $param_id_usuario = $id_usuario;
                    $param_matricula = $matricula;
                    $param_data_nascimento = $data_nascimento;

                    if (!mysqli_stmt_execute($stmt_aluno)) {
                        $erro_geral = "Erro ao inserir na tabela alunos: " . mysqli_error($link);
                        $sucesso = false;
                    }
                    mysqli_stmt_close($stmt_aluno);
                } else {
                    $erro_geral = "Erro de preparação (alunos): " . mysqli_error($link);
                    $sucesso = false;
                }
            } else {
                // Erro de inserção em usuários (ex: email duplicado)
                $erro_geral = "Erro ao inserir usuário: " . mysqli_error($link);
                $sucesso = false;
            }
        } else {
            $erro_geral = "Erro de preparação (usuários): " . mysqli_error($link);
            $sucesso = false;
        }

        // Finaliza a transação
        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_alunos.php?sucesso=cadastro");
            exit;
        } else {
            mysqli_rollback($link);
            // Se houve erro na segunda tabela (alunos), talvez queira desfazer a primeira
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
    <title>Cadastrar Aluno - Administrador</title>
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
        <h2>Cadastrar Novo Aluno</h2>
<p>Preencha os dados do novo aluno (Dados obrigatórios: nome completo, data de nascimento, matrícula)</p>

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
                <label>Matrícula</label>
                <input type="text" name="matricula" class="form-control" value="<?php echo htmlspecialchars($matricula); ?>">
                <span class="alert"><?php echo $matricula_err; ?></span>
            </div>
            <div class="form-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>">
                <span class="alert"><?php echo $data_nascimento_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Cadastrar Aluno">
                <a href="gerenciar_alunos.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>