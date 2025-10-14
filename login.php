<?php
require_once 'sessao.php';
require_once 'config.php';
require_once 'conexão.php';

// Se o usuário já estiver logado, redireciona para o dashboard
if (is_logged_in()) {
    header("location: dashboard.php");
    exit;
}

$email = $senha = "";
$email_err = $senha_err = $login_err = "";

// Processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Validação do Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Por favor, insira o email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // 2. Validação da Senha
    if (empty(trim($_POST["senha"]))) {
        $senha_err = "Por favor, insira a senha.";
    } else {
        $senha = trim($_POST["senha"]);
    }
    
    // 3. Validação das Credenciais
    if (empty($email_err) && empty($senha_err)) {
        // CORREÇÃO: Incluindo 'AND u.ativo = 1' para bloquear usuários inativos
        $sql = "SELECT u.id_usuario, u.nome_completo, u.email, u.senha, p.nome_papel 
                FROM usuarios u
                INNER JOIN papeis p ON u.id_papel = p.id_papel
                WHERE u.email = ? AND u.ativo = 1"; 
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id_usuario, $nome_completo, $email_db, $senha_db, $papel);
                    if (mysqli_stmt_fetch($stmt)) {
                        // Sem criptografia, comparamos as strings
                        if ($senha == $senha_db) { 
                            // Senha correta, inicia a sessão
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_usuario"] = $id_usuario;
                            $_SESSION["nome_completo"] = $nome_completo;
                            $_SESSION["email"] = $email_db;
                            $_SESSION["papel"] = $papel; // 'Administrador', 'Professor', 'Aluno'

                            // Redireciona para a página principal (dashboard)
                            header("location: dashboard.php");
                            exit;
                        } else {
                            $login_err = "Email ou senha inválidos.";
                        }
                    }
                } else {
                    // Retorna 'Email ou senha inválidos' para usuários não encontrados OU INATIVOS
                    $login_err = "Email ou senha inválidos.";
                }
            } else {
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
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
    <title>Login - Sistema Escolar Zynlera</title>
    <style>
        /* Estilos básicos para o formulário de login */
        body { font: 14px sans-serif; }
        .wrapper { width: 360px; padding: 20px; margin: 50px auto; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .alert { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Por favor, preencha suas credenciais para acessar o sistema.</p>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="alert">' . $login_err . '</div>';
        } 
        if (isset($_GET['expirada']) && $_GET['expirada'] == 'true') {
            echo '<div class="alert">Sua sessão expirou por inatividade. Faça login novamente.</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="text" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="alert"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control">
                <span class="alert"><?php echo $senha_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Entrar">
            </div>
        </form>
    </div>
</body>
</html>