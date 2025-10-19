<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');
include_once('restricao_acesso_professor.php');

if (!isset($_GET['id_disc_turma'])) {
    header("Location: gerenciar_turmas.php");
    exit();
}

$id_disc_turma = $_GET['id_disc_turma'];

// Salvar nova avaliação
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];
    $peso = $_POST['peso'];
    $data_avaliacao = $_POST['data_avaliacao'];

    $query_insert_avaliacao = "INSERT INTO avaliacoes (id_disc_turma, titulo, peso, data_avaliacao) VALUES (?, ?, ?, ?)";
    $stmt_insert_avaliacao = $conexao->prepare($query_insert_avaliacao);
    $stmt_insert_avaliacao->bind_param("isds", $id_disc_turma, $titulo, $peso, $data_avaliacao);
    $stmt_insert_avaliacao->execute();
    
    // Recarregar a página para limpar o POST e mostrar a nova avaliação
    header("Location: gerenciar_avaliacoes.php?id_disc_turma=" . $id_disc_turma);
    exit();
}

// Buscar avaliações existentes
$query_avaliacoes = "SELECT id_avaliacao, titulo, peso, data_avaliacao FROM avaliacoes WHERE id_disc_turma = ? ORDER BY data_avaliacao DESC";
$stmt_avaliacoes = $conexao->prepare($query_avaliacoes);
$stmt_avaliacoes->bind_param("i", $id_disc_turma);
$stmt_avaliacoes->execute();
$resultado_avaliacoes = $stmt_avaliacoes->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Avaliações</title>
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
        
        h1 { font-size: 1.8em; color: #0056b3; margin-bottom: 20px; }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
            margin-bottom: 30px;
        }

        .content-card h2 {
            font-size: 1.5em; color: #0056b3; margin-top: 0;
            margin-bottom: 20px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto; /* Título | Peso | Data | Botão */
            gap: 20px;
            align-items: flex-end;
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

        .btn {
            padding: 12px 25px; text-decoration: none; border-radius: 6px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            white-space: nowrap; /* Evita que o texto do botão quebre */
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }

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
        
        .no-data-message {
            background-color: #eef7ff; color: #0c5460; padding: 15px;
            border: 1px solid #bee5eb; border-radius: 8px; text-align: center;
            margin-top: 20px; font-size: 1.1em;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do Professor</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Minhas Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <h1>Gerenciar Avaliações</h1>

        <div class="content-card">
            <h2><i class="fa-solid fa-plus-circle"></i> Criar Nova Avaliação</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Título da Avaliação</label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ex: Prova 1" required>
                    </div>
                    <div class="form-group">
                        <label>Peso</label>
                        <input type="number" step="0.01" name="peso" class="form-control" placeholder="Ex: 1.0" required>
                    </div>
                    <div class="form-group">
                        <label>Data</label>
                        <input type="date" name="data_avaliacao" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Criar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2><i class="fa-solid fa-list-check"></i> Avaliações Criadas</h2>
            <?php if ($resultado_avaliacoes->num_rows > 0) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Peso</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($avaliacao = $resultado_avaliacoes->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($avaliacao['titulo']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($avaliacao['peso'], 2, ',', '.')); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($avaliacao['data_avaliacao']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else : ?>
                <div class="no-data-message">Nenhuma avaliação criada para esta turma/disciplina.</div>
            <?php endif; ?>
        </div>
        
        <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar para Turmas</a>
    </main>

</body>
</html>
<?php
$conexao->close();
?>