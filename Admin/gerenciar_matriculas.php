<?php
// gerenciar_matriculas.php (Gerenciar Alunos/Matrículas em uma Turma)
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// 1. Validar e Obter ID da Turma
if (!isset($_GET['id_turma']) || empty(trim($_GET['id_turma']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_turma = trim($_GET['id_turma']);

// Variáveis para exibição no cabeçalho
$nome_turma_atual = $nome_curso_atual = $ano_semestre_atual = "";
$mensagem_sucesso = ""; // Unificado, pode vir do GET ou do POST
$mensagem_erro = ""; // Apenas do POST

// Tratar mensagens via GET
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 1) {
        $mensagem_sucesso = "Aluno matriculado com sucesso!";
    } elseif ($_GET['sucesso'] == 2) {
        $mensagem_sucesso = "Aluno rematriculado com sucesso!";
    }
}


// 2. Consulta de Dados da Turma (Para o Cabeçalho da Página)
$sql_turma = "SELECT t.nome_turma, t.ano, t.semestre, c.nome_curso 
              FROM turmas t
              INNER JOIN cursos c ON t.id_curso = c.id_curso
              WHERE t.id_turma = ?";

if ($stmt_turma = mysqli_prepare($link, $sql_turma)) {
    mysqli_stmt_bind_param($stmt_turma, "i", $id_turma);
    if (mysqli_stmt_execute($stmt_turma)) {
        $result_turma = mysqli_stmt_get_result($stmt_turma);
        if (mysqli_num_rows($result_turma) == 1) {
            $turma = mysqli_fetch_assoc($result_turma);
            $nome_turma_atual = $turma['nome_turma'];
            $nome_curso_atual = $turma['nome_curso'];
            $ano_semestre_atual = $turma['ano'] . '/' . $turma['semestre'];
        }
    }
    mysqli_stmt_close($stmt_turma);
}

// 3. Lógica de processamento do formulário de MATRÍCULA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'matricular') {
    if (!empty($_POST['id_aluno'])) {
        $id_aluno = trim($_POST['id_aluno']);

        // Verificar se já existe uma matrícula cancelada
        $sql_check = "SELECT id_matricula FROM matriculas WHERE id_turma = ? AND id_aluno = ? AND situacao = 'cancelada'";
        if ($stmt_check = mysqli_prepare($link, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ii", $id_turma, $id_aluno);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            
            if (mysqli_num_rows($result_check) == 1) {
                // Rematrícula: Atualiza a situação para 'ativa'
                $matricula_reativar = mysqli_fetch_assoc($result_check);
                $id_matricula_reativar = $matricula_reativar['id_matricula'];
                $sql_update = "UPDATE matriculas SET situacao = 'ativa' WHERE id_matricula = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "i", $id_matricula_reativar);
                    if (mysqli_stmt_execute($stmt_update)) {
                        header("location: gerenciar_matriculas.php?id_turma=" . $id_turma . "&sucesso=2"); // Sucesso = 2 para rematrícula
                        exit();
                    } else {
                         $mensagem_erro = "Erro ao reativar matrícula.";
                    }
                    mysqli_stmt_close($stmt_update);
                }
            } else {
                // Matrícula nova: Insere um novo registro
                $sql_insert = "INSERT INTO matriculas (id_turma, id_aluno, situacao) VALUES (?, ?, 'ativa')";
                if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
                    mysqli_stmt_bind_param($stmt_insert, "ii", $id_turma, $id_aluno);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        header("location: gerenciar_matriculas.php?id_turma=" . $id_turma . "&sucesso=1");
                        exit();
                    } else {
                        $mensagem_erro = "Erro ao matricular o aluno. O aluno já pode estar ativo nesta ou em outra turma no mesmo período.";
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                     $mensagem_erro = "Erro ao preparar inserção.";
                }
            }
            mysqli_stmt_close($stmt_check);
        } else {
             $mensagem_erro = "Erro ao verificar matrícula existente.";
        }
    } else {
        $mensagem_erro = "Por favor, selecione um aluno.";
    }
}

// 4. Consultas para o Formulário (Alunos disponíveis para matrícula)
$sql_alunos_disponiveis = "
    SELECT 
        a.id_aluno, 
        a.matricula, 
        u.nome_completo 
    FROM alunos a
    INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
    WHERE u.ativo = 1 AND a.id_aluno NOT IN (
        SELECT id_aluno FROM matriculas WHERE id_turma = ? AND situacao = 'ativa'
    )
    ORDER BY u.nome_completo";
    
$alunos_disponiveis_result = null;
if ($stmt_disp = mysqli_prepare($link, $sql_alunos_disponiveis)) {
    mysqli_stmt_bind_param($stmt_disp, "i", $id_turma);
    if (mysqli_stmt_execute($stmt_disp)) {
        $alunos_disponiveis_result = mysqli_stmt_get_result($stmt_disp);
    } else {
        $mensagem_erro .= " Erro ao buscar alunos disponíveis."; // Adiciona ao erro existente
    }
    mysqli_stmt_close($stmt_disp);
} else {
    $mensagem_erro .= " Erro ao preparar busca de alunos disponíveis.";
}


// 5. Consulta de Alunos Matriculados (Lista de Vínculos)
$sql_alunos_matriculados = "
    SELECT 
        m.id_matricula, 
        a.id_aluno,         
        a.matricula, 
        u.nome_completo, 
        u.email 
    FROM matriculas m
    INNER JOIN alunos a ON m.id_aluno = a.id_aluno
    INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
    WHERE m.id_turma = ? AND m.situacao = 'ativa'
    ORDER BY u.nome_completo";

$matriculas_result = null;
if ($stmt_mat = mysqli_prepare($link, $sql_alunos_matriculados)) {
    mysqli_stmt_bind_param($stmt_mat, "i", $id_turma);
    if (mysqli_stmt_execute($stmt_mat)) {
        $matriculas_result = mysqli_stmt_get_result($stmt_mat);
    } else {
         $mensagem_erro .= " Erro ao buscar alunos matriculados.";
    }
    mysqli_stmt_close($stmt_mat);
} else {
    $mensagem_erro .= " Erro ao preparar busca de alunos matriculados.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Matrículas - <?php echo htmlspecialchars($nome_turma_atual); ?></title>
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
        
        .turma-info-box {
             background-color: #e2f3ff; border-left: 5px solid #007bff;
             padding: 15px 20px; margin-bottom: 30px; border-radius: 5px;
        }
        .turma-info-box h3 { margin-top: 0; color: #0056b3; font-size: 1.3em;}
        .turma-info-box p { margin-bottom: 0; color: #333; }

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

        .form-grid-matricula {
            display: grid;
            grid-template-columns: 1fr auto; /* Select | Botão */
            gap: 20px;
            align-items: flex-end; /* Alinha o botão com a base do select */
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
         .btn-sm { font-size: 0.8em; padding: 6px 12px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
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
            display: inline-flex; align-items: center; gap: 5px;
        }

        .alert-danger {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
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
        <div class="logo">Sistema Escolar</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <h1>Gerenciar Matrículas</h1>

        <div class="turma-info-box">
             <h3>Turma: <?php echo htmlspecialchars($nome_turma_atual); ?> (<?php echo htmlspecialchars($nome_curso_atual); ?>)</h3>
             <p>Ano/Semestre: <?php echo htmlspecialchars($ano_semestre_atual); ?></p>
        </div>

         <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert-success"><?php echo $mensagem_sucesso; ?></div>
         <?php endif; ?>
         <?php if (!empty($mensagem_erro)): ?>
            <div class="alert-danger"><?php echo $mensagem_erro; ?></div>
         <?php endif; ?>

        <div class="content-card">
            <h2><i class="fa-solid fa-user-plus"></i> Matricular Novo Aluno</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id_turma=<?php echo $id_turma; ?>" method="post">
                <input type="hidden" name="acao" value="matricular">
                <div class="form-grid-matricula">
                    <div class="form-group">
                        <label>Selecione o aluno</label>
                        <select name="id_aluno" class="form-control">
                            <option value="">Selecione um aluno disponível...</option>
                            <?php if ($alunos_disponiveis_result && mysqli_num_rows($alunos_disponiveis_result) > 0): ?>
                                <?php while($aluno = mysqli_fetch_assoc($alunos_disponiveis_result)): ?>
                                    <option value="<?php echo $aluno['id_aluno']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome_completo']); ?> (Matrícula: <?php echo htmlspecialchars($aluno['matricula']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Nenhum aluno ativo disponível para matrícula.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Matricular</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2><i class="fa-solid fa-users"></i> Alunos Matriculados (<?php echo $matriculas_result ? mysqli_num_rows($matriculas_result) : 0; ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID Matrícula</th>
                        <th>Matrícula Aluno</th>
                        <th>Nome Completo</th>
                        <th>E-mail</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (isset($matriculas_result) && $matriculas_result && mysqli_num_rows($matriculas_result) > 0): 
                        while($row = mysqli_fetch_assoc($matriculas_result)): 
                    ?>
                        <tr>
                            <td><?php echo $row['id_matricula']; ?></td>
                            <td><?php echo htmlspecialchars($row['matricula']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td> 
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="action-links">
                                <a href="desmatricular_aluno.php?id=<?php echo $row['id_matricula']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja desmatricular este aluno? A matrícula será marcada como \'cancelada\' e poderá ser reativada.')"
                                   class="btn btn-danger btn-sm"><i class="fa-solid fa-user-minus"></i> Desmatricular</a>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Nenhum aluno matriculado nesta turma.</td>
                        </tr>
                    <?php
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
         <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar para Turmas</a>
    </main>
        
</body>
</html>
<?php mysqli_close($link); ?>