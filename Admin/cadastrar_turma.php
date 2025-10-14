<?php
// editar_turma.php
require_once 'restrição_acesso.php';
require_once '../conexão.php';

// 1. Obter ID da Turma
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_turma = trim($_GET['id']);

// Variáveis para preencher o formulário
$nome_turma = $ano = $semestre = $id_curso_atual = $nome_curso_atual = "";

// 2. Consulta 1: Obter dados da Turma e Curso para o formulário
$sql_turma = "SELECT t.nome_turma, t.ano, t.semestre, c.id_curso, c.nome_curso
              FROM turmas t
              INNER JOIN cursos c ON t.id_curso = c.id_curso
              WHERE t.id_turma = ?";

if ($stmt_turma = mysqli_prepare($link, $sql_turma)) {
    mysqli_stmt_bind_param($stmt_turma, "i", $param_id_turma);
    $param_id_turma = $id_turma;
    if (mysqli_stmt_execute($stmt_turma)) {
        $result_turma = mysqli_stmt_get_result($stmt_turma);
        if (mysqli_num_rows($result_turma) == 1) {
            $turma = mysqli_fetch_assoc($result_turma);
            $nome_turma = $turma['nome_turma'];
            $ano = $turma['ano'];
            $semestre = $turma['semestre'];
            $id_curso_atual = $turma['id_curso'];
            $nome_curso_atual = $turma['nome_curso'];
        }
    }
    mysqli_stmt_close($stmt_turma);
}

// 3. Consulta 2: Obter Vínculos (Grade Curricular)
$sql_vinculos = "SELECT
    dt.id_disc_turma,
    d.nome_disciplina,
    d.codigo_disciplina AS codigo, 
    u.nome_completo AS nome_professor
FROM disciplinas_turmas dt
INNER JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina
INNER JOIN professores p ON dt.id_professor = p.id_professor
INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
WHERE dt.id_turma = ?";

$vinculos_result = null;
if ($stmt_vinculos = mysqli_prepare($link, $sql_vinculos)) {
    mysqli_stmt_bind_param($stmt_vinculos, "i", $param_id_turma_vinculo);
    $param_id_turma_vinculo = $id_turma;
    if (mysqli_stmt_execute($stmt_vinculos)) {
        $vinculos_result = mysqli_stmt_get_result($stmt_vinculos);
    }
    mysqli_stmt_close($stmt_vinculos);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Turma: <?php echo htmlspecialchars($nome_turma); ?></title>
    </head>
<body>

    <?php include 'menu_admin.php'; ?>

    <div class="wrapper">
        <h2>Editar Turma: <?php echo htmlspecialchars($nome_turma); ?> (<?php echo htmlspecialchars($nome_curso_atual); ?>)</h2>

        <h3>Disciplinas e Professores Vinculados (Grade Curricular)</h3>
        
        <p>
            <a href="gerenciar_disciplinas_turma.php?id=<?php echo $id_turma; ?>">Gerenciar/Adicionar Vínculos</a>
        </p>
        
        <table border="1" style="width:100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>ID Vínculo</th>
                    <th>Código</th>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (isset($vinculos_result) && $vinculos_result && mysqli_num_rows($vinculos_result) > 0): 
                    while($row = mysqli_fetch_assoc($vinculos_result)): 
                ?>
                    <tr>
                        <td><?php echo $row['id_disc_turma']; ?></td>
                        <td><?php echo htmlspecialchars($row['codigo']); ?></td> 
                        <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                        <td><?php echo htmlspecialchars($row['nome_professor']); ?></td>
                        <td>
                            <a href="desvincular_disciplina_turma.php?id=<?php echo $row['id_disc_turma']; ?>" 
                               onclick="return confirm('Tem certeza que deseja remover este vínculo?')"
                               class="btn-remover">Remover</a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Nenhum vínculo (Disciplina/Professor) encontrado para esta turma.</td>
                    </tr>
                <?php
                endif; 
                ?>
            </tbody>
        </table>

        <?php mysqli_close($link); ?>
    </div>
</body>
</html>