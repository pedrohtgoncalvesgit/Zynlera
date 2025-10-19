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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Escolar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* --- Estilos Gerais --- */
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f4f7fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- Cabeçalho Superior --- */
        .main-header {
            background: linear-gradient(90deg, #0056b3, #007bff);
            color: white;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.5em;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info i {
            margin-right: 8px;
            font-size: 1.2em;
        }

        .user-info a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .user-info a:hover {
            opacity: 1;
        }
        
        /* --- Conteúdo Principal --- */
        .container {
            flex: 1;
            padding: 30px;
            max-width: 1200px;
            margin: 20px auto;
            width: 100%;
            box-sizing: border-box;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: left;
        }

        .welcome-box {
            background-color: #e7f5ff;
            border-left: 5px solid #007bff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .welcome-box h1 {
            margin: 0;
            font-size: 1.8em;
            color: #0056b3;
        }
        
        .dashboard-card h2 {
            font-size: 1.5em;
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .dashboard-card p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 30px;
        }
        
        /* --- Menu de Funcionalidades --- */
        .menu-section h3 {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .menu-button {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            font-size: 1.1em;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .menu-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .menu-button i {
            font-size: 1.5em;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        
        /* Cores dos Botões */
        .btn-green { background-color: #28a745; }
        .btn-orange { background-color: #fd7e14; }
        .btn-purple { background-color: #6f42c1; }
        .btn-blue { background-color: #17a2b8; }
        .btn-red { background-color: #dc3545; }
        .btn-dark-blue { background-color: #007bff; }
        .btn-yellow { background-color: #ffc107; color: #333 !important; }

        /* --- Link de Logout --- */
        .logout-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .logout-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .logout-button:hover {
            background-color: #5a6268;
        }

    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Zynlera</div>
        <div class="user-info">
            <i class="fa-solid fa-user-circle"></i>
            <span><?php echo htmlspecialchars($nome); ?> (<?php echo htmlspecialchars($papel); ?>)</span>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </div>
    </header>

    <main class="container">
        <div class="dashboard-card">
            <div class="welcome-box">
                <h1>Bem-vindo, <?php echo htmlspecialchars($nome); ?>!</h1>
            </div>

            <h2>Dashboard de <?php echo htmlspecialchars($papel); ?></h2>
            <p>Este é o ponto de acesso inicial. As suas funcionalidades específicas estarão listadas abaixo.</p>

            <section class="menu-section">
                <?php 
                if (is_role('Administrador')) {
                    echo '<h3>Funcionalidades de Administrador:</h3>';
                    echo '<div class="menu-grid">';
                    echo '<a href="Admin/gerenciar_alunos.php" class="menu-button btn-dark-blue"><i class="fa-solid fa-users"></i> Gerenciar Alunos</a>';
                    echo '<a href="Admin/gerenciar_professores.php" class="menu-button btn-purple"><i class="fa-solid fa-chalkboard-user"></i> Gerenciar Professores</a>';
                    echo '<a href="Admin/gerenciar_cursos.php" class="menu-button btn-green"><i class="fa-solid fa-graduation-cap"></i> Gerenciar Cursos</a>';
                    echo '<a href="Admin/gerenciar_turmas.php" class="menu-button btn-orange"><i class="fa-solid fa-book-open"></i> Gerenciar Turmas e Disciplinas</a>';
                    echo '<a href="Admin/corrigir_notas.php" class="menu-button btn-red"><i class="fa-solid fa-marker"></i> Corrigir Notas/Faltas</a>';
                    echo '<a href="relatorios.php" class="menu-button btn-blue"><i class="fa-solid fa-chart-line"></i> Visualizar Relatórios</a>';
                    echo '</div>';
                } elseif (is_role('Professor')) {
                    echo '<h3>Funcionalidades de Professor:</h3>';
                    echo '<div class="menu-grid">';
                    echo '<a href="Professor/gerenciar_turmas.php" class="menu-button btn-green"><i class="fa-solid fa-school"></i> Visualizar Turmas e Lançar Notas</a>';
                    echo '<a href="relatorios.php?nivel=professor" class="menu-button btn-blue"><i class="fa-solid fa-file-invoice"></i> Gerar Relatórios</a>';
                    echo '</div>';
                } elseif (is_role('Aluno')) {
                    echo '<h3>Funcionalidades de Aluno:</h3>';
                    echo '<div class="menu-grid">';
                    echo '<a href="aluno/visualizar_informacoes.php" class="menu-button btn-dark-blue"><i class="fa-solid fa-graduation-cap"></i> Consultar Notas e Faltas</a>';
                    echo '<a href="aluno/solicitar_alteracao.php" class="menu-button btn-yellow"><i class="fa-solid fa-user-pen"></i> Solicitar Alteração de Dados</a>';
                    echo '</div>';
                } else {
                    echo '<p>Seu nível de acesso não está configurado corretamente. Contate o administrador.</p>';
                }
                ?>
            </section>
            
            <section class="logout-section">
                <a href="logout.php" class="logout-button">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair do Sistema
                </a>
            </section>
        </div>
    </main>

</body>
</html>