<?php
// Inclui a restrição de acesso e a conexão com o banco
require_once 'restricao_acesso.php';
$link = require_once '../conexão.php';

// Consulta SQL para buscar todas as disciplinas, juntando com o nome do curso
$sql = "SELECT 
            d.id_disciplina, 
            d.nome_disciplina, 
            d.codigo_disciplina, 
            d.carga_horaria, 
            c.nome_curso
        FROM disciplinas d
        INNER JOIN cursos c ON d.id_curso = c.id_curso
        ORDER BY c.nome_curso ASC, d.nome_disciplina ASC";

$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disciplinas - Administrador</title>
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
            flex: 1; padding: 30px; max-width: 1200px;
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .card-header-flex h2 {
            font-size: 1.8em; color: #0056b3; margin: 0;
            display: flex; align-items: center;
        }
        .card-header-flex h2 i { margin-right: 15px; color: #007bff; }

        .btn-add {
            display: inline-flex; align-items: center; padding: 10px 20px; /* Ajuste padding */
            background-color: #28a745; color: white; text-decoration: none;
            border-radius: 8px; font-weight: 500; 
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-add i { margin-right: 10px; }
        .btn-add:hover { background-color: #218838; transform: translateY(-2px); }

        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-radius: 8px;
            overflow: hidden;
        }
        .data-table th, .data-table td {
            border: 1px solid #e0e0e0; padding: 12px 15px; text-align: left;
        }
        .data-table thead th {
            background-color: #f0f8ff; color: #333; font-weight: 600;
            text-transform: uppercase; font-size: 0.9em;
        }
        .data-table tbody tr:nth-child(even) { background-color: #fdfdfd; }
        .data-table tbody tr:hover { background-color: #eef7ff; }

        .action-links a {
            margin-right: 15px; text-decoration: none; font-weight: 500;
            transition: color 0.2s; display: inline-flex; align-items: center; gap: 5px;
        }
        .action-links .edit-link { color: #007bff; }
        .action-links .edit-link:hover { color: #0056b3; }
        .action-links .delete-link { color: #dc3545; }
        .action-links .delete-link:hover { color: #c82333; }

        .alert-danger {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }
         .no-data-message {
             background-color: #eef7ff; color: #0c5460; padding: 15px;
             border: 1px solid #bee5eb; border-radius: 8px; text-align: center;
             margin-top: 20px; font-size: 1.1em;
         }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_disciplinas.php"><i class="fa-solid fa-book"></i> Disciplinas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <div class="card-header-flex">
                 <h2><i class="fa-solid fa-book-open-reader"></i> Gerenciamento de Disciplinas</h2>
                 <a href="cadastrar_disciplina.php" class="btn-add">
                     <i class="fa-solid fa-plus"></i> Adicionar Disciplina
                 </a>
            </div>

            <?php if (isset($_GET['erro']) && $_GET['erro'] == 'dependencia'): ?>
                <div class="alert-danger">Erro: Não foi possível excluir a disciplina. Ela possui vínculos com Turmas/Professores.</div>
            <?php endif; ?>
            
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Disciplina</th>
                            <th>Código</th>
                            <th>Carga (h)</th>
                            <th>Curso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id_disciplina']; ?></td>
                                <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                                <td><?php echo htmlspecialchars($row['codigo_disciplina']); ?></td>
                                <td><?php echo $row['carga_horaria']; ?></td>
                                <td><?php echo htmlspecialchars($row['nome_curso']); ?></td>
                                <td class="action-links">
                                    <a href="editar_disciplina.php?id=<?php echo $row['id_disciplina']; ?>" class="edit-link"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                    <a href="excluir_disciplina.php?id=<?php echo $row['id_disciplina']; ?>" onclick="return confirm('ATENÇÃO: A exclusão só é permitida se não houver vínculo em nenhuma turma.')" class="delete-link"><i class="fa-solid fa-trash-can"></i> Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data-message">Nenhuma disciplina cadastrada no momento.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
<?php mysqli_close($link); ?>