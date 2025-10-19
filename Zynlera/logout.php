<?php
// Inclui o arquivo de sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("location: login.php");
exit;
?>