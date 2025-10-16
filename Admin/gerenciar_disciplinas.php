<?php
// Inclui a restrição de acesso e a conexão com o banco
require_once 'restricao_acesso.php';
$link = require_once '../conexão.php'; 

// Consulta SQL para buscar todas as disciplinas, juntando com o nome do curso
$sql = "SELECT 
            d.id_disciplina, 
            d.nome_disciplina, 
            d.codigo_disciplina, 
            d.carga_horaria, 
            c.nome_curso
        FROM disciplinas d
        INNER JOIN cursos c ON d.id_curso = c.id_curso
        ORDER BY c.nome_curso ASC, d.nome_disciplina ASC";

$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Disciplinas - Administrador</title>
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 90%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .btn-add { display: inline-block; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px;}
        .alert { color: red; font-size: 0.9em; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu_admin.php'; ?>
        <h2>Gerenciamento de Disciplinas</h2>
        <a href="cadastrar_disciplina.php" class="btn-add">Adicionar Nova Disciplina</a>

        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'dependencia'): ?>
            <div class="alert">Erro: Não foi possível excluir a disciplina. Ela possui vínculos com Turmas/Professores.</div>
        <?php endif; ?>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 30%;">Disciplina</th>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 10%;">Carga Horária (h)</th>
                        <th style="width: 30%;">Curso</th>
                        <th style="width: 10%;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id_disciplina']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars($row['codigo_disciplina']); ?></td>
                            <td><?php echo $row['carga_horaria']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome_curso']); ?></td>
                            <td>
                                <a href="editar_disciplina.php?id=<?php echo $row['id_disciplina']; ?>">Editar</a> | 
                                <a href="excluir_disciplina.php?id=<?php echo $row['id_disciplina']; ?>" onclick="return confirm('ATENÇÃO: A exclusão só é permitida se não houver vínculo em nenhuma turma.')">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma disciplina cadastrada no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>