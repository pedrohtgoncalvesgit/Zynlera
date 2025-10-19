<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// 1. Validar e Obter o ID da associação (id_disc_turma)
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_disc_turma = trim($_GET['id']);

// 2. Buscar o id_turma antes de deletar, para poder redirecionar de volta
$id_turma = null;
$sql_get_turma = "SELECT id_turma FROM disciplinas_turmas WHERE id_disc_turma = ?";
if ($stmt_get_turma = mysqli_prepare($link, $sql_get_turma)) {
    mysqli_stmt_bind_param($stmt_get_turma, "i", $id_disc_turma);
    mysqli_stmt_execute($stmt_get_turma);
    $result = mysqli_stmt_get_result($stmt_get_turma);
    if ($row = mysqli_fetch_assoc($result)) {
        $id_turma = $row['id_turma'];
    }
    mysqli_stmt_close($stmt_get_turma);
}

// Se não encontrou a turma, redireciona para a página geral de turmas
if ($id_turma === null) {
    header("location: gerenciar_turmas.php?erro=associacao_nao_encontrada");
    exit();
}

// 3. Tentar excluir a associação
$sql_delete = "DELETE FROM disciplinas_turmas WHERE id_disc_turma = ?";

if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_disc_turma);
    
    // A execução pode falhar devido a restrições de chave estrangeira (FK)
    // se houver aulas, avaliações, etc., vinculadas a esta associação.
    if (mysqli_stmt_execute($stmt_delete)) {
        // Sucesso
        header("location: gerenciar_disciplinas_turma.php?id=" . $id_turma . "&sucesso=removido");
    } else {
        // Erro (provavelmente FK)
        $error_message = urlencode("Não foi possível remover a disciplina. Verifique se existem aulas, avaliações ou notas associadas a ela e remova-as primeiro.");
        header("location: gerenciar_disciplinas_turma.php?id=" . $id_turma . "&erro=" . $error_message);
    }
    mysqli_stmt_close($stmt_delete);
} else {
    // Erro na preparação da query
    $error_message = urlencode("Ocorreu um erro ao preparar a operação.");
    header("location: gerenciar_disciplinas_turma.php?id=" . $id_turma . "&erro=" . $error_message);
}

mysqli_close($link);
exit();
?>
