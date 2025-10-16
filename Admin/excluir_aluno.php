<?php
// CORREÇÃO DE INCLUSÃO
require_once 'restricao_acesso.php'; 
require_once '../conexão.php'; 

// 1. Validar ID do Aluno
if (!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: gerenciar_alunos.php");
    exit();
}

$id_aluno = trim($_GET["id"]);
$id_usuario = null;

// 2. Buscar o id_usuario associado 
$sql_get_usuario = "SELECT id_usuario FROM alunos WHERE id_aluno = ?";
if ($stmt_get = mysqli_prepare($link, $sql_get_usuario)) {
    mysqli_stmt_bind_param($stmt_get, "i", $param_id_aluno);
    $param_id_aluno = $id_aluno;
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    
    if (mysqli_num_rows($result_get) == 1) {
        $row = mysqli_fetch_assoc($result_get);
        $id_usuario = $row['id_usuario'];
    }
    mysqli_stmt_close($stmt_get);
}

if (is_null($id_usuario)) {
    header("location: gerenciar_alunos.php?erro=aluno_nao_encontrado");
    exit();
}

// 3. Processar APENAS a Inativação (Desativação) na tabela USUARIOS
// REMOVIDO: A tentativa de UPDATE na tabela 'alunos' para resolver o erro 'Unknown column 'ativo''
mysqli_begin_transaction($link);
$sucesso = true;

// A. Inativar Usuário (Setar 'ativo = 0' na tabela 'usuarios')
$sql_inativar_usuario = "UPDATE usuarios SET ativo = 0 WHERE id_usuario = ?";
if ($stmt_u = mysqli_prepare($link, $sql_inativar_usuario)) {
    mysqli_stmt_bind_param($stmt_u, "i", $id_usuario);
    if (!mysqli_stmt_execute($stmt_u)) { $sucesso = false; }
    mysqli_stmt_close($stmt_u);
} else { $sucesso = false; }


// 4. Finalizar Transação e Redirecionar
if ($sucesso) {
    mysqli_commit($link);
    // Redireciona com mensagem de sucesso de inativação
    header("location: gerenciar_alunos.php?sucesso=inativacao"); 
    exit();
} else {
    mysqli_rollback($link);
    header("location: gerenciar_alunos.php?erro=inativacao_falhou");
    exit();
}

mysqli_close($link);
?>