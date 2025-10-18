<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// 1. Validar e Obter o ID da Matrícula
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: gerenciar_turmas.php");
    exit();
}
$id_matricula = trim($_GET['id']);

// 2. Buscar o id_turma antes de deletar/atualizar, para poder redirecionar de volta
$id_turma = null;
$sql_get_turma = "SELECT id_turma FROM matriculas WHERE id_matricula = ?";
if ($stmt_get_turma = mysqli_prepare($link, $sql_get_turma)) {
    mysqli_stmt_bind_param($stmt_get_turma, "i", $id_matricula);
    mysqli_stmt_execute($stmt_get_turma);
    $result = mysqli_stmt_get_result($stmt_get_turma);
    if ($row = mysqli_fetch_assoc($result)) {
        $id_turma = $row['id_turma'];
    }
    mysqli_stmt_close($stmt_get_turma);
}

// Se não encontrou a turma, redireciona para a página geral de turmas
if ($id_turma === null) {
    header("location: gerenciar_turmas.php?erro=matricula_nao_encontrada");
    exit();
}

// 3. Atualizar a situação da matrícula para 'cancelada'
// Em vez de excluir, alteramos a situação para manter o histórico.
$sql_update = "UPDATE matriculas SET situacao = 'cancelada' WHERE id_matricula = ?";

if ($stmt_update = mysqli_prepare($link, $sql_update)) {
    mysqli_stmt_bind_param($stmt_update, "i", $id_matricula);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Sucesso: redireciona de volta para a página de matrículas da turma
        header("location: gerenciar_matriculas.php?id_turma=" . $id_turma . "&sucesso=desmatriculado");
    } else {
        // Erro: redireciona com uma mensagem de erro
        header("location: gerenciar_matriculas.php?id_turma=" . $id_turma . "&erro=ao_desmatricular");
    }
    mysqli_stmt_close($stmt_update);
} else {
    // Erro na preparação da query
    header("location: gerenciar_matriculas.php?id_turma=" . $id_turma . "&erro=query_preparacao");
}

mysqli_close($link);
exit();
?>
