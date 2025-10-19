<?php
require_once 'restricao_acesso.php';
$link = require_once '../conexão.php';

$mensagem_sucesso = "";
$mensagem_erro = "";

if(isset($_GET['sucesso'])){
    $mensagem_sucesso = "Disciplina removida da turma com sucesso!";
}
if(isset($_GET['erro'])){
    $mensagem_erro = urldecode($_GET['erro']);
}


if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_turma = trim($_GET['id']);
$erro_geral = "";

// Lógica para adicionar disciplina e professor
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['id_disciplina']) && !empty($_POST['id_professor'])) {
        $id_disciplina = $_POST['id_disciplina'];
        $id_professor = $_POST['id_professor'];

        $sql_insert = "INSERT INTO disciplinas_turmas (id_turma, id_disciplina, id_professor) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "iii", $id_turma, $id_disciplina, $id_professor);
            if(mysqli_stmt_execute($stmt)){
                $mensagem_sucesso = "Disciplina associada com sucesso!";
            } else {
                $erro_geral = "Erro ao associar disciplina: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $erro_geral = "Por favor, selecione uma disciplina e um professor.";
    }
}

// Buscar informações da turma
$sql_turma = "SELECT nome_turma, id_curso FROM turmas WHERE id_turma = ?";
$nome_turma = "";
if($stmt_turma = mysqli_prepare($link, $sql_turma)){
    mysqli_stmt_bind_param($stmt_turma, "i", $id_turma);
    mysqli_stmt_execute($stmt_turma);
    $result_turma = mysqli_stmt_get_result($stmt_turma);
    $turma = mysqli_fetch_assoc($result_turma);
    $nome_turma = $turma['nome_turma'];
    $id_curso_turma = $turma['id_curso']; // Pega o id_curso da turma
    mysqli_stmt_close($stmt_turma);
}


// Buscar disciplinas disponíveis para o curso da turma
$sql_disciplinas = "SELECT d.id_disciplina, d.nome_disciplina FROM disciplinas d WHERE d.id_curso = ? AND d.id_disciplina NOT IN (SELECT dt.id_disciplina FROM disciplinas_turmas dt WHERE dt.id_turma = ?) ORDER BY d.nome_disciplina";
$result_disciplinas = null;
if($stmt_disciplinas = mysqli_prepare($link, $sql_disciplinas)){
    mysqli_stmt_bind_param($stmt_disciplinas, "ii", $id_curso_turma, $id_turma);
    mysqli_stmt_execute($stmt_disciplinas);
    $result_disciplinas = mysqli_stmt_get_result($stmt_disciplinas);
}

$sql_professores = "SELECT p.id_professor, u.nome_completo FROM professores p JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY u.nome_completo";
$result_professores = mysqli_query($link, $sql_professores);

// Buscar disciplinas e professores já associados à turma
$sql_associados = "SELECT dt.id_disc_turma, d.nome_disciplina, u.nome_completo AS nome_professor FROM disciplinas_turmas dt JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina JOIN professores p ON dt.id_professor = p.id_professor JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE dt.id_turma = ?";
$result_associados = null;
if($stmt_associados = mysqli_prepare($link, $sql_associados)){
    mysqli_stmt_bind_param($stmt_associados, "i", $id_turma);
    mysqli_stmt_execute($stmt_associados);
    $result_associados = mysqli_stmt_get_result($stmt_associados);
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Disciplinas da Turma</title>
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
        
        h1 { font-size: 1.8em; color: #0056b3; margin-bottom: 5px; }
        h2 { font-size: 1.5em; color: #555; margin-top: 0; margin-bottom: 20px; }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
            margin-bottom: 30px;
        }

        .content-card h3 {
            font-size: 1.5em; color: #0056b3; margin-top: 0;
            margin-bottom: 20px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h3 i { margin-right: 15px; color: #007bff; }
        
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 2fr auto;
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
            white-space: nowrap;
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

        .action-links a {
            margin-right: 10px; text-decoration: none; font-weight: 500;
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
        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-info {
            background-color: #d1ecf1; color: #0c5460; padding: 15px;
            border: 1px solid #bee5eb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-info a { color: #0c5460; font-weight: bold; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <h1>Gerenciar Disciplinas da Turma</h1>
        <h2><?php echo htmlspecialchars($nome_turma); ?></h2>

        <?php if(!empty($mensagem_sucesso)): ?>
            <div class="alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if(!empty($mensagem_erro)): ?>
            <div class="alert-danger"><?php echo $mensagem_erro; ?></div>
        <?php endif; ?>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>
        
        <?php if ($result_disciplinas && mysqli_num_rows($result_disciplinas) > 0): ?>
        <div class="content-card">
            <h3><i class="fa-solid fa-plus-circle"></i> Associar Nova Disciplina</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_turma; ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Disciplina</label>
                        <select name="id_disciplina" class="form-control">
                            <option value="">Selecione a Disciplina</option>
                            <?php 
                            if ($result_disciplinas && mysqli_num_rows($result_disciplinas) > 0) {
                                mysqli_data_seek($result_disciplinas, 0);
                                while($disciplina = mysqli_fetch_assoc($result_disciplinas)) : ?>
                                    <option value="<?php echo $disciplina['id_disciplina']; ?>"><?php echo $disciplina['nome_disciplina']; ?></option>
                                <?php endwhile; 
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Professor</label>
                        <select name="id_professor" class="form-control">
                            <option value="">Selecione o Professor</option>
                            <?php 
                            if ($result_professores && mysqli_num_rows($result_professores) > 0) {
                                mysqli_data_seek($result_professores, 0);
                                while($professor = mysqli_fetch_assoc($result_professores)) : ?>
                                <option value="<?php echo $professor['id_professor']; ?>"><?php echo $professor['nome_completo']; ?></option>
                            <?php endwhile; 
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-link"></i> Associar</button>
                    </div>
                </div>
            </form>
        </div>
        <?php else: ?>
            <div class="alert-info">
                Todas as disciplinas do curso já foram adicionadas a esta turma. Para cadastrar uma nova disciplina, <a href="cadastrar_disciplina.php">clique aqui</a>.
            </div>
        <?php endif; ?>

        <div class="content-card">
            <h3><i class="fa-solid fa-list-check"></i> Disciplinas e Professores Associados</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <th>Professor</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result_associados && mysqli_num_rows($result_associados) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result_associados)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                                <td><?php echo htmlspecialchars($row['nome_professor']); ?></td>
                                <td class="action-links">
                                    <a href="editar_disciplina_turma.php?id=<?php echo $row['id_disc_turma']; ?>" class="edit-link"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                    <a href="excluir_disciplina_turma.php?id=<?php echo $row['id_disc_turma']; ?>" class="delete-link" onclick="return confirm('Tem certeza que deseja remover esta disciplina da turma?')"><i class="fa-solid fa-trash-can"></i> Remover</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Nenhuma disciplina associada a esta turma.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </main>
</body>
</html>
<?php
mysqli_close($link);
?>