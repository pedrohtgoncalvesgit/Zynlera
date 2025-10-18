<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_turma = $_GET['id_turma'] ?? null;
$id_vinculo = $_GET['id_vinculo'] ?? null;

// Se um vínculo foi selecionado, redireciona para a página de edição
if ($id_vinculo) {
    // O conteúdo da página de edição de notas que criei antes virá aqui.
    // Por enquanto, vamos apenas mostrar uma mensagem.
    echo "Redirecionando para a edição de notas...";
    header("Location: editar_notas_final.php?id_vinculo=" . $id_vinculo);
    exit();
}

$page_title = "Selecionar Turma";
$items = [];

if ($id_turma) {
    // Etapa 2: Listar disciplinas da turma selecionada
    $page_title = "Selecionar Disciplina";
    $sql = "SELECT dt.id_disc_turma, d.nome_disciplina, u.nome_completo AS nome_professor 
            FROM disciplinas_turmas dt 
            JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina 
            JOIN professores p ON dt.id_professor = p.id_professor 
            JOIN usuarios u ON p.id_usuario = u.id_usuario 
            WHERE dt.id_turma = ? 
            ORDER BY d.nome_disciplina";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_turma);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'id' => $row['id_disc_turma'],
                'name' => $row['nome_disciplina'] . ' - ' . $row['nome_professor'],
                'link' => 'corrigir_notas.php?id_vinculo=' . $row['id_disc_turma']
            ];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Etapa 1: Listar todas as turmas
    $sql = "SELECT t.id_turma, t.nome_turma, c.nome_curso, t.ano, t.semestre 
            FROM turmas t
            JOIN cursos c ON t.id_curso = c.id_curso
            ORDER BY t.ano DESC, t.semestre DESC, t.nome_turma ASC";
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['id_turma'],
            'name' => $row['nome_turma'] . ' (' . $row['nome_curso'] . ') - ' . $row['ano'] . '/' . $row['semestre'],
            'link' => 'corrigir_notas.php?id_turma=' . $row['id_turma']
        ];
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include 'menu_admin.php'; ?>
    <div class="container mt-5">
        <h2><?php echo $page_title; ?></h2>
        
        <div class="list-group">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <a href="<?php echo $item['link']; ?>" class="list-group-item list-group-item-action">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">Nenhum item encontrado.</div>
            <?php endif; ?>
        </div>

        <?php if($id_turma): ?>
            <a href="corrigir_notas.php" class="btn btn-secondary mt-3">Voltar para a seleção de turmas</a>
        <?php endif; ?>
    </div>
</body>
</html>
