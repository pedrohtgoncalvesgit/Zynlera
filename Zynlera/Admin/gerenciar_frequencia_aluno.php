<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// 1. Validar IDs
if (!isset($_GET['id_aluno']) || !isset($_GET['id_vinculo'])) {
    header("location: corrigir_notas.php");
    exit();
}
$id_aluno = trim($_GET['id_aluno']);
$id_vinculo = trim($_GET['id_vinculo']);

// 2. Lógica de ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $frequencias_post = $_POST['frequencias'] ?? [];
    mysqli_begin_transaction($link);
    try {
        $sql_select = "SELECT id_frequencia FROM frequencias WHERE id_aula = ? AND id_aluno = ?";
        $sql_update = "UPDATE frequencias SET status = ? WHERE id_frequencia = ?";
        $sql_insert = "INSERT INTO frequencias (id_aula, id_aluno, status) VALUES (?, ?, ?)";

        $stmt_select = mysqli_prepare($link, $sql_select);
        $stmt_update = mysqli_prepare($link, $sql_update);
        $stmt_insert = mysqli_prepare($link, $sql_insert);

        foreach ($frequencias_post as $id_aula => $status) {
            if (empty($status)) continue;

            // Verifica se o registro já existe
            mysqli_stmt_bind_param($stmt_select, "ii", $id_aula, $id_aluno);
            mysqli_stmt_execute($stmt_select);
            $result = mysqli_stmt_get_result($stmt_select);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Existe: Atualiza
                $id_frequencia = $row['id_frequencia'];
                mysqli_stmt_bind_param($stmt_update, "si", $status, $id_frequencia);
                mysqli_stmt_execute($stmt_update);
            } else {
                // Não existe: Insere
                mysqli_stmt_bind_param($stmt_insert, "iis", $id_aula, $id_aluno, $status);
                mysqli_stmt_execute($stmt_insert);
            }
        }

        mysqli_commit($link);
        header("location: " . $_SERVER['PHP_SELF'] . "?id_aluno=$id_aluno&id_vinculo=$id_vinculo&sucesso=1");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        $erro_geral = "Erro ao atualizar as frequências: " . $e->getMessage();
    }
}

// 3. Buscar dados do Aluno e da Disciplina para o cabeçalho
$aluno_info = [];
$sql_aluno = "SELECT nome_completo, matricula FROM usuarios u JOIN alunos a ON u.id_usuario = a.id_usuario WHERE a.id_aluno = ?";
if($stmt_aluno = mysqli_prepare($link, $sql_aluno)){
    mysqli_stmt_bind_param($stmt_aluno, "i", $id_aluno);
    mysqli_stmt_execute($stmt_aluno);
    $aluno_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_aluno));
    mysqli_stmt_close($stmt_aluno);
}

// 4. Buscar todas as aulas da disciplina
$aulas = [];
$sql_aulas = "SELECT a.id_aula, a.data_aula, a.conteudo, f.status 
              FROM aulas a
              LEFT JOIN frequencias f ON a.id_aula = f.id_aula AND f.id_aluno = ?
              WHERE a.id_disc_turma = ? 
              ORDER BY a.data_aula ASC";
if ($stmt_aulas = mysqli_prepare($link, $sql_aulas)) {
    mysqli_stmt_bind_param($stmt_aulas, "ii", $id_aluno, $id_vinculo);
    mysqli_stmt_execute($stmt_aulas);
    $result_aulas = mysqli_stmt_get_result($stmt_aulas);
    while ($row = mysqli_fetch_assoc($result_aulas)) {
        $aulas[] = $row;
    }
    mysqli_stmt_close($stmt_aulas);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Frequência do Aluno</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include 'menu_admin.php'; ?>
    <div class="container mt-5">
        <p><a href="editar_notas_final.php?id_vinculo=<?php echo $id_vinculo; ?>">← Voltar para a correção de notas</a></p>
        <h2>Gerenciar Frequência</h2>
        <div class="alert alert-secondary">
            <p><strong>Aluno:</strong> <?php echo htmlspecialchars($aluno_info['nome_completo']); ?></p>
            <p class="mb-0"><strong>Matrícula:</strong> <?php echo htmlspecialchars($aluno_info['matricula']); ?></p>
        </div>

        <?php if(isset($erro_geral)): ?><div class="alert alert-danger"><?php echo $erro_geral; ?></div><?php endif; ?>
        <?php if(isset($_GET['sucesso'])): ?><div class="alert alert-success">Frequências atualizadas com sucesso!</div><?php endif; ?>

        <form action="" method="post">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Data da Aula</th>
                        <th>Conteúdo</th>
                        <th>Status da Presença</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($aulas)): ?>
                        <?php foreach ($aulas as $aula): ?>
                            <tr>
                                <td><?php echo date("d/m/Y", strtotime($aula['data_aula'])); ?></td>
                                <td><?php echo htmlspecialchars($aula['conteudo']); ?></td>
                                <td>
                                    <select name="frequencias[<?php echo $aula['id_aula']; ?>]" class="form-control">
                                        <option value="Presente" <?php echo ($aula['status'] == 'Presente') ? 'selected' : ''; ?>>Presente</option>
                                        <option value="Falta" <?php echo ($aula['status'] == 'Falta') ? 'selected' : ''; ?>>Falta</option>
                                        <option value="Justificada" <?php echo ($aula['status'] == 'Justificada') ? 'selected' : ''; ?>>Justificada</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">Nenhuma aula cadastrada para esta disciplina.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if (!empty($aulas)): ?>
                <button type="submit" class="btn btn-primary">Salvar Alterações de Frequência</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
