<?php
require_once 'restricao_acesso.php';
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
    <title>Editar Aluno - Administrador</title>
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
        <h2>Editar Aluno: <?php echo htmlspecialchars($nome_completo); ?></h2>
        
        <?php if (!empty($erro_geral)) { echo '<div class="alert">Erro: ' . $erro_geral . '</div>'; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_aluno; ?>" method="post">
            <input type="hidden" name="id_aluno" value="<?php echo $id_aluno; ?>">
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
                <label>Status do Aluno</label>
                <select name="ativo" class="form-control">
                    <option value="1" <?php echo ($ativo == 1) ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo ($ativo == 0) ? 'selected' : ''; ?>>Inativo</option>
                </select>
                <p style="font-size: 0.8em; color: #6c757d;">Se o aluno for inativado, ele não poderá mais fazer login.</p>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Salvar Alterações">
                <a href="gerenciar_alunos.php" class="btn" style="background-color: #6c757d;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
mysqli_close($link);
?>