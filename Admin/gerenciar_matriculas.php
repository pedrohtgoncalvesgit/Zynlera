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
$mensagem_sucesso = $mensagem_erro = "";

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
                    }
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
                        $mensagem_erro = "Erro ao matricular o aluno. O aluno já pode estar ativo na turma.";
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
            mysqli_stmt_close($stmt_check);
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
    }
    mysqli_stmt_close($stmt_disp);
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
    }
    mysqli_stmt_close($stmt_mat);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Matrículas - <?php echo htmlspecialchars($nome_turma_atual); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

    <?php include 'menu_admin.php'; ?>
    <div class="container mt-4">
    <p><a href="gerenciar_turmas.php">← Voltar para Gerenciar Turmas</a></p>

    <div class="wrapper">
        <h2>Gerenciar Matrículas</h2>
        <div class="alert alert-info">
            <h4>Turma: <?php echo htmlspecialchars($nome_turma_atual); ?> (<?php echo htmlspecialchars($nome_curso_atual); ?>)</h4>
            <p>Ano/Semestre: <?php echo htmlspecialchars($ano_semestre_atual); ?></p>
        </div>

        <div class="card mt-4">
            <div class="card-header">Matricular Novo Aluno</div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id_turma=<?php echo $id_turma; ?>" method="post">
                    <input type="hidden" name="acao" value="matricular">
                    <div class="form-row">
                        <div class="col-md-8">
                            <select name="id_aluno" class="form-control">
                                <option value="">Selecione um aluno...</option>
                                <?php while($aluno = mysqli_fetch_assoc($alunos_disponiveis_result)): ?>
                                    <option value="<?php echo $aluno['id_aluno']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome_completo']); ?> (Matrícula: <?php echo htmlspecialchars($aluno['matricula']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Matricular Aluno</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Alunos Matriculados (<?php echo $matriculas_result ? mysqli_num_rows($matriculas_result) : 0; ?>)</h3>
        <table class="table table-bordered table-striped mt-3">
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
                        <td>
                            <a href="desmatricular_aluno.php?id=<?php echo $row['id_matricula']; ?>" 
                               onclick="return confirm('Tem certeza que deseja desmatricular este aluno?')"
                               class="btn btn-danger btn-sm">Desmatricular</a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum aluno matriculado nesta turma.</td>
                    </tr>
                <?php
                endif; 
                ?>
            </tbody>
        </table>
        
        <?php mysqli_close($link); ?>
    </div>
    </div>
</body>
</html>