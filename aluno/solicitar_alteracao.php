<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');

if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['papel']) != 'aluno') {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Buscar dados do aluno
$query_aluno = "SELECT u.nome_completo, u.email, a.data_nascimento
                FROM usuarios u
                JOIN alunos a ON u.id_usuario = a.id_usuario
                WHERE u.id_usuario = ?";
$stmt_aluno = $conexao->prepare($query_aluno);
$stmt_aluno->bind_param("i", $id_usuario);
$stmt_aluno->execute();
$resultado_aluno = $stmt_aluno->get_result();
$aluno = $resultado_aluno->fetch_assoc();

$nome_completo = $aluno['nome_completo'];
$email = $aluno['email'];
$data_nascimento = $aluno['data_nascimento'];

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Aqui, em um sistema real, você enviaria um email para o administrador
    // ou salvaria a solicitação em uma tabela para aprovação.
    $mensagem = "<div class='alert alert-success'>Sua solicitação de alteração de dados foi enviada para análise.</div>";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Alteração de Dados</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Solicitar Alteração de Dados</h2>

        <?php echo $mensagem; ?>

        <form method="POST" class="mt-4">
            <div class="form-group">
                <label for="nome_completo">Nome Completo</label>
                <input type="text" id="nome_completo" name="nome_completo" class="form-control" value="<?php echo htmlspecialchars($nome_completo); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
            <a href="../dashboard.php" class="btn btn-secondary">Voltar</a>
        </form>

        <div class="alert alert-info mt-4">
            <strong>Atenção:</strong> Suas alterações serão enviadas para um administrador para aprovação. Elas não serão refletidas imediatamente no sistema.
        </div>
    </div>
</body>
</html>
