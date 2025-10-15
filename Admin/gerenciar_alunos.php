<?php
// Inclui a restrição de acesso e a conexão com o banco
require_once 'restrição_acesso.php';
require_once '../conexão.php'; // Adicione o 'ç'

// Consulta SQL para buscar todos os alunos (incluindo dados do usuário)
// Acessamos 'nome_completo' e 'email' de 'usuarios' e 'matricula' de 'alunos'
$sql = "SELECT 
    a.id_aluno,
    a.matricula,
    u.nome_completo,
    u.email,
    u.ativo,        
    u.id_usuario
FROM alunos a
INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
ORDER BY u.nome_completo ASC";
$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - Administrador</title>
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
            max-width: 1200px;
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
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .content-card h2 i {
            margin-right: 15px;
            color: #007bff;
        }

        /* --- Botão Adicionar --- */
        .btn-add {
            display: inline-flex; /* Para alinhar ícone e texto */
            align-items: center;
            padding: 12px 25px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 30px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-add i {
            margin-right: 10px;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* --- Tabela --- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden; /* Garante que a borda arredondada seja aplicada */
        }

        .data-table th, .data-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }

        .data-table thead th {
            background-color: #f0f8ff; /* Um azul bem claro */
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }

        .data-table tbody tr:hover {
            background-color: #eef7ff;
        }

        /* --- Ações na Tabela --- */
        .action-links a {
            margin-right: 15px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .action-links .edit-link {
            color: #007bff; /* Azul primário */
        }
        .action-links .edit-link:hover {
            color: #0056b3;
        }

        .action-links .deactivate-link {
            color: #dc3545; /* Vermelho para inativar */
        }
        .action-links .deactivate-link:hover {
            color: #c82333;
        }

        /* --- Status --- */
        .status-active {
            color: #28a745;
            font-weight: 500;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: 500;
        }

        /* --- Mensagem de Sem Dados --- */
        .no-data-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
            font-size: 1.1em;
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
            <h2><i class="fa-solid fa-user-graduate"></i> Gerenciamento de Alunos</h2>
            <a href="cadastrar_alunos.php" class="btn-add">
                <i class="fa-solid fa-user-plus"></i> Adicionar Novo Aluno
            </a>
            
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Matrícula</th>
                            <th>Nome Completo</th>
                            <th>Email de Login</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id_aluno']; ?></td>
                                <td><?php echo htmlspecialchars($row['matricula']); ?></td>
                                <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php if ($row['ativo']): ?>
                                        <span class="status-active">Ativo</span>
                                    <?php else: ?>
                                        <span class="status-inactive">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-links">
                                    <a href="editar_alunos.php?id=<?php echo $row['id_aluno']; ?>" class="edit-link"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                    <a href="excluir_aluno.php?id=<?php echo $row['id_aluno']; ?>" onclick="return confirm('Tem certeza que deseja tentar inativar este aluno?')" class="deactivate-link"><i class="fa-solid fa-user-slash"></i> Inativar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data-message">Nenhum aluno cadastrado no momento.</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>

<?php
mysqli_close($link);
?>