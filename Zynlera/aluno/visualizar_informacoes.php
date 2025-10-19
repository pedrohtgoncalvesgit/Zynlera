<?php
$conexao = include_once('../conexão.php');
include_once('../sessao.php');

if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['papel']) != 'aluno') {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Buscar id_aluno
$query_aluno = "SELECT id_aluno FROM alunos WHERE id_usuario = ?";
$stmt_aluno = $conexao->prepare($query_aluno);
$stmt_aluno->bind_param("i", $id_usuario);
$stmt_aluno->execute();
$resultado_aluno = $stmt_aluno->get_result();
$aluno = $resultado_aluno->fetch_assoc();
$id_aluno = $aluno['id_aluno'];

// Buscar notas
$query_notas = "SELECT d.nome_disciplina, av.titulo, n.valor
                FROM notas n
                JOIN avaliacoes av ON n.id_avaliacao = av.id_avaliacao
                JOIN disciplinas_turmas dt ON av.id_disc_turma = dt.id_disc_turma
                JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
                WHERE n.id_aluno = ?
                ORDER BY d.nome_disciplina, av.titulo";
$stmt_notas = $conexao->prepare($query_notas);
$stmt_notas->bind_param("i", $id_aluno);
$stmt_notas->execute();
$resultado_notas = $stmt_notas->get_result();

// Buscar faltas
$query_faltas = "SELECT d.nome_disciplina, a.data_aula, f.status
                 FROM frequencias f
                 JOIN aulas a ON f.id_aula = a.id_aula
                 JOIN disciplinas_turmas dt ON a.id_disc_turma = dt.id_disc_turma
                 JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
                 WHERE f.id_aluno = ? AND f.status = 'Falta'
                 ORDER BY d.nome_disciplina, a.data_aula";
$stmt_faltas = $conexao->prepare($query_faltas);
$stmt_faltas->bind_param("i", $id_aluno);
$stmt_faltas->execute();
$resultado_faltas = $stmt_faltas->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notas e Faltas</title>
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
        
        h1 {
            font-size: 1.8em; color: #0056b3; margin-bottom: 20px;
        }

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

        .status-falta {
            color: #dc3545; font-weight: 500;
        }

        .no-data-message {
            background-color: #eef7ff; color: #0c5460; padding: 15px;
            border: 1px solid #bee5eb; border-radius: 8px; text-align: center;
            margin-top: 20px; font-size: 1.1em;
        }
        
        .button-container {
             text-align: center; margin-top: 20px;
        }

        .btn-secondary {
            padding: 12px 25px; text-decoration: none; border-radius: 8px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            background-color: #6c757d; color: white;
        }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
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
        <h1>Minhas Notas e Faltas</h1>

        <div class="content-card">
            <h2><i class="fa-solid fa-star-half-stroke"></i> Notas</h2>
            <?php if ($resultado_notas->num_rows > 0) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <th>Avaliação</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($nota = $resultado_notas->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nota['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars($nota['titulo']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($nota['valor'], 1, ',', '.')); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else : ?>
                <div class="no-data-message">Nenhuma nota lançada até o momento.</div>
            <?php endif; ?>
        </div>

        <div class="content-card">
            <h2><i class="fa-solid fa-calendar-xmark"></i> Faltas</h2>
            <?php if ($resultado_faltas->num_rows > 0) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <th>Data da Aula</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($falta = $resultado_faltas->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($falta['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($falta['data_aula']))); ?></td>
                            <td><span class="status-falta"><?php echo htmlspecialchars($falta['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else : ?>
                <div class="no-data-message">Nenhuma falta registrada até o momento.</div>
            <?php endif; ?>
        </div>
        
        <div class="button-container">
             <a href="../dashboard.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard</a>
        </div>
    </main>

</body>
</html>
<?php
$conexao->close();
?>