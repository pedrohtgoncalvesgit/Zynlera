<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    
    $id_curso = trim($_GET["id"]);
    $sucesso = false;

    // 1. Verificar dependências (Disciplinas ou Turmas)
    $sql_check_dep = "SELECT 
                        (SELECT COUNT(id_disciplina) FROM disciplinas WHERE id_curso = ?) AS total_disciplinas,
                        (SELECT COUNT(id_turma) FROM turmas WHERE id_curso = ?) AS total_turmas";

    $tem_dependencia = false;
    
    if ($stmt_dep = mysqli_prepare($link, $sql_check_dep)) {
        // Usamos o ID duas vezes no SELECT, então vinculamos duas vezes
        mysqli_stmt_bind_param($stmt_dep, "ii", $param_id_curso, $param_id_curso_2);
        $param_id_curso = $id_curso;
        $param_id_curso_2 = $id_curso;
        mysqli_stmt_execute($stmt_dep);
        
        $result = mysqli_stmt_get_result($stmt_dep);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_dep);
        
        if ($row['total_disciplinas'] > 0 || $row['total_turmas'] > 0) {
            $tem_dependencia = true;
        }
    } else {
        header("location: gerenciar_cursos.php?erro=verificacao_dep");
        exit();
    }
    
    if ($tem_dependencia) {
        // REQUISITO: Não é possível excluir, pois tem dependência.
        header("location: gerenciar_cursos.php?erro=dependencia");
        exit();
    } else {
        // Exclusão PERMITIDA (sem dependências)
        $sql_delete = "DELETE FROM cursos WHERE id_curso = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $param_id_curso);
            $param_id_curso = $id_curso;
            
            if (mysqli_stmt_execute($stmt_delete)) {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_cursos.php?sucesso=excluido");
                exit();
            } else {
                mysqli_stmt_close($stmt_delete);
                header("location: gerenciar_cursos.php?erro=exclusao");
                exit();
            }
        }
    }
    
} else {
    header("location: gerenciar_cursos.php?erro=id_faltando");
    exit();
}

mysqli_close($link);
?>