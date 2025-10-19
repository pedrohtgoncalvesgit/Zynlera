<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_turma = $_GET['id_turma'] ?? null;
$id_vinculo = $_GET['id_vinculo'] ?? null; // Renomeado de id_disc_turma para clareza no contexto de edição de notas

// Se um vínculo foi selecionado (passo final), redireciona para a página de edição
if ($id_vinculo) {
    // Redireciona para a página específica de edição/correção de notas finais
    header("Location: editar_notas_final.php?id_vinculo=" . $id_vinculo); 
    exit();
}

$page_title = "Selecionar Turma para Corrigir Notas"; // Título inicial
$items = [];
$step = 1; // Controla a etapa (1 para turma, 2 para disciplina)

if ($id_turma) {
    // Etapa 2: Listar disciplinas da turma selecionada
    $step = 2;
    $page_title = "Selecionar Disciplina";
    $sql = "SELECT dt.id_disc_turma, d.nome_disciplina, u.nome_completo AS nome_professor 
            FROM disciplinas_turmas dt 
            JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina 
            JOIN professores p ON dt.id_professor = p.id_professor 
            JOIN usuarios u ON p.id_usuario = u.id_usuario 
            WHERE dt.id_turma = ? 
            ORDER BY d.nome_disciplina";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_turma);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'id' => $row['id_disc_turma'],
                'name' => $row['nome_disciplina'] . ' - Prof. ' . $row['nome_professor'], // Adicionado 'Prof.' para clareza
                'link' => 'corrigir_notas.php?id_vinculo=' . $row['id_disc_turma'] // Próximo passo é id_vinculo
            ];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Etapa 1: Listar todas as turmas
    $sql = "SELECT t.id_turma, t.nome_turma, c.nome_curso, t.ano, t.semestre 
            FROM turmas t
            JOIN cursos c ON t.id_curso = c.id_curso
            ORDER BY t.ano DESC, t.semestre DESC, t.nome_turma ASC";
    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['id_turma'],
            'name' => $row['nome_turma'] . ' (' . $row['nome_curso'] . ') - ' . $row['ano'] . '/' . $row['semestre'],
            'link' => 'corrigir_notas.php?id_turma=' . $row['id_turma'] // Próximo passo é id_turma
        ];
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif; margin: 0; background-color: #f4f7fa;
            color: #333; display: flex; flex-direction: column; min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(90deg, #0056b3, #007bff); color: white;
            padding: 10px 30px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo { font-size: 1.5em; font-weight: 700; }
        
        .main-nav a {
            color: white; text-decoration: none; margin-left: 20px;
            font-weight: 500; opacity: 0.9; transition: opacity 0.3s;
        }
        .main-nav a:hover { opacity: 1; }

        .container {
            flex: 1; padding: 30px; max-width: 900px; /* Largura ajustada para listas */
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }

        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
        }

        .content-card h2 {
            font-size: 1.8em; color: #0056b3; margin-top: 0;
            margin-bottom: 30px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }

        .selection-list {
            list-style: none; padding: 0; margin: 0;
        }
        .selection-list-item {
            border-bottom: 1px solid #eee; 
        }
         .selection-list-item:last-child {
             border-bottom: none;
         }

        .selection-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 10px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .selection-link:hover {
            background-color: #f8f9fa;
            color: #0056b3;
        }
        .selection-link i {
            color: #007bff;
            transition: transform 0.2s ease;
        }
         .selection-link:hover i {
             transform: translateX(5px);
         }

        .no-data-message {
             background-color: #eef7ff; color: #0c5460; padding: 15px;
             border: 1px solid #bee5eb; border-radius: 8px; text-align: center;
             margin-top: 20px; font-size: 1.1em;
         }

        .button-container {
             margin-top: 30px;
        }
        
        .btn-secondary {
            padding: 12px 25px; text-decoration: none; border-radius: 8px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            background-color: #6c757d; color: white;
        }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2>
                <?php if($step == 1): ?>
                    <i class="fa-solid fa-list-check"></i> <?php echo $page_title; ?>
                <?php else: ?>
                    <i class="fa-solid fa-book"></i> <?php echo $page_title; ?>
                <?php endif; ?>
            </h2>
            
            <ul class="selection-list">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <li class="selection-list-item">
                            <a href="<?php echo $item['link']; ?>" class="selection-link">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data-message">
                        <?php echo ($step == 1) ? 'Nenhuma turma encontrada.' : 'Nenhuma disciplina encontrada para esta turma.'; ?>
                    </div>
                <?php endif; ?>
            </ul>

             <div class="button-container">
                 <?php if($step == 2): // Mostra o botão Voltar apenas na etapa 2 ?>
                    <a href="corrigir_notas.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar para seleção de turmas</a>
                 <?php endif; ?>
             </div>
        </div>
    </main>

</body>
</html>