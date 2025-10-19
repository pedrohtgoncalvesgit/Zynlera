<?php
require_once 'restricao_acesso.php'; // Assume que é um admin ou professor acessando
require_once '../conexão.php';

$erro_geral = ""; // Inicializa a variável de erro geral
$mensagem_sucesso = ""; // Inicializa a variável de sucesso

// 1. Validar IDs
if (!isset($_GET['id_aluno']) || empty(trim($_GET['id_aluno'])) || !isset($_GET['id_vinculo']) || empty(trim($_GET['id_vinculo']))) {
    // Se faltar algum ID, redireciona para a seleção inicial
    header("location: corrigir_notas.php?erro=" . urlencode("IDs inválidos para gerenciar frequência."));
    exit();
}
$id_aluno = trim($_GET['id_aluno']);
$id_vinculo = trim($_GET['id_vinculo']); // id_disc_turma

// Tratar mensagem de sucesso via GET
if(isset($_GET['sucesso']) && $_GET['sucesso'] == 1){
    $mensagem_sucesso = "Frequências atualizadas com sucesso!";
}


// 2. Lógica de ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $frequencias_post = $_POST['frequencias'] ?? [];
    mysqli_begin_transaction($link);
    try {
        // Prepara statements fora do loop para reutilização
        $sql_select = "SELECT id_frequencia FROM frequencias WHERE id_aula = ? AND id_aluno = ?";
        $sql_update = "UPDATE frequencias SET status = ? WHERE id_frequencia = ?";
        $sql_insert = "INSERT INTO frequencias (id_aula, id_aluno, status) VALUES (?, ?, ?)";

        $stmt_select = mysqli_prepare($link, $sql_select);
        $stmt_update = mysqli_prepare($link, $sql_update);
        $stmt_insert = mysqli_prepare($link, $sql_insert);

        foreach ($frequencias_post as $id_aula => $status) {
            // Pula se o status estiver vazio ou for inválido (pode adicionar mais validações)
            if (empty($status) || !in_array($status, ['Presente', 'Falta', 'Justificada'])) continue;

            // Verifica se o registro já existe
            mysqli_stmt_bind_param($stmt_select, "ii", $id_aula, $id_aluno);
            mysqli_stmt_execute($stmt_select);
            $result = mysqli_stmt_get_result($stmt_select);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Existe: Atualiza
                $id_frequencia = $row['id_frequencia'];
                mysqli_stmt_bind_param($stmt_update, "si", $status, $id_frequencia);
                mysqli_stmt_execute($stmt_update);
            } else {
                // Não existe: Insere
                mysqli_stmt_bind_param($stmt_insert, "iis", $id_aula, $id_aluno, $status);
                mysqli_stmt_execute($stmt_insert);
            }
        }

        // Fecha statements
        mysqli_stmt_close($stmt_select);
        mysqli_stmt_close($stmt_update);
        mysqli_stmt_close($stmt_insert);

        mysqli_commit($link);
        // Redireciona para a mesma página com parâmetro de sucesso
        header("location: " . $_SERVER['PHP_SELF'] . "?id_aluno=$id_aluno&id_vinculo=$id_vinculo&sucesso=1");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        $erro_geral = "Erro ao atualizar as frequências: " . $e->getMessage();
        // Fecha statements em caso de erro também, se foram abertos
        if (isset($stmt_select)) mysqli_stmt_close($stmt_select);
        if (isset($stmt_update)) mysqli_stmt_close($stmt_update);
        if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
    }
}

// 3. Buscar dados do Aluno para o cabeçalho
$aluno_info = [];
$sql_aluno = "SELECT u.nome_completo, a.matricula 
              FROM usuarios u 
              JOIN alunos a ON u.id_usuario = a.id_usuario 
              WHERE a.id_aluno = ?";
if($stmt_aluno = mysqli_prepare($link, $sql_aluno)){
    mysqli_stmt_bind_param($stmt_aluno, "i", $id_aluno);
    if(mysqli_stmt_execute($stmt_aluno)){
        $result_aluno = mysqli_stmt_get_result($stmt_aluno);
        if($result_aluno && mysqli_num_rows($result_aluno) == 1){
            $aluno_info = mysqli_fetch_assoc($result_aluno);
        } else {
             $erro_geral .= " Aluno não encontrado."; // Adiciona ao erro geral
        }
    } else {
         $erro_geral .= " Erro ao buscar dados do aluno.";
    }
    mysqli_stmt_close($stmt_aluno);
} else {
     $erro_geral .= " Erro ao preparar busca do aluno.";
}

// 4. Buscar todas as aulas da disciplina e a frequência do aluno específico
$aulas = [];
$sql_aulas = "SELECT a.id_aula, a.data_aula, a.conteudo, f.status 
              FROM aulas a
              LEFT JOIN frequencias f ON a.id_aula = f.id_aula AND f.id_aluno = ?
              WHERE a.id_disc_turma = ? 
              ORDER BY a.data_aula ASC"; // Ordena da mais antiga para a mais recente
if ($stmt_aulas = mysqli_prepare($link, $sql_aulas)) {
    mysqli_stmt_bind_param($stmt_aulas, "ii", $id_aluno, $id_vinculo);
    if(mysqli_stmt_execute($stmt_aulas)){
        $result_aulas = mysqli_stmt_get_result($stmt_aulas);
        while ($row = mysqli_fetch_assoc($result_aulas)) {
            $aulas[] = $row;
        }
    } else {
         $erro_geral .= " Erro ao buscar aulas.";
    }
    mysqli_stmt_close($stmt_aulas);
} else {
     $erro_geral .= " Erro ao preparar busca de aulas.";
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Frequência do Aluno</title>
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
            flex: 1; padding: 30px; max-width: 1000px; /* Um pouco mais largo para tabela */
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }
        
        .page-title { font-size: 1.8em; color: #0056b3; margin-bottom: 20px; }

        .aluno-info-box {
             background-color: #e2f3ff; border-left: 5px solid #007bff;
             padding: 15px 20px; margin-bottom: 30px; border-radius: 5px;
        }
        .aluno-info-box h3 { margin-top: 0; color: #0056b3; font-size: 1.3em;}
        .aluno-info-box p { margin-bottom: 0; color: #333; }
        
        .content-card {
            background-color: white; border-radius: 10px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: left;
            margin-bottom: 30px;
        }

        .content-card h2 { /* Usado para o título dentro do card */
            font-size: 1.5em; color: #0056b3; margin-top: 0;
            margin-bottom: 20px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
         .content-card h2 i { margin-right: 15px; color: #007bff; }
        
        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-radius: 8px;
            overflow: hidden; 
        }
        .data-table th, .data-table td {
            border: 1px solid #e0e0e0; padding: 10px 15px; text-align: left;
            vertical-align: middle;
        }
        .data-table thead th {
            background-color: #f0f8ff; color: #333; font-weight: 600;
            text-transform: uppercase; font-size: 0.9em;
        }
         .data-table th:nth-child(1) { width: 20%; } /* Data */
         .data-table th:nth-child(2) { width: 50%; } /* Conteúdo */
         .data-table th:nth-child(3) { width: 30%; text-align: center;} /* Status */
         .data-table td:nth-child(3) { text-align: center; } /* Centraliza select */


        .data-table tbody tr:nth-child(even) { background-color: #fdfdfd; }
        .data-table tbody tr:hover { background-color: #eef7ff; }
        
        .form-control {
            width: 90%; 
            padding: 8px; box-sizing: border-box; margin: 0 auto; 
            border: 1px solid #ccc; border-radius: 6px; 
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
        }

         /* Estilos condicionais para o select baseado no valor */
        select.status-presente { border-left: 4px solid #28a745; }
        select.status-falta { border-left: 4px solid #dc3545; }
        select.status-justificada { border-left: 4px solid #ffc107; }

        .btn {
            padding: 12px 25px; text-decoration: none; border-radius: 8px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; margin-bottom: 15px;} /* Adicionado margin-bottom */
        
        .button-container { text-align: right; margin-top: 20px; } 

        .alert-danger {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
        }
         .no-data-message {
             background-color: #eef7ff; color: #0c5460; padding: 15px;
             border: 1px solid #bee5eb; border-radius: 8px; text-align: center;
             margin-top: 20px; font-size: 1.1em;
         }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
             <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
             <a href="corrigir_notas.php"><i class="fa-solid fa-list-check"></i> Corrigir Notas</a>
             <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
         <a href="editar_notas_final.php?id_vinculo=<?php echo $id_vinculo; ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        <h1 class="page-title">Gerenciar Frequência</h1>

        <?php if(!empty($aluno_info)): ?>
        <div class="aluno-info-box">
            <h3>Aluno: <?php echo htmlspecialchars($aluno_info['nome_completo']); ?></h3>
            <p>Matrícula: <?php echo htmlspecialchars($aluno_info['matricula']); ?></p>
        </div>
        <?php endif; ?>

        <?php if(!empty($mensagem_sucesso)): ?>
            <div class="alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>

        <div class="content-card">
             <h2><i class="fa-solid fa-calendar-days"></i> Registro de Presença nas Aulas</h2>
             <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id_aluno=' . $id_aluno . '&id_vinculo=' . $id_vinculo; ?>" method="post">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data da Aula</th>
                            <th>Conteúdo</th>
                            <th>Status da Presença</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($aulas)): ?>
                            <?php foreach ($aulas as $aula): 
                                $status_atual = $aula['status'] ?? 'Presente'; // Assume presente se nulo
                                $status_class = '';
                                if ($status_atual == 'Falta') $status_class = 'status-falta';
                                elseif ($status_atual == 'Justificada') $status_class = 'status-justificada';
                                else $status_class = 'status-presente';
                            ?>
                                <tr>
                                    <td><?php echo date("d/m/Y", strtotime($aula['data_aula'])); ?></td>
                                    <td><?php echo !empty($aula['conteudo']) ? htmlspecialchars($aula['conteudo']) : '<span style="color: #999;">Não informado</span>'; ?></td>
                                    <td>
                                        <select name="frequencias[<?php echo $aula['id_aula']; ?>]" class="form-control <?php echo $status_class; ?>">
                                            <option value="Presente" <?php echo ($status_atual == 'Presente') ? 'selected' : ''; ?>>Presente</option>
                                            <option value="Falta" <?php echo ($status_atual == 'Falta') ? 'selected' : ''; ?>>Falta</option>
                                            <option value="Justificada" <?php echo ($status_atual == 'Justificada') ? 'selected' : ''; ?>>Justificada</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center;">Nenhuma aula encontrada para esta disciplina/turma.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($aulas)): ?>
                <div class="button-container">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Alterações de Frequência</button>
                </div>
                <?php endif; ?>
             </form>
        </div>
    </main>
</body>
</html>