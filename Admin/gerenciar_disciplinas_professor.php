<?php
// gerenciar_disciplinas_professor.php (Lista as disciplinas do Professor em uma Turma)
require_once 'restrição_acesso.php';
require_once '../conexão.php';

// 1. Validar e Obter ID da Turma
if (!isset($_GET['id_turma']) || empty(trim($_GET['id_turma']))) {
    header("location: visualizar_turmas.php");
    exit();
}
$id_turma = trim($_GET['id_turma']);
$id_professor = $_SESSION["id_professor"] ?? 0; // Garantindo que o ID do professor esteja disponível

$nome_turma_atual = $nome_curso_atual = $ano_semestre_atual = "Turma Não Encontrada";
$erro_consulta = null;

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

// 3. Consulta de Disciplinas Vinculadas à Turma Pelo Professor Logado
$sql_disciplinas = "
    SELECT 
        dtp.id_disciplina_turma_professor,
        d.nome_disciplina, 
        d.codigo_disciplina,
        d.creditos
    FROM disciplinas_turmas_professores dtp
    INNER JOIN disciplinas d ON dtp.id_disciplina = d.id_disciplina
    WHERE dtp.id_turma = ? AND dtp.id_professor = ?
    ORDER BY d.nome_disciplina
";

$disciplinas_result = null;

if ($stmt_disp = mysqli_prepare($link, $sql_disciplinas)) {
    mysqli_stmt_bind_param($stmt_disp, "ii", $id_turma, $id_professor);
    
    if (mysqli_stmt_execute($stmt_disp)) {
        $disciplinas_result = mysqli_stmt_get_result($stmt_disp);
    } else {
        $erro_consulta = "Erro ao carregar disciplinas: " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_disp);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Disciplinas em <?php echo htmlspecialchars($nome_turma_atual); ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .wrapper { width: 90%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-link { margin-right: 15px; text-decoration: none; white-space: nowrap; }
        .info-box { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php include 'menu_professor.php'; // Seu menu de navegação do professor ?>
    <p><a href="visualizar_turmas.php">← Voltar para Minhas Turmas</a></p>

    <div class="wrapper">
        <h2>Minhas Disciplinas na Turma</h2>

        <div class="info-box">
            <h4>Turma: <?php echo htmlspecialchars($nome_turma_atual); ?> (<?php echo htmlspecialchars($nome_curso_atual); ?>)</h4>
            <p>Ano/Semestre: <?php echo htmlspecialchars($ano_semestre_atual); ?></p>
        </div>

        <?php if (isset($erro_consulta)): ?>
            <p style="color: red;"><?php echo $erro_consulta; ?></p>
        <?php endif; ?>

        <?php if ($disciplinas_result && mysqli_num_rows($disciplinas_result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Vínculo</th>
                    <th>Código</th>
                    <th>Disciplina</th>
                    <th>Créditos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($disciplinas_result)): ?>
                <tr>
                    <td><?php echo $row['id_disciplina_turma_professor']; ?></td>
                    <td><?php echo htmlspecialchars($row['codigo_disciplina']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                    <td><?php echo $row['creditos']; ?></td>
                    <td>
                        <a href="lancar_notas_faltas.php?id_vinculo=<?php echo $row['id_disciplina_turma_professor']; ?>" class="action-link">Lançar Notas/Faltas</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="margin-top: 20px;">Você não está vinculado a nenhuma disciplina nesta turma.</p>
        <?php endif; ?>

        <?php mysqli_close($link); ?>
    </div>
</body>
</html>