<?php
// Inicia a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Requisito: Sessões devem expirar após período de inatividade definido (ex.: 30 min). [cite: 12]
// Configuração de tempo de inatividade (30 minutos = 1800 segundos)
$tempo_inatividade = 1800; 

if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > $tempo_inatividade)) {
    // A última atividade foi há mais de 30 minutos, destrói a sessão
    session_unset();
    session_destroy();
    header("location: login.php?expirada=true");
    exit;
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

// Função para verificar se o usuário está logado
function is_logged_in() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Função para obter o papel do usuário (Administrador, Professor, Aluno)
function get_user_role() {
    return isset($_SESSION["papel"]) ? $_SESSION["papel"] : null;
}

// Função para verificar se o usuário tem o papel necessário
function is_role($role_name) {
    return get_user_role() === $role_name;
}
?>