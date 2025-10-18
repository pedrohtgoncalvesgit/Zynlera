<?php
require_once 'restricao_acesso.php';
$link = require_once '../conexão.php';

$mensagem_sucesso = "";
$mensagem_erro = "";

if(isset($_GET['sucesso'])){
    $mensagem_sucesso = "Disciplina removida da turma com sucesso!";
}
if(isset($_GET['erro'])){
    $mensagem_erro = urldecode($_GET['erro']);
}


if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_turma = trim($_GET['id']);
$erro_geral = "";

// Lógica para adicionar disciplina e professor
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['id_disciplina']) && !empty($_POST['id_professor'])) {
        $id_disciplina = $_POST['id_disciplina'];
        $id_professor = $_POST['id_professor'];

        $sql_insert = "INSERT INTO disciplinas_turmas (id_turma, id_disciplina, id_professor) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "iii", $id_turma, $id_disciplina, $id_professor);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        $erro_geral = "Por favor, selecione uma disciplina e um professor.";
    }
}

// Buscar informações da turma
$sql_turma = "SELECT nome_turma, id_curso FROM turmas WHERE id_turma = ?";
$nome_turma = "";
if($stmt_turma = mysqli_prepare($link, $sql_turma)){
    mysqli_stmt_bind_param($stmt_turma, "i", $id_turma);
    mysqli_stmt_execute($stmt_turma);
    $result_turma = mysqli_stmt_get_result($stmt_turma);
    $turma = mysqli_fetch_assoc($result_turma);
    $nome_turma = $turma['nome_turma'];
	$id_curso_turma = $turma['id_curso']; // Pega o id_curso da turma
    mysqli_stmt_close($stmt_turma);
}


// Buscar disciplinas disponíveis para o curso da turma
$sql_disciplinas = "SELECT d.id_disciplina, d.nome_disciplina FROM disciplinas d WHERE d.id_curso = ? AND d.id_disciplina NOT IN (SELECT dt.id_disciplina FROM disciplinas_turmas dt WHERE dt.id_turma = ?) ORDER BY d.nome_disciplina";
$result_disciplinas = null;
if($stmt_disciplinas = mysqli_prepare($link, $sql_disciplinas)){
    mysqli_stmt_bind_param($stmt_disciplinas, "ii", $id_curso_turma, $id_turma);
    mysqli_stmt_execute($stmt_disciplinas);
    $result_disciplinas = mysqli_stmt_get_result($stmt_disciplinas);
}

$sql_professores = "SELECT p.id_professor, u.nome_completo FROM professores p JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY u.nome_completo";
$result_professores = mysqli_query($link, $sql_professores);

// Buscar disciplinas e professores já associados à turma
$sql_associados = "SELECT dt.id_disc_turma, d.nome_disciplina, u.nome_completo AS nome_professor FROM disciplinas_turmas dt JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina JOIN professores p ON dt.id_professor = p.id_professor JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE dt.id_turma = ?";
$result_associados = null;
if($stmt_associados = mysqli_prepare($link, $sql_associados)){
    mysqli_stmt_bind_param($stmt_associados, "i", $id_turma);
    mysqli_stmt_execute($stmt_associados);
    $result_associados = mysqli_stmt_get_result($stmt_associados);
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Disciplinas e Professores da Turma</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Gerenciar Disciplinas e Professores da Turma: <?php echo htmlspecialchars($nome_turma); ?></h2>
        <?php if(!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if(!empty($mensagem_erro)): ?>
            <div class="alert alert-danger"><?php echo $mensagem_erro; ?></div>
        <?php endif; ?>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>
        
        <?php if ($result_disciplinas && mysqli_num_rows($result_disciplinas) > 0): ?>
        <form action="" method="post" class="mb-4">
            <div class="form-row">
                <div class="col">
                    <select name="id_disciplina" class="form-control">
                        <option value="">Selecione a Disciplina</option>
                        <?php 
                        if ($result_disciplinas && mysqli_num_rows($result_disciplinas) > 0) {
                            mysqli_data_seek($result_disciplinas, 0); // Reset pointer
                            while($disciplina = mysqli_fetch_assoc($result_disciplinas)) : ?>
                                <option value="<?php echo $disciplina['id_disciplina']; ?>"><?php echo $disciplina['nome_disciplina']; ?></option>
                            <?php endwhile; 
                        }
                        ?>
                    </select>
                </div>
                <div class="col">
                    <select name="id_professor" class="form-control">
                        <option value="">Selecione o Professor</option>
                        <?php 
                        if ($result_professores && mysqli_num_rows($result_professores) > 0) {
                            mysqli_data_seek($result_professores, 0); // Reset pointer
                            while($professor = mysqli_fetch_assoc($result_professores)) : ?>
                            <option value="<?php echo $professor['id_professor']; ?>"><?php echo $professor['nome_completo']; ?></option>
                        <?php endwhile; 
                        }
                        ?>
                    </select>
                </div>
                <div class="col">
                    <button type="submit" class="btn btn-primary">Associar</button>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-info">
                Todas as disciplinas do curso já foram adicionadas a esta turma. Para cadastrar uma nova disciplina, <a href="cadastrar_disciplina.php">clique aqui</a>. Para alterar o professor de uma disciplina, edite a associação na tabela abaixo.
            </div>
        <?php endif; ?>

        <h4>Disciplinas e Professores Associados</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result_associados && mysqli_num_rows($result_associados) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result_associados)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome_professor']); ?></td>
                            <td>
                                <a href="editar_disciplina_turma.php?id=<?php echo $row['id_disc_turma']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="excluir_disciplina_turma.php?id=<?php echo $row['id_disc_turma']; ?>" class="btn btn-danger btn-sm">Remover</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">Nenhuma disciplina associada a esta turma.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="gerenciar_turmas.php" class="btn btn-secondary">Voltar</a>
    </div>
</body>
</html>