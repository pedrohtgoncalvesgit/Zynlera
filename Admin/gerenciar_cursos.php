<?php
// Inclui a restrição de acesso e a conexão com o banco
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// Consulta SQL para buscar todos os cursos
$sql = "SELECT id_curso, nome_curso, descricao FROM cursos ORDER BY nome_curso ASC";
$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Cursos - Administrador</title>
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 80%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .btn-add { display: inline-block; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px;}
        /* Estilos do formulário para reuso */
        .alert { color: red; font-size: 0.9em; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu_admin.php'; ?>
        <h2>Gerenciamento de Cursos</h2>
        <a href="cadastrar_curso.php" class="btn-add">Adicionar Novo Curso</a>

        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'dependencia'): ?>
            <div class="alert">Erro: Não foi possível excluir o curso. Ele possui disciplinas ou turmas vinculadas.</div>
        <?php endif; ?>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 30%;">Nome do Curso</th>
                        <th style="width: 50%;">Descrição</th>
                        <th style="width: 15%;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id_curso']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome_curso']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['descricao'], 0, 150)) . (strlen($row['descricao']) > 150 ? '...' : ''); ?></td>
                            <td>
                                <a href="editar_curso.php?id=<?php echo $row['id_curso']; ?>">Editar</a> | 
                                <a href="excluir_curso.php?id=<?php echo $row['id_curso']; ?>" onclick="return confirm('ATENÇÃO: Excluir este curso irá afetar todas as disciplinas e turmas vinculadas. Tem certeza?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum curso cadastrado no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>