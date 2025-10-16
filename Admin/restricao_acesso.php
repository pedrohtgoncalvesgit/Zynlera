<?php
// Inclui a sessão (necessário para is_logged_in e is_role)
require_once '../sessao.php';

// Redireciona para o login se não estiver logado
if (!is_logged_in()) {
    header("location: ../login.php");
    exit;
}

// Verifica se o usuário é Administrador
if (!is_role('Administrador')) {
    // Se não for Administrador, redireciona para o dashboard ou exibe erro
    header("location: ../dashboard.php?erro=acesso_negado");
    exit;
}
?>