<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$mensagem_sucesso = $erro_geral = "";
if(isset($_GET['sucesso'])){
    $mensagem_sucesso = "Notas atualizadas com sucesso!";
}
if(isset($_GET['erro'])){
    $erro_geral = urldecode($_GET['erro']);
}


// 1. Validar IDs
if (!isset($_GET['id_vinculo']) || empty(trim($_GET['id_vinculo']))) {
    header("location: corrigir_notas.php");
    exit();
}
$id_vinculo = trim($_GET['id_vinculo']);

// 2. Obter Informações do Cabeçalho (Turma, Disciplina, etc.)
$info_header = [];
$sql_info = "SELECT dt.id_turma, d.nome_disciplina, d.codigo_disciplina, t.nome_turma, c.nome_curso, t.ano, t.semestre FROM disciplinas_turmas dt JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina JOIN turmas t ON dt.id_turma = t.id_turma JOIN cursos c ON t.id_curso = c.id_curso WHERE dt.id_disc_turma = ?";
if ($stmt_info = mysqli_prepare($link, $sql_info)) {
    mysqli_stmt_bind_param($stmt_info, "i", $id_vinculo);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);
    if (mysqli_num_rows($result_info) == 1) {
        $info_header = mysqli_fetch_assoc($result_info);
    } else {
        header("location: corrigir_notas.php"); exit();
    }
    mysqli_stmt_close($stmt_info);
}
$id_turma = $info_header['id_turma'];

// 3. Lógica de ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $notas_post = $_POST['notas'] ?? [];
    mysqli_begin_transaction($link);
    try {
        $sql_update = "UPDATE notas SET valor = ? WHERE id_nota = ?";
        $stmt_update = mysqli_prepare($link, $sql_update);
        foreach ($notas_post as $id_aluno => $avaliacoes) {
            foreach ($avaliacoes as $id_avaliacao => $dados) {
                if (isset($dados['id_nota']) && $dados['id_nota'] != '' && isset($dados['valor'])) {
                    mysqli_stmt_bind_param($stmt_update, "di", $dados['valor'], $dados['id_nota']);
                    mysqli_stmt_execute($stmt_update);
                } elseif (isset($dados['valor']) && $dados['valor'] !== '') {
                    $sql_insert = "INSERT INTO notas (id_avaliacao, id_aluno, valor) VALUES (?, ?, ?)";
                    if($stmt_insert = mysqli_prepare($link, $sql_insert)){
                        mysqli_stmt_bind_param($stmt_insert, "iid", $id_avaliacao, $id_aluno, $dados['valor']);
                        mysqli_stmt_execute($stmt_insert);
                        mysqli_stmt_close($stmt_insert);
                    }
                }
            }
        }
        mysqli_commit($link);
        header("location: " . $_SERVER['PHP_SELF'] . "?id_vinculo=$id_vinculo&sucesso=1");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        $erro_geral = "Erro ao atualizar as notas: " . $e->getMessage();
    }
}

// 4. Buscar as Avaliações da Disciplina
$avaliacoes = [];
$sql_avaliacoes = "SELECT id_avaliacao, titulo FROM avaliacoes WHERE id_disc_turma = ? ORDER BY data_avaliacao, titulo";
if ($stmt_avaliacoes = mysqli_prepare($link, $sql_avaliacoes)) {
    mysqli_stmt_bind_param($stmt_avaliacoes, "i", $id_vinculo);
    mysqli_stmt_execute($stmt_avaliacoes);
    $result_avaliacoes = mysqli_stmt_get_result($stmt_avaliacoes);
    while ($row = mysqli_fetch_assoc($result_avaliacoes)) {
        $avaliacoes[] = $row;
    }
    mysqli_stmt_close($stmt_avaliacoes);
}

// 5. Buscar Alunos e suas respectivas notas e faltas
$alunos_data = [];
$sql_alunos = "SELECT a.id_aluno, u.nome_completo, a.matricula FROM matriculas m JOIN alunos a ON m.id_aluno = a.id_aluno JOIN usuarios u ON a.id_usuario = u.id_usuario WHERE m.id_turma = ? AND m.situacao = 'ativa' ORDER BY u.nome_completo";
if ($stmt_alunos = mysqli_prepare($link, $sql_alunos)) {
    mysqli_stmt_bind_param($stmt_alunos, "i", $id_turma);
    mysqli_stmt_execute($stmt_alunos);
    $result_alunos = mysqli_stmt_get_result($stmt_alunos);
    while ($aluno = mysqli_fetch_assoc($result_alunos)) {
        $id_aluno = $aluno['id_aluno'];
        $alunos_data[$id_aluno] = $aluno;
        $alunos_data[$id_aluno]['notas'] = [];

        // Buscar notas para este aluno
        $sql_notas = "SELECT id_nota, id_avaliacao, valor FROM notas WHERE id_aluno = ?";
        if($stmt_notas = mysqli_prepare($link, $sql_notas)){
            mysqli_stmt_bind_param($stmt_notas, "i", $id_aluno);
            mysqli_stmt_execute($stmt_notas);
            $result_notas = mysqli_stmt_get_result($stmt_notas);
            while($nota = mysqli_fetch_assoc($result_notas)){
                $alunos_data[$id_aluno]['notas'][$nota['id_avaliacao']] = $nota;
            }
            mysqli_stmt_close($stmt_notas);
        }

        // Buscar total de faltas
        $sql_faltas = "SELECT COUNT(id_frequencia) as total_faltas FROM frequencias f JOIN aulas a ON f.id_aula = a.id_aula WHERE f.id_aluno = ? AND a.id_disc_turma = ? AND f.status = 'Falta'";
        if($stmt_faltas = mysqli_prepare($link, $sql_faltas)){
            mysqli_stmt_bind_param($stmt_faltas, "ii", $id_aluno, $id_vinculo);
            mysqli_stmt_execute($stmt_faltas);
            $result_faltas = mysqli_stmt_get_result($stmt_faltas);
            $faltas_data = mysqli_fetch_assoc($result_faltas);
            $alunos_data[$id_aluno]['total_faltas'] = $faltas_data['total_faltas'] ?? 0;
            mysqli_stmt_close($stmt_faltas);
        }
    }
    mysqli_stmt_close($stmt_alunos);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Corrigir Notas e Faltas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include 'menu_admin.php'; ?>
    <div class="container mt-5">
        <p><a href="corrigir_notas.php?id_turma=<?php echo $id_turma; ?>">← Voltar para a seleção de disciplinas</a></p>
        <h2>Correção de Notas e Faltas</h2>

        <?php if(!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <h4><?php echo htmlspecialchars($info_header['codigo_disciplina'] . " - " . $info_header['nome_disciplina']); ?></h4>
            <p>Turma: <?php echo htmlspecialchars($info_header['nome_turma']); ?> (<?php echo htmlspecialchars($info_header['nome_curso']); ?>) - <?php echo htmlspecialchars($info_header['ano'] . '/' . $info_header['semestre']); ?></p>
        </div>

        <form action="" method="post">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <th><?php echo htmlspecialchars($avaliacao['titulo']); ?></th>
                            <?php endforeach; ?>
                            <th>Faltas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos_data as $id_aluno => $aluno): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aluno['nome_completo']); ?><br><small class="text-muted"><?php echo htmlspecialchars($aluno['matricula']); ?></small></td>
                                <?php foreach ($avaliacoes as $avaliacao): 
                                    $id_avaliacao = $avaliacao['id_avaliacao'];
                                    $nota_info = $aluno['notas'][$id_avaliacao] ?? null;
                                    $valor_nota = $nota_info['valor'] ?? '';
                                    $id_nota = $nota_info['id_nota'] ?? '';
                                ?>
                                    <td>
                                        <input type="hidden" name="notas[<?php echo $id_aluno; ?>][<?php echo $id_avaliacao; ?>][id_nota]" value="<?php echo $id_nota; ?>">
                                        <input type="number" name="notas[<?php echo $id_aluno; ?>][<?php echo $id_avaliacao; ?>][valor]" value="<?php echo htmlspecialchars($valor_nota); ?>" step="0.1" min="0" max="10" class="form-control">
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <?php echo $aluno['total_faltas']; ?>
                                    <a href="gerenciar_frequencia_aluno.php?id_aluno=<?php echo $id_aluno; ?>&id_vinculo=<?php echo $id_vinculo; ?>" class="btn btn-secondary btn-sm">Gerenciar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success">Salvar Todas as Alterações</button>
        </form>
    </div>
</body>
</html>