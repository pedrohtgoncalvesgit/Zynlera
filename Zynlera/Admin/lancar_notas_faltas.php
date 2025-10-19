<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';
// 1. Validar e Obter o ID do Vínculo (id_disciplina_turma_professor)
if (!isset($_GET['id_vinculo']) || empty(trim($_GET['id_vinculo']))) {
    // Redireciona para a lista de turmas se o ID do vínculo não for passado
    header("location: visualizar_turmas.php");
    exit();
}
$id_vinculo = trim($_GET['id_vinculo']);
$id_professor = $_SESSION["id_professor"] ?? 0;

$info_disciplina = [];
$alunos_turma_result = null;
$erro_consulta = null;
$redirecionar_turma = "visualizar_turmas.php"; // Padrão de retorno

// 2. Obter Informações do Vínculo e da Turma (Para o cabeçalho)
$sql_info = "
    SELECT 
        dtp.id_turma, 
        d.nome_disciplina, 
        d.codigo_disciplina,
        t.nome_turma, 
        c.nome_curso,
        t.ano,
        t.semestre
    FROM disciplinas_turmas_professores dtp
    INNER JOIN disciplinas d ON dtp.id_disciplina = d.id_disciplina
    INNER JOIN turmas t ON dtp.id_turma = t.id_turma
    INNER JOIN cursos c ON t.id_curso = c.id_curso
    WHERE dtp.id_disciplina_turma_professor = ? AND dtp.id_professor = ?
";

if ($stmt_info = mysqli_prepare($link, $sql_info)) {
    mysqli_stmt_bind_param($stmt_info, "ii", $id_vinculo, $id_professor);
    if (mysqli_stmt_execute($stmt_info)) {
        $result_info = mysqli_stmt_get_result($stmt_info);
        if (mysqli_num_rows($result_info) == 1) {
            $info_disciplina = mysqli_fetch_assoc($result_info);
            // Atualiza o destino de volta com base no ID da turma
            $redirecionar_turma = "gerenciar_disciplinas_professor.php?id_turma=" . $info_disciplina['id_turma'];
        } else {
            // Se o vínculo não existir ou não pertencer a este professor
            header("location: visualizar_turmas.php");
            exit();
        }
    }
    mysqli_stmt_close($stmt_info);
}

// 3. Consulta de Alunos Matriculados na Turma (e suas notas/faltas)
// NOTA: Esta consulta deve ser adaptada para incluir as notas/faltas
// (que viriam de uma tabela 'registros_disciplina' ou similar)
// Por simplicidade inicial, buscamos apenas os alunos da turma.
$sql_alunos = "
    SELECT 
        a.id_aluno,
        u.nome_completo,
        a.matricula
    FROM alunos_turmas at
    INNER JOIN alunos a ON at.id_aluno = a.id_aluno
    INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
    WHERE at.id_turma = ?
    ORDER BY u.nome_completo
";

if ($stmt_alunos = mysqli_prepare($link, $sql_alunos)) {
    mysqli_stmt_bind_param($stmt_alunos, "i", $info_disciplina['id_turma']);
    if (mysqli_stmt_execute($stmt_alunos)) {
        $alunos_turma_result = mysqli_stmt_get_result($stmt_alunos);
    } else {
        $erro_consulta = "Erro ao carregar alunos: " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_alunos);
}

// 4. Lógica de Processamento do Formulário (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Aqui seria a lógica para processar o array de notas e faltas e atualizar o BD
    // ... Seu código de UPDATE/INSERT de Notas/Faltas aqui ...
    
    // Redireciona de volta após o salvamento
    // header("location: " . $redirecionar_turma . "&sucesso=notas_salvas");
    // exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Notas/Faltas</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .wrapper { width: 90%; margin: 0 auto; padding: 20px; }
        .info-box { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        input[type="number"] { width: 60px; text-align: center; }
        .btn-salvar { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>

    <?php include 'menu_professor.php'; // Seu menu de navegação do professor ?>
    <p><a href="<?php echo htmlspecialchars($redirecionar_turma); ?>">← Voltar para Disciplinas</a></p>

    <div class="wrapper">
        <h2>Lançamento de Notas e Faltas</h2>

        <div class="info-box">
            <h4>Disciplina: <?php echo htmlspecialchars($info_disciplina['codigo_disciplina'] ?? 'N/A') . " - " . htmlspecialchars($info_disciplina['nome_disciplina'] ?? 'N/A'); ?></h4>
            <p>Turma: <?php echo htmlspecialchars($info_disciplina['nome_turma'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($info_disciplina['nome_curso'] ?? 'N/A'); ?>) - <?php echo htmlspecialchars($info_disciplina['ano'] ?? 'N/A') . '/' . htmlspecialchars($info_disciplina['semestre'] ?? 'N/A'); ?></p>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id_vinculo=" . $id_vinculo; ?>" method="post">
        
        <?php if (isset($erro_consulta)): ?>
            <p style="color: red;"><?php echo $erro_consulta; ?></p>
        <?php endif; ?>

        <?php if ($alunos_turma_result && mysqli_num_rows($alunos_turma_result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nome do Aluno</th>
                    <th>Nota 1</th>
                    <th>Nota 2</th>
                    <th>Total Faltas</th>
                    <th>Situação (Exibição)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($aluno = mysqli_fetch_assoc($alunos_turma_result)): 
                    // Valores de exemplo. Estes devem ser carregados do banco de dados real.
                    $nota1 = 0; $nota2 = 0; $faltas = 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($aluno['matricula']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                    <td><input type="number" name="notas[<?php echo $aluno['id_aluno']; ?>][n1]" value="<?php echo $nota1; ?>" step="0.1" min="0" max="10" required></td>
                    <td><input type="number" name="notas[<?php echo $aluno['id_aluno']; ?>][n2]" value="<?php echo $nota2; ?>" step="0.1" min="0" max="10"></td>
                    <td><input type="number" name="faltas[<?php echo $aluno['id_aluno']; ?>]" value="<?php echo $faltas; ?>" min="0"></td>
                    <td><span style="color: gray;">Aguardando Lançamento</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <button type="submit" class="btn-salvar">Salvar Lançamentos</button>
        
        <?php else: ?>
            <p style="margin-top: 20px;">Nenhum aluno matriculado nesta turma.</p>
        <?php endif; ?>

        </form>

        <?php mysqli_close($link); ?>
    </div>
</body>
</html>