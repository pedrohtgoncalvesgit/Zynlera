<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    
    $id_turma = trim($_GET["id"]);
    $sucesso = false;

    // 1. Verificar dependências
    $sql_check_dep = "SELECT 
                        (SELECT COUNT(id_matricula) FROM matriculas WHERE id_turma = ?) AS total_matriculas,
                        (SELECT COUNT(id_disc_turma) FROM disciplinas_turmas WHERE id_turma = ?) AS total_disciplinas";

    $tem_dependencia = false;
    
    if ($stmt_dep = mysqli_prepare($link, $sql_check_dep)) {
        mysqli_stmt_bind_param($stmt_dep, "ii", $param_id_turma_1, $param_id_turma_2);
        $param_id_turma_1 = $id_turma;
        $param_id_turma_2 = $id_turma;
        mysqli_stmt_execute($stmt_dep);
        
        $result = mysqli_stmt_get_result($stmt_dep);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_dep);
        
        if ($row['total_matriculas'] > 0 || $row['total_disciplinas'] > 0) {
            $tem_dependencia = true;
        }
    } else {
        header("location: gerenciar_turmas.php?erro=verificacao_dep");
        exit();
    }
    
    if ($tem_dependencia) {
        // REQUISITO: Não é possível excluir, pois tem dependência.
        header("location: gerenciar_turmas.php?erro=dependencia");
        exit();
    } else {
        // Exclusão PERMITIDA (sem dependências)
        $sql_delete = "DELETE FROM turmas WHERE id_turma = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $param_id_turma);
            $param_id_turma = $id_turma;
            
            if (mysqli_stmt_execute($stmt_delete)) {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_turmas.php?sucesso=excluido");
                exit();
            } else {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_turmas.php?erro=exclusao");
                exit();
            }
        }
    }
    
} else {
    header("location: gerenciar_turmas.php?erro=id_faltando");
    exit();
}

mysqli_close($link);
?>