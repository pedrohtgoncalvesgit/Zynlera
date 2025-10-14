<?php
// O CÓDIGO PHP CONTINUA INTACTO
require_once 'sessao.php';
require_once 'config.php';
require_once 'conexão.php';

if (is_logged_in()) {
    header("location: dashboard.php");
    exit;
}

$email = $senha = "";
$email_err = $senha_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (toda a sua lógica de validação continua aqui, intacta) ...
    if (empty(trim($_POST["email"]))) {
        $email_err = "Por favor, insira o email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    if (empty(trim($_POST["senha"]))) {
        $senha_err = "Por favor, insira a senha.";
    } else {
        $senha = trim($_POST["senha"]);
    }
    
    if (empty($email_err) && empty($senha_err)) {
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
                        if ($senha == $senha_db) { 
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_usuario"] = $id_usuario;
                            $_SESSION["nome_completo"] = $nome_completo;
                            $_SESSION["email"] = $email_db;
                            $_SESSION["papel"] = $papel;

                            header("location: dashboard.php");
                            exit;
                        } else {
                            $login_err = "Email ou senha inválidos.";
                        }
                    }
                } else {
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
// FIM DO BLOCO PHP
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Escolar Zynlera</title>
    <link rel="icon" type="image/png" sizes="32x32" href="imagens/favicon-32x32.png">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap');
        
        *{ margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans', Arial, Helvetica, sans-serif; }
        .main-login{ width: 100vw; height: 100vh; background-color: rgb(18, 18, 48); display: flex; justify-content: center; align-items: center; }
        .left-login{ width: 50vw; height: 100vh; display: flex; justify-content: center; align-items: center; flex-direction: column; padding: 20px; }
        .left-login > h1{ color: rgb(0, 255 , 255); margin-bottom: 20px; text-align: center; }
        .left-login img { max-width: 100%; max-height: 80%; object-fit: contain; display: block; margin-top: 20px; }
        .right-login{ width: 50vw; height: 100vh; display: flex; justify-content: center; align-items: center; }
        .card-login{ width: 60%; max-width: 400px; display: flex; justify-content: center; align-items: center; flex-direction: column; padding: 30px 35px; background-color: #2f2841; border-radius: 20px; box-shadow: 0px 10px 40px #00000056 ; }
        
        /* ================================================= */
        /* == CORREÇÃO DEFINITIVA FEITA AQUI (REMOVIDO O >) == */
        /* ================================================= */
        .card-login h1{
            color: rgb(0, 255 , 255);
            font-weight: 800;
            margin: 0;
            margin-bottom: 20px;
        }
        
        .text-field{ width: 100%; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; margin: 10px 0px; }
        .text-field > input{ width: 100%; border: none; border-radius: 10px; padding: 15px; background-color: #514869; color: rgb(183, 238, 238); font-size: 12pt; box-shadow: 0px 10px 40px #00000056; outline: none; box-sizing: border-box; }
        .text-field > label{ color: rgb(183, 238, 238); margin-bottom: 10px; }
        .text-field > input::placeholder{ color: #f0ffff94; }
        .btn-login{ width: 100%; padding: 16px 0px; margin: 25px 0px; border: none; border-radius: 8px; outline: none; text-transform: uppercase; font-weight: 800; letter-spacing: 3px; color: #2b134b; background-color: rgb(0, 255 , 255); cursor: pointer; box-shadow: 0px 10px 40px -12px rgb(2, 46, 46); }
        @media only screen and (max-width: 900px){ .card-login{ width: 85%; } }
        @media only screen and (max-width: 600px){ .main-login{ flex-direction: column; } .left-login > h1{ display: none; } .left-login{ width: 100vw; height: auto; } .right-login{ width: 100vw; height: auto; } .left-login img { max-height: 60vh; width: 80%; } .card-login{ width: 90%; } }
        .error-message { color: #ff7b7b; font-size: 0.8em; margin-top: 5px; width: 100%; text-align: left; }
        .login-alert { color: #ff7b7b; background-color: rgba(255, 0, 0, 0.1); border: 1px solid #ff7b7b; border-radius: 8px; padding: 10px; margin-bottom: 15px; width: 100%; box-sizing: border-box; text-align: center; }
    </style>
</head>
<body>
    <div class="main-login">
        <div class="left-login">
            <h1> Faça Seu Login</h1><br>
            <img src="imagens/college students-rafiki.svg" class="left-login-image" alt="Estudantes na escola">
        </div>
        <div class="right-login">
            <div class="card-login">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="width: 100%; display: flex; flex-direction: column; align-items: center;" novalidate>
                    <h1>Login</h1>
                    <?php 
                    if (!empty($login_err)) {
                        echo '<div class="login-alert">' . $login_err . '</div>';
                    } 
                    if (isset($_GET['expirada']) && $_GET['expirada'] == 'true') {
                        echo '<div class="login-alert">Sua sessão expirou por inatividade. Faça login novamente.</div>';
                    }
                    ?>
                    <div class="text-field">
                        <label for="email">Usuário (Email)</label>
                        <input type="text" name="email" id="email" placeholder="Digite seu email" value="<?php echo $email; ?>">
                        <span class="error-message"><?php echo $email_err; ?></span>
                    </div>
                    <div class="text-field">
                        <label for="senha">Senha</label>
                        <input type="password" name="senha" id="senha" placeholder="Digite sua senha">
                        <span class="error-message"><?php echo $senha_err; ?></span>
                    </div>
                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>