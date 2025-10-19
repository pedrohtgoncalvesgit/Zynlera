<?php
require_once 'restricao_acesso.php'; 
require_once '../conexão.php'; 

// Verifica se o ID do Professor está definido na sessão.
// Se a sua variável de sessão for diferente de 'id_professor', ajuste aqui.
if (!isset($_SESSION["id_professor"])) {
    // Redireciona para o login ou uma página de erro se o ID não estiver disponível.
    header("location: index.php"); 
    exit();
}

$id_professor = $_SESSION["id_professor"];
$nome_professor = $_SESSION["nome_completo"] ?? "Professor"; // Usado para exibição

// Consulta SQL para buscar TODAS as turmas onde o professor está vinculado, 
// através da tabela de vínculo disciplinas_turmas_professores.
$sql = "
    SELECT DISTINCT
        t.id_turma, 
        t.nome_turma, 
        t.ano, 
        t.semestre, 
        c.nome_curso AS curso 
    FROM turmas t 
    INNER JOIN cursos c ON t.id_curso = c.id_curso
    INNER JOIN disciplinas_turmas_professores dtp ON dtp.id_turma = t.id_turma
    WHERE dtp.id_professor = ?
    ORDER BY t.ano DESC, t.semestre ASC, c.nome_curso ASC
";

$result = null;

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id_professor);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        // Em caso de erro de execução
        $erro_consulta = "Erro ao executar consulta: " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt);
} else {
    // Em caso de erro de preparação
    $erro_consulta = "Erro ao preparar consulta: " . mysqli_error($link);
}

// Fechamento da conexão (opcional, dependendo de outras ações)
// mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Turmas - <?php echo htmlspecialchars($nome_professor); ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .wrapper { width: 90%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-link { margin-right: 10px; text-decoration: none; white-space: nowrap; }
        .alert-error { color: red; }
    </style>
</head>
<body>

    <?php include 'menu_professor.php'; // Seu menu de navegação do professor ?>

    <div class="wrapper">
        <h2>Minhas Turmas</h2>
        <p>Lista de turmas onde você está vinculado como professor:</p>

        <?php if (isset($erro_consulta)): ?>
            <div class="alert-error"><?php echo $erro_consulta; ?></div>
        <?php endif; ?>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Curso</th>
                    <th>Turma</th>
                    <th>Ano</th>
                    <th>Semestre</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id_turma']; ?></td>
                    <td><?php echo htmlspecialchars($row['curso']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome_turma']); ?></td>
                    <td><?php echo $row['ano']; ?></td>
                    <td><?php echo $row['semestre']; ?></td>
                    <td>
                        <a href="gerenciar_disciplinas_professor.php?id_turma=<?php echo $row['id_turma']; ?>" class="action-link">Disciplinas da Turma</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="margin-top: 20px;">Você não está vinculado a nenhuma turma no momento.</p>
        <?php endif; ?>

        <?php mysqli_close($link); ?>
    </div>
</body>
</html>