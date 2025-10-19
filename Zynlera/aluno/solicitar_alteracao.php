<?php
// ATENÇÃO: Corrigi a inclusão para usar a mesma variável $conexao em todo o código.
$conexao = require_once('../conexão.php'); 
require_once('../sessao.php'); // Alterei para require_once para consistência

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
    // A única alteração foi na classe da div para combinar com o novo estilo.
    $mensagem = "<div class='alert-success'>Sua solicitação de alteração de dados foi enviada para análise.</div>";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Alteração de Dados</title>
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
            flex: 1; padding: 30px; max-width: 800px;
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
        }

        .content-card h2 {
            font-size: 1.8em; color: #0056b3; margin-top: 0;
            margin-bottom: 30px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }
        
        .form-group { margin-bottom: 20px; }
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

        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
        }

        .alert-info {
            background-color: #d1ecf1; color: #0c5460; padding: 15px;
            border: 1px solid #bee5eb; border-radius: 8px; margin-top: 30px;
            display: flex; align-items: center; gap: 10px;
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
        <div class="logo">Área do Aluno</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="dados_pessoais.php"><i class="fa-solid fa-user"></i> Meus Dados</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-user-pen"></i> Solicitar Alteração de Dados</h2>

            <?php echo $mensagem; ?>

            <form method="POST">
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
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Enviar Solicitação</button>
                    <a href="../dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
                </div>
            </form>

            <div class="alert-info mt-4">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Atenção:</strong> Suas alterações serão enviadas para um administrador para aprovação. Elas não serão refletidas imediatamente no sistema.
                </div>
            </div>
        </div>
    </main>

</body>
</html>
<?php
$conexao->close();
?>