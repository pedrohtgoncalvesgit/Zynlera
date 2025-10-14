<?php
require_once 'restricao_acesso.php';
$link = require_once '../conexao.php';

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    
    $id_disciplina = trim($_GET["id"]);
    $sucesso = false;

    // 1. Verificar dependências na tabela disciplinas_turmas
    $sql_check_dep = "SELECT COUNT(id_disc_turma) AS total_vinculos 
                      FROM disciplinas_turmas 
                      WHERE id_disciplina = ?";

    $tem_dependencia = false;
    
    if ($stmt_dep = mysqli_prepare($link, $sql_check_dep)) {
        mysqli_stmt_bind_param($stmt_dep, "i", $param_id_disciplina);
        $param_id_disciplina = $id_disciplina;
        mysqli_stmt_execute($stmt_dep);
        
        $result = mysqli_stmt_get_result($stmt_dep);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_dep);
        
        if ($row['total_vinculos'] > 0) {
            $tem_dependencia = true;
        }
    } else {
        header("location: gerenciar_disciplinas.php?erro=verificacao_dep");
        exit();
    }
    
    if ($tem_dependencia) {
        // REQUISITO: Não é possível excluir, pois tem dependência.
        header("location: gerenciar_disciplinas.php?erro=dependencia");
        exit();
    } else {
        // Exclusão PERMITIDA (sem dependências)
        $sql_delete = "DELETE FROM disciplinas WHERE id_disciplina = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $param_id_disciplina);
            $param_id_disciplina = $id_disciplina;
            
            if (mysqli_stmt_execute($stmt_delete)) {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_disciplinas.php?sucesso=excluido");
                exit();
            } else {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_disciplinas.php?erro=exclusao");
                exit();
            }
        }
    }
    
} else {
    header("location: gerenciar_disciplinas.php?erro=id_faltando");
    exit();
}

mysqli_close($link);
?>