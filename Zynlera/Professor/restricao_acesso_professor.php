<?php
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['papel']) != 'professor') {
    echo "Acesso negado. Você não tem permissão para acessar esta página.";
    exit();
}
?>