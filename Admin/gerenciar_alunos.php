<?php
// Inclui a restrição de acesso e a conexão com o banco
require_once 'restrição_acesso.php';
require_once '../conexão.php'; // Adicione o 'ç'

// Consulta SQL para buscar todos os alunos (incluindo dados do usuário)
// Acessamos 'nome_completo' e 'email' de 'usuarios' e 'matricula' de 'alunos'
$sql = "SELECT 
    a.id_aluno,
    a.matricula,
    u.nome_completo,
    u.email,
    u.ativo,        
    u.id_usuario
FROM alunos a
INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
ORDER BY u.nome_completo ASC";
$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Alunos - Administrador</title>
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
        <?php include 'menu_admin.php'; // Incluiremos um menu aqui ?>
        <h2>Gerenciamento de Alunos</h2>
        <a href="cadastrar_alunos.php" class="btn-add">Adicionar Novo Aluno</a>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Matrícula</th>
                        <th>Nome Completo</th>
                        <th>Email de Login</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id_aluno']; ?></td>
                            <td><?php echo htmlspecialchars($row['matricula']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo $row['ativo'] ? 'Ativo' : 'Inativo '; ?></td>
                            <td>
                                <a href="editar_alunos.php?id=<?php echo $row['id_aluno']; ?>">Editar</a> | 
                                <a href="excluir_aluno.php?id=<?php echo $row['id_aluno']; ?>" onclick="return confirm('Tem certeza que deseja tentar inativar  este aluno?')">Inativar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum aluno cadastrado no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_close($link);
?>