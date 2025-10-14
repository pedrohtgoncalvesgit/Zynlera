<?php
require_once 'sessao.php';

// Redireciona para o login se não estiver logado
if (!is_logged_in()) {
    header("location: login.php");
    exit;
}

$papel = get_user_role();
$nome = $_SESSION["nome_completo"];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistema Escolar</title>
    <style>
        body { font: 14px sans-serif; text-align: center; }
        .menu { margin-top: 20px; }
        .menu a { margin: 0 10px; text-decoration: none; padding: 10px; border: 1px solid #ccc; display: inline-block; }
    </style>
</head>
<body>
    <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
    <h2>Dashboard de <?php echo htmlspecialchars($papel); ?></h2>
    <p>Este é o ponto de acesso inicial. Suas funcionalidades específicas de <?php echo htmlspecialchars($papel); ?> estarão listadas abaixo.</p>

    <div class="menu">
        <?php 
        if (is_role('Administrador')) {
            // Requisitos Administrador [cite: 34] - Acesso total ao sistema. [cite: 13]
            echo '<h3>Funcionalidades de Administrador:</h3>';
            echo '<a href="admin/gerenciar_alunos.php">Gerenciar Alunos</a>'; // Cadastro e Gerenciamento de Alunos 
            echo '<a href="admin/gerenciar_professores.php">Gerenciar Professores</a>'; // Cadastro e Gerenciamento de Professores [cite: 40]
            echo '<a href="admin/gerenciar_turmas.php">Gerenciar Turmas e Disciplinas</a>'; // Gerenciamento de Turmas e Disciplinas [cite: 45]
            echo '<a href="admin/corrigir_notas.php">Corrigir Notas/Faltas</a>'; // Controle de Notas e Faltas [cite: 51]
            echo '<a href="relatorios.php">Visualizar Dashboards/Relatórios</a>'; // Dashboard [cite: 16, 17]
        } elseif (is_role('Professor')) {
            // Requisitos Professor [cite: 20] - Acesso restrito às turmas e disciplinas vinculadas. [cite: 14]
            echo '<h3>Funcionalidades de Professor:</h3>';
            echo '<a href="admin/visualizar_turmas.php">Visualizar Turmas Vinculadas</a>'; // Visualizar todas as turmas em que atuam. [cite: 22]
            echo '<a href="admin/gerenciar_turmas.php">Gerenciar Disciplinas</a>'; // Criar, editar e excluir disciplinas. [cite: 23]
            echo '<a href="admin/lancar_notas_faltas.php">Lançar e Controlar Notas/Faltas</a>'; // Lançamento e Controle de Notas e Faltas [cite: 27]
            echo '<a href="relatorios.php?nivel=professor">Gerar Relatórios</a>'; // Relatórios devem estar disponíveis por turma, disciplina e aluno. [cite: 33]
        } elseif (is_role('Aluno')) {
            // Requisitos Aluno [cite: 54] - Acesso apenas às suas próprias informações acadêmicas. [cite: 15]
            echo '<h3>Funcionalidades de Aluno:</h3>';
            echo '<a href="aluno/visualizar_informacoes.php">Consultar Notas e Faltas</a>'; // Visualização de Notas e Faltas [cite: 55]
            echo '<a href="aluno/solicitar_alteracao.php">Solicitar Alteração de Dados</a>'; // Alteração de Dados Pessoais [cite: 59]
        } else {
            echo '<p>Seu nível de acesso não está configurado corretamente. Contate o administrador.</p>';
        }
        ?>
    </div>
    
    <hr>
    <p><a href="logout.php">Sair (Logout)</a></p>
</body>
</html>