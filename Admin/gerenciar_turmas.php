<?php
header('Content-Type: text/html; charset=utf-8');
// gerenciar_turmas.php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// Consulta para buscar todas as turmas (juntando com o nome do curso)
$sql = "SELECT t.id_turma, t.nome_turma, t.ano, t.semestre, c.nome_curso AS curso 
        FROM turmas t 
        INNER JOIN cursos c ON t.id_curso = c.id_curso
        ORDER BY t.ano DESC, t.semestre ASC, c.nome_curso ASC";

$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Turmas</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .wrapper { width: 90%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-add { background-color: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
        .action-link { margin-right: 10px; text-decoration: none; white-space: nowrap; }
    </style>
</head>
<body>

    <?php include 'menu_admin.php'; // Seu menu de navegação ?>

    <div class="wrapper">
        <h2>Gerenciamento de Turmas</h2>
        
        <a href="cadastrar_turma.php" class="btn-add">Adicionar Nova Turma</a>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
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
                        <a href="editar_turma.php?id=<?php echo $row['id_turma']; ?>" class="action-link">Editar</a> |
                        
                        <a href="gerenciar_disciplinas_turma.php?id=<?php echo $row['id_turma']; ?>" class="action-link">Disciplinas/Professores</a> |
                        
                        <a href="gerenciar_matriculas.php?id_turma=<?php echo $row['id_turma']; ?>" class="action-link">Matrículas</a> |

                        <a href="excluir_turma.php?id=<?php echo $row['id_turma']; ?>" 
                           onclick="return confirm('Tem certeza que deseja excluir esta turma?')"
                           class="action-link">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="margin-top: 20px;">Nenhuma turma cadastrada.</p>
        <?php endif; ?>

        <?php mysqli_close($link); ?>
    </div>
</body>
</html>