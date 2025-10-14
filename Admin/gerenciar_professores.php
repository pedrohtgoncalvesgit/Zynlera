<?php
require_once 'restrição_acesso.php';
require_once '../conexão.php';


// Consulta SQL para buscar todos os professores (incluindo dados do usuário)
$sql = "SELECT 
            p.id_professor, 
            p.registro_funcional, 
            u.nome_completo, 
            u.email,
            u.ativo
        FROM professores p
        INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
        ORDER BY u.nome_completo ASC";

$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Professores - Administrador</title>
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 80%; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-add { display: inline-block; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu_admin.php'; // Certifique-se de que este menu será atualizado! ?>
        <h2>Gerenciamento de Professores</h2>
        <a href="cadastrar_professor.php" class="btn-add">Adicionar Novo Professor</a>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Registro Funcional</th>
                        <th>Nome Completo</th>
                        <th>Email de Login</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id_professor']; ?></td>
                            <td><?php echo htmlspecialchars($row['registro_funcional']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo $row['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                            <td>
                                <a href="editar_professor.php?id=<?php echo $row['id_professor']; ?>">Editar</a> | 
                                <a href="excluir_professor.php?id=<?php echo $row['id_professor']; ?>" onclick="return confirm('Tem certeza que deseja Inativar este professor?')">Inativar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum professor cadastrado no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_close($link);
?>