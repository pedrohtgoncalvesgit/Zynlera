<?php
require_once 'restrição_acesso.php';
require_once '../conexão.php'; // Adicione o 'ç'

// Variáveis para armazenar os dados atuais e erros
$id_aluno = $id_usuario = $nome_completo = $email = $matricula = $data_nascimento = $ativo = "";
$nome_completo_err = $email_err = $matricula_err = $data_nascimento_err = $ativo_err = $erro_geral = "";

// 1. Processamento da requisição GET (Carregar dados)
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_aluno_param = trim($_GET["id"]);

    // Busca os dados do Aluno e do Usuário
$sql = "SELECT 
    a.id_aluno,
    a.matricula, 
    a.data_nascimento,
    u.id_usuario,
    u.nome_completo,
    u.email,
    u.ativo
FROM alunos a 
INNER JOIN usuarios u ON a.id_usuario = u.id_usuario 
WHERE a.id_aluno = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_aluno);
        $param_id_aluno = $id_aluno_param;

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                // Dados encontrados, preenche as variáveis
                $row = mysqli_fetch_assoc($result);
                $id_aluno = $row["id_aluno"];
                $id_usuario = $row["id_usuario"];
                $nome_completo = $row["nome_completo"];
                $email = $row["email"];
                $matricula = $row["matricula"];
                $data_nascimento = $row["data_nascimento"];
                $ativo = $row["ativo"];
            } else {
                // URL não contém ID válido.
                header("location: gerenciar_alunos.php?erro=nao_encontrado");
                exit();
            }
        } else {
            $erro_geral = "Erro ao buscar dados: " . mysqli_error($link);
        }
    }
    mysqli_stmt_close($stmt);
    
} else {
    // ID não fornecido na URL
    header("location: gerenciar_alunos.php?erro=id_faltando");
    exit();
}


// 2. Processamento da requisição POST (Atualizar dados)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Captura os IDs que vieram do campo oculto (hidden)
    $id_aluno = $_POST["id_aluno"];
    $id_usuario = $_POST["id_usuario"];
    
    // Validação dos dados (similar ao cadastro)
    if (empty(trim($_POST["nome_completo"]))) { $nome_completo_err = "Insira o nome completo."; } else { $nome_completo = trim($_POST["nome_completo"]); }
    if (empty(trim($_POST["email"]))) { $email_err = "Insira um email."; } else { $email = trim($_POST["email"]); }
    if (empty(trim($_POST["matricula"]))) { $matricula_err = "Insira a matrícula."; } else { $matricula = trim($_POST["matricula"]); }
    if (empty(trim($_POST["data_nascimento"]))) { $data_nascimento_err = "Insira a data de nascimento."; } else { $data_nascimento = trim($_POST["data_nascimento"]); }
    $ativo = (isset($_POST["ativo"]) && $_POST["ativo"] == 0) ? 0 : 1;


    if (empty($nome_completo_err) && empty($email_err) && empty($matricula_err) && empty($data_nascimento_err)) {
        
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

        // Atualiza a tabela alunos
        if ($sucesso) {
            $sql_aluno = "UPDATE alunos SET 
                            matricula = ?, 
                            data_nascimento = ?  /* CORREÇÃO AQUI */
                        WHERE id_aluno = ?";;
            if ($stmt_aluno = mysqli_prepare($link, $sql_aluno)) {
               // Linha 107 - CORRIGIDO
        mysqli_stmt_bind_param($stmt_aluno, 'ssi', $matricula, $data_nascimento, $id_aluno);
                $param_matricula = $matricula;
                $param_data_nascimento = $data_nascimento;
                $param_ativo_aluno = $ativo;
                $param_id_aluno = $id_aluno;

                if (!mysqli_stmt_execute($stmt_aluno)) {
                    $erro_geral .= " Erro ao atualizar os dados do aluno: " . mysqli_error($link);
                    $sucesso = false;
                }
                mysqli_stmt_close($stmt_aluno);
            } else {
                $erro_geral .= " Erro de preparação (alunos): " . mysqli_error($link);
                $sucesso = false;
            }
        }

        // Finaliza a transação
        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_alunos.php?sucesso=edicao");
            exit;
        } else {
            mysqli_rollback($link);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno - Administrador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Estilos Gerais --- */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f4f7fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- Cabeçalho Superior (simulando menu_admin.php) --- */
        .main-header {
            background: linear-gradient(90deg, #0056b3, #007bff);
            color: white;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.5em;
            font-weight: 700;
        }
        
        .main-nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .main-nav a:hover {
            opacity: 1;
        }

        /* --- Conteúdo Principal --- */
        .container {
            flex: 1;
            padding: 30px;
            max-width: 700px; /* Largura ajustada para formulário */
            margin: 20px auto;
            width: 100%;
            box-sizing: border-box;
        }

        .content-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: left;
        }

        .content-card h2 {
            font-size: 1.8em;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 10px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .content-card h2 i {
            margin-right: 15px;
            color: #007bff;
        }

        .content-card p.info { /* Adicionado classe para o parágrafo de info */
            font-size: 0.9em;
            color: #6c757d;
            margin-top: -10px;
            margin-bottom: 25px;
        }

        /* --- Formulário --- */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 1.05em;
        }

        .form-control {
            width: calc(100% - 24px); /* Ajuste para padding */
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            -webkit-appearance: none; /* Remove estilo padrão do navegador para selects */
            -moz-appearance: none;
            appearance: none;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        /* Estilo para selects */
        select.form-control {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007bff%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-5.4H18.2c-4.9%200-9.4%201.8-13.2%205.4A17.6%2017.6%200%200%01-5.4%2082.5a17.6%2017.6%200%200%015.4%2013.2l132.8%20132.8c4%204%209.2%206.2%2014.1%206.2s10.1-2.2%2014.1-6.2l132.8-132.8c4-4%205.4-9.2%205.4-13.2s-1.4-9.2-5.4-13.2z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 30px; /* Espaço para o ícone */
        }

        /* --- Botões de Ação --- */
        .form-actions {
            display: flex;
            justify-content: flex-start; /* Alinha à esquerda */
            gap: 15px; /* Espaçamento entre botões */
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            border: none; /* Remover borda padrão de button */
            display: inline-flex; /* Para ícones */
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #007bff; /* Azul para Salvar */
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        }

        .btn-secondary {
            background-color: #6c757d; /* Cinza para Cancelar */
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
        }

        /* --- Mensagens de Erro/Alerta --- */
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
        }
        .alert-error i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .error-message {
            color: #dc3545; /* Vermelho para erros de campo */
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Zynlera</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_alunos.php"><i class="fa-solid fa-users"></i> Alunos</a>
            <a href="gerenciar_professores.php"><i class="fa-solid fa-chalkboard-user"></i> Professores</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-user-pen"></i> Editar Aluno: <?php echo htmlspecialchars($nome_completo); ?></h2>
            <p class="info">Atualize os dados do aluno e seu status de acesso ao sistema.</p>
            
            <?php if (!empty($erro_geral)): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $erro_geral; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_aluno; ?>" method="post">
                <input type="hidden" name="id_aluno" value="<?php echo $id_aluno; ?>">
                <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

                <div class="form-group">
                    <label for="nome_completo">Nome Completo</label>
                    <input type="text" name="nome_completo" id="nome_completo" class="form-control" value="<?php echo htmlspecialchars($nome_completo); ?>" required>
                    <span class="error-message"><?php echo $nome_completo_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="email">Email (Login)</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    <span class="error-message"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label for="matricula">Matrícula</label>
                    <input type="text" name="matricula" id="matricula" class="form-control" value="<?php echo htmlspecialchars($matricula); ?>" required>
                    <span class="error-message"><?php echo $matricula_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" id="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
                    <span class="error-message"><?php echo $data_nascimento_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="ativo">Status do Aluno</label>
                    <select name="ativo" id="ativo" class="form-control">
                        <option value="1" <?php echo ($ativo == 1) ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo ($ativo == 0) ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                    <p class="info" style="font-size: 0.85em; margin-top: 10px;">Se o aluno for inativado, ele não poderá mais fazer login.</p>
                    <span class="error-message"><?php echo $ativo_err; ?></span>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar Alterações</button>
                    <a href="gerenciar_alunos.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
<?php
mysqli_close($link);
?>