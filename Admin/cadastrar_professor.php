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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Professor - Administrador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif; margin: 0; background-color: #f4f7fa;
            color: #333; display: flex; flex-direction: column; min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(90deg, #0056b3, #007bff); color: white;
            padding: 10px 30px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo { font-size: 1.5em; font-weight: 700; }
        
        .main-nav a {
            color: white; text-decoration: none; margin-left: 20px;
            font-weight: 500; opacity: 0.9; transition: opacity 0.3s;
        }
        .main-nav a:hover { opacity: 1; }

        .container {
            flex: 1; padding: 30px; max-width: 900px;
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
        }

        .content-card h2 {
            font-size: 1.8em; color: #0056b3; margin-top: 0;
            margin-bottom: 10px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }
        .content-card p { margin-top: 0; margin-bottom: 30px; color: #666; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group { margin-bottom: 10px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: #555;
        }
        .form-control {
            width: 100%; padding: 12px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
        }

        .form-error {
            color: #dc3545; font-size: 0.85em; display: block; margin-top: 5px;
        }
        
        .alert-geral {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }

        .button-group {
            margin-top: 30px; display: flex; gap: 15px; border-top: 2px solid #eee;
            padding-top: 25px;
        }

        .btn {
            padding: 12px 25px; text-decoration: none; border-radius: 8px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_professores.php"><i class="fa-solid fa-chalkboard-user"></i> Professores</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-user-plus"></i> Cadastrar Novo Professor</h2>
            <p>Preencha os dados abaixo para criar um novo perfil de professor.</p>

            <?php if (!empty($erro_geral)) { echo '<div class="alert-geral">' . $erro_geral . '</div>'; } ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome_completo" class="form-control" value="<?php echo htmlspecialchars($nome_completo); ?>">
                        <span class="form-error"><?php echo $nome_completo_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Email (será usado para login)</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                        <span class="form-error"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Registro Funcional</label>
                        <input type="text" name="registro_funcional" class="form-control" value="<?php echo htmlspecialchars($registro_funcional); ?>">
                        <span class="form-error"><?php echo $registro_funcional_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Data de Admissão</label>
                        <input type="date" name="data_admissao" class="form-control" value="<?php echo htmlspecialchars($data_admissao); ?>">
                        <span class="form-error"><?php echo $data_admissao_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Senha Provisória</label>
                        <input type="text" name="senha" class="form-control" value="<?php echo htmlspecialchars($senha); ?>">
                        <span class="form-error"><?php echo $senha_err; ?></span>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Cadastrar Professor</button>
                    <a href="gerenciar_professores.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>