<?php
require_once 'config.php';

// Tenta estabelecer a conexão com o banco de dados
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if($link === false){
    die("ERRO: Não foi possível conectar ao banco de dados. " . mysqli_connect_error());
}

// Configura o charset para evitar problemas com acentuação
mysqli_set_charset($link, "utf8mb4");


?>