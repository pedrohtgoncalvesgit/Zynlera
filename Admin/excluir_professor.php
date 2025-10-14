<?php
require_once 'restrição_acesso.php';
require_once '../conexão.php';


if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    
    $id_professor = trim($_GET["id"]);
    $sucesso = false;

    // 1. Verificar se o professor tem vínculos em disciplinas_turmas
    $sql_check_vinculo = "SELECT COUNT(id_disc_turma) FROM disciplinas_turmas WHERE id_professor = ?";

    $tem_vinculo = false;
    
    if ($stmt_vinculo = mysqli_prepare($link, $sql_check_vinculo)) {
        mysqli_stmt_bind_param($stmt_vinculo, "i", $param_id_professor);
        $param_id_professor = $id_professor;
        mysqli_stmt_execute($stmt_vinculo);
        mysqli_stmt_bind_result($stmt_vinculo, $count_vinculo);
        mysqli_stmt_fetch($stmt_vinculo);
        mysqli_stmt_close($stmt_vinculo);
        if ($count_vinculo > 0) {
            $tem_vinculo = true;
        }
    }
    
    // Inicia a transação
    mysqli_begin_transaction($link);
    $sucesso = true;

    if ($tem_vinculo) {
        // REQUISITO: Marcar como inativo se houver vínculo.
        
        // 2.A. Atualiza o status 'ativo' na tabela 'usuarios' para 0 (Inativo)
        // O campo 'ativo' na tabela 'professores' não existe no script fornecido.
        // Iremos inativar apenas na tabela 'usuarios'.

        $sql_get_user_id = "SELECT id_usuario FROM professores WHERE id_professor = ?";
        $id_usuario_to_deactivate = null;
        if($stmt_get_user = mysqli_prepare($link, $sql_get_user_id)) {
            mysqli_stmt_bind_param($stmt_get_user, "i", $id_professor);
            mysqli_stmt_execute($stmt_get_user);
            mysqli_stmt_bind_result($stmt_get_user, $id_usuario_to_deactivate);
            mysqli_stmt_fetch($stmt_get_user);
            mysqli_stmt_close($stmt_get_user);
        }
        
        if ($sucesso && $id_usuario_to_deactivate) {
            $sql_update_usuario = "UPDATE usuarios SET ativo = 0 WHERE id_usuario = ?";
            if ($stmt_user = mysqli_prepare($link, $sql_update_usuario)) {
                mysqli_stmt_bind_param($stmt_user, "i", $id_usuario_to_deactivate);
                if (!mysqli_stmt_execute($stmt_user)) {
                    $sucesso = false;
                }
                mysqli_stmt_close($stmt_user);
            } else { $sucesso = false; }
        }

        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_professores.php?sucesso=inativado");
            exit();
        } else {
            mysqli_rollback($link);
            header("location: gerenciar_professores.php?erro=inativacao");
            exit();
        }
        
    } else {
        // 2.B. Exclusão PERMITIDA (sem vínculo)

        $sql_get_user_id = "SELECT id_usuario FROM professores WHERE id_professor = ?";
        $id_usuario_to_delete = null;
        if($stmt_get_user = mysqli_prepare($link, $sql_get_user_id)) {
            mysqli_stmt_bind_param($stmt_get_user, "i", $id_professor);
            mysqli_stmt_execute($stmt_get_user);
            mysqli_stmt_bind_result($stmt_get_user, $id_usuario_to_delete);
            mysqli_stmt_fetch($stmt_get_user);
            mysqli_stmt_close($stmt_get_user);
        }
        
        // Exclui de 'professores'
        $sql_delete_professor = "DELETE FROM professores WHERE id_professor = ?";
        if ($stmt_professor = mysqli_prepare($link, $sql_delete_professor)) {
            mysqli_stmt_bind_param($stmt_professor, "i", $param_id_professor);
            if (!mysqli_stmt_execute($stmt_professor)) {
                $sucesso = false;
            }
            mysqli_stmt_close($stmt_professor);
        } else { $sucesso = false; }
        
        // Exclui de 'usuarios'
        if ($sucesso && $id_usuario_to_delete) {
            $sql_delete_usuario = "DELETE FROM usuarios WHERE id_usuario = ?";
            if ($stmt_user = mysqli_prepare($link, $sql_delete_usuario)) {
                mysqli_stmt_bind_param($stmt_user, "i", $id_usuario_to_delete);
                if (!mysqli_stmt_execute($stmt_user)) {
                    $sucesso = false;
                }
                mysqli_stmt_close($stmt_user);
            } else { $sucesso = false; }
        }

        if ($sucesso) {
            mysqli_commit($link);
            header("location: gerenciar_professores.php?sucesso=excluido");
            exit();
        } else {
            mysqli_rollback($link);
            header("location: gerenciar_professores.php?erro=exclusao");
            exit();
        }
    }
    
} else {
    header("location: gerenciar_professores.php?erro=id_faltando");
    exit();
}

mysqli_close($link);
?>