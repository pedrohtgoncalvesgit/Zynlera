<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$mensagem_sucesso = $erro_geral = "";
if(isset($_GET['sucesso'])){
    $mensagem_sucesso = "Notas atualizadas com sucesso!";
}
if(isset($_GET['erro'])){
    $erro_geral = urldecode($_GET['erro']);
}


// 1. Validar IDs
if (!isset($_GET['id_vinculo']) || empty(trim($_GET['id_vinculo']))) {
    header("location: corrigir_notas.php"); // Volta para a seleção de turma/disciplina
    exit();
}
$id_vinculo = trim($_GET['id_vinculo']);

// 2. Obter Informações do Cabeçalho (Turma, Disciplina, etc.)
$info_header = [];
$sql_info = "SELECT dt.id_turma, d.nome_disciplina, d.codigo_disciplina, t.nome_turma, c.nome_curso, t.ano, t.semestre 
             FROM disciplinas_turmas dt 
             JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina 
             JOIN turmas t ON dt.id_turma = t.id_turma 
             JOIN cursos c ON t.id_curso = c.id_curso 
             WHERE dt.id_disc_turma = ?";
if ($stmt_info = mysqli_prepare($link, $sql_info)) {
    mysqli_stmt_bind_param($stmt_info, "i", $id_vinculo);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);
    if (mysqli_num_rows($result_info) == 1) {
        $info_header = mysqli_fetch_assoc($result_info);
    } else {
        // Se o vínculo não for encontrado, volta para a seleção
        header("location: corrigir_notas.php?erro=vinculo_invalido"); 
        exit();
    }
    mysqli_stmt_close($stmt_info);
} else {
    // Erro na consulta de informações, redireciona com erro
     header("location: corrigir_notas.php?erro=db_error"); 
     exit();
}
$id_turma = $info_header['id_turma'];

// 3. Lógica de ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $notas_post = $_POST['notas'] ?? [];
    mysqli_begin_transaction($link);
    try {
        // Prepara fora do loop para reutilizar
        $sql_update = "UPDATE notas SET valor = ? WHERE id_nota = ?";
        $stmt_update = mysqli_prepare($link, $sql_update);
        
        $sql_insert = "INSERT INTO notas (id_avaliacao, id_aluno, valor) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($link, $sql_insert);

        foreach ($notas_post as $id_aluno => $avaliacoes) {
            foreach ($avaliacoes as $id_avaliacao => $dados) {
                // Limpa o valor para verificar se está vazio ou não
                $valor_nota_post = isset($dados['valor']) ? trim($dados['valor']) : '';

                // Se id_nota existe E valor foi enviado (mesmo que vazio para apagar)
                if (isset($dados['id_nota']) && $dados['id_nota'] != '' && isset($dados['valor'])) {
                     if ($valor_nota_post === '') {
                         // Se o valor está vazio, pode ser para apagar a nota (opcional, depende da regra)
                         // $sql_delete = "DELETE FROM notas WHERE id_nota = ?";
                         // $stmt_delete = mysqli_prepare($link, $sql_delete);
                         // mysqli_stmt_bind_param($stmt_delete, "i", $dados['id_nota']);
                         // mysqli_stmt_execute($stmt_delete);
                         // mysqli_stmt_close($stmt_delete);
                         
                         // Ou apenas atualiza para NULL ou 0, dependendo do DB
                         $valor_db = null; // Ou 0.0
                         mysqli_stmt_bind_param($stmt_update, "di", $valor_db, $dados['id_nota']);
                         mysqli_stmt_execute($stmt_update);
                     } else {
                         // Atualiza nota existente com o novo valor
                         mysqli_stmt_bind_param($stmt_update, "di", $valor_nota_post, $dados['id_nota']);
                         mysqli_stmt_execute($stmt_update);
                     }
                } 
                // Se id_nota NÃO existe, mas um valor foi enviado (não vazio) -> INSERIR
                elseif ($valor_nota_post !== '') { 
                    mysqli_stmt_bind_param($stmt_insert, "iid", $id_avaliacao, $id_aluno, $valor_nota_post);
                    mysqli_stmt_execute($stmt_insert);
                }
            }
        }
        mysqli_stmt_close($stmt_update); // Fecha statements preparados
        mysqli_stmt_close($stmt_insert);
        mysqli_commit($link);
        header("location: " . $_SERVER['PHP_SELF'] . "?id_vinculo=$id_vinculo&sucesso=1");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        $erro_geral = "Erro ao atualizar as notas: " . $e->getMessage();
        // Fecha statements em caso de erro também, se foram abertos
        if (isset($stmt_update)) mysqli_stmt_close($stmt_update);
        if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
    }
}

// 4. Buscar as Avaliações da Disciplina
$avaliacoes = [];
$sql_avaliacoes = "SELECT id_avaliacao, titulo FROM avaliacoes WHERE id_disc_turma = ? ORDER BY data_avaliacao, titulo";
if ($stmt_avaliacoes = mysqli_prepare($link, $sql_avaliacoes)) {
    mysqli_stmt_bind_param($stmt_avaliacoes, "i", $id_vinculo);
    mysqli_stmt_execute($stmt_avaliacoes);
    $result_avaliacoes = mysqli_stmt_get_result($stmt_avaliacoes);
    while ($row = mysqli_fetch_assoc($result_avaliacoes)) {
        $avaliacoes[] = $row;
    }
    mysqli_stmt_close($stmt_avaliacoes);
}

// 5. Buscar Alunos e suas respectivas notas e faltas
$alunos_data = [];
$sql_alunos = "SELECT a.id_aluno, u.nome_completo, a.matricula 
               FROM matriculas m 
               JOIN alunos a ON m.id_aluno = a.id_aluno 
               JOIN usuarios u ON a.id_usuario = u.id_usuario 
               WHERE m.id_turma = ? AND m.situacao = 'ativa' 
               ORDER BY u.nome_completo";
if ($stmt_alunos = mysqli_prepare($link, $sql_alunos)) {
    mysqli_stmt_bind_param($stmt_alunos, "i", $id_turma);
    mysqli_stmt_execute($stmt_alunos);
    $result_alunos = mysqli_stmt_get_result($stmt_alunos);
    while ($aluno = mysqli_fetch_assoc($result_alunos)) {
        $id_aluno = $aluno['id_aluno'];
        $alunos_data[$id_aluno] = $aluno;
        $alunos_data[$id_aluno]['notas'] = [];

        // Buscar notas para este aluno NAS avaliações DESTA disciplina/turma
        $sql_notas = "SELECT n.id_nota, n.id_avaliacao, n.valor 
                      FROM notas n
                      JOIN avaliacoes av ON n.id_avaliacao = av.id_avaliacao
                      WHERE n.id_aluno = ? AND av.id_disc_turma = ?"; // Garante que são notas desta disciplina/turma
        if($stmt_notas = mysqli_prepare($link, $sql_notas)){
            mysqli_stmt_bind_param($stmt_notas, "ii", $id_aluno, $id_vinculo);
            mysqli_stmt_execute($stmt_notas);
            $result_notas = mysqli_stmt_get_result($stmt_notas);
            while($nota = mysqli_fetch_assoc($result_notas)){
                $alunos_data[$id_aluno]['notas'][$nota['id_avaliacao']] = $nota;
            }
            mysqli_stmt_close($stmt_notas);
        }

        // Buscar total de faltas
        $sql_faltas = "SELECT COUNT(id_frequencia) as total_faltas 
                       FROM frequencias f 
                       JOIN aulas a ON f.id_aula = a.id_aula 
                       WHERE f.id_aluno = ? AND a.id_disc_turma = ? AND f.status = 'Falta'";
        if($stmt_faltas = mysqli_prepare($link, $sql_faltas)){
            mysqli_stmt_bind_param($stmt_faltas, "ii", $id_aluno, $id_vinculo);
            mysqli_stmt_execute($stmt_faltas);
            $result_faltas = mysqli_stmt_get_result($stmt_faltas);
            $faltas_data = mysqli_fetch_assoc($result_faltas);
            $alunos_data[$id_aluno]['total_faltas'] = $faltas_data['total_faltas'] ?? 0;
            mysqli_stmt_close($stmt_faltas);
        } else {
             $alunos_data[$id_aluno]['total_faltas'] = '?'; // Indica erro ao buscar faltas
        }
    }
    mysqli_stmt_close($stmt_alunos);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Notas e Faltas</title>
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
            flex: 1; padding: 30px; max-width: 1400px; /* Mais largo para tabelas */
            margin: 20px auto; width: 100%; box-sizing: border-box;
        }
        
        .page-title { font-size: 1.8em; color: #0056b3; margin-bottom: 20px; }
        
        .turma-info-box {
             background-color: #e2f3ff; border-left: 5px solid #007bff;
             padding: 15px 20px; margin-bottom: 30px; border-radius: 5px;
        }
        .turma-info-box h3 { margin-top: 0; color: #0056b3; font-size: 1.3em;}
        .turma-info-box p { margin-bottom: 0; color: #333; }

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
            overflow: hidden; /* Garante bordas arredondadas */
            table-layout: fixed; /* Ajuda a controlar largura das colunas */
        }
        .data-table th, .data-table td {
            border: 1px solid #e0e0e0; padding: 10px; text-align: left;
            vertical-align: middle;
            word-wrap: break-word; /* Quebra palavras longas */
        }
        .data-table thead th {
            background-color: #f0f8ff; color: #333; font-weight: 600;
            text-transform: uppercase; font-size: 0.9em;
            text-align: center; /* Centraliza títulos das colunas */
            white-space: normal; /* Permite quebra de linha nos títulos */
        }
         .data-table th.aluno-col { width: 25%; text-align: left;} /* Coluna Aluno mais larga e alinhada à esquerda */
         .data-table th.nota-col { width: 10%; } /* Largura fixa para notas */
         .data-table th.faltas-col { width: 15%; } /* Largura para faltas */

        .data-table tbody tr:nth-child(even) { background-color: #fdfdfd; }
        .data-table tbody tr:hover { background-color: #eef7ff; }
        
        .data-table td { text-align: center; } /* Centraliza conteúdo das células */
        .data-table td:first-child { text-align: left; } /* Alinha nome do aluno à esquerda */

        .aluno-nome { font-weight: 500; }
        .aluno-matricula { font-size: 0.9em; color: #666; }

        .form-control {
            width: 90%; /* Não ocupa 100% para ter respiro */
            padding: 8px; box-sizing: border-box; margin: 0 auto; /* Centraliza */
            border: 1px solid #ccc; border-radius: 6px; text-align: center;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
        }

        .btn {
            padding: 8px 15px; text-decoration: none; border-radius: 6px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-sm { font-size: 0.8em; padding: 6px 12px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-success { background-color: #28a745; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        
        .button-container { text-align: right; margin-top: 20px; } /* Alinha botão Salvar à direita */

        .alert-danger {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda; color: #155724; padding: 15px;
            border: 1px solid #c3e6cb; border-radius: 8px; margin-bottom: 20px;
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
         <a href="corrigir_notas.php?id_turma=<?php echo $id_turma; ?>" class="btn btn-secondary" style="margin-bottom: 15px;"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        <h1 class="page-title">Correção de Notas e Faltas</h1>

        <?php if(!empty($mensagem_sucesso)): ?>
            <div class="alert-success"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if(!empty($erro_geral)): ?>
            <div class="alert-danger"><?php echo $erro_geral; ?></div>
        <?php endif; ?>

        <div class="turma-info-box">
             <h3><?php echo htmlspecialchars($info_header['codigo_disciplina'] . " - " . $info_header['nome_disciplina']); ?></h3>
             <p>Turma: <?php echo htmlspecialchars($info_header['nome_turma']); ?> (<?php echo htmlspecialchars($info_header['nome_curso']); ?>) - <?php echo htmlspecialchars($info_header['ano'] . '/' . $info_header['semestre']); ?></p>
        </div>

        <div class="content-card">
             <h2><i class="fa-solid fa-pen-ruler"></i> Notas e Faltas dos Alunos</h2>
             <form action="" method="post">
                 <div style="overflow-x: auto;"> <table class="data-table">
                        <thead>
                            <tr>
                                <th class="aluno-col">Aluno</th>
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <th class="nota-col"><?php echo htmlspecialchars($avaliacao['titulo']); ?></th>
                                <?php endforeach; ?>
                                <th class="faltas-col">Faltas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_data as $id_aluno => $aluno): ?>
                                <tr>
                                    <td>
                                        <div class="aluno-nome"><?php echo htmlspecialchars($aluno['nome_completo']); ?></div>
                                        <div class="aluno-matricula"><?php echo htmlspecialchars($aluno['matricula']); ?></div>
                                    </td>
                                    <?php foreach ($avaliacoes as $avaliacao): 
                                        $id_avaliacao = $avaliacao['id_avaliacao'];
                                        $nota_info = $aluno['notas'][$id_avaliacao] ?? null;
                                        // Formata para exibição com vírgula, mas usa ponto no value
                                        $valor_nota = isset($nota_info['valor']) ? number_format($nota_info['valor'], 1, '.', '') : ''; 
                                        $id_nota = $nota_info['id_nota'] ?? '';
                                    ?>
                                        <td>
                                            <input type="hidden" name="notas[<?php echo $id_aluno; ?>][<?php echo $id_avaliacao; ?>][id_nota]" value="<?php echo $id_nota; ?>">
                                            <input type="number" name="notas[<?php echo $id_aluno; ?>][<?php echo $id_avaliacao; ?>][valor]" value="<?php echo htmlspecialchars($valor_nota); ?>" step="0.1" min="0" max="10" class="form-control" placeholder="-">
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <?php echo $aluno['total_faltas']; ?>
                                        <a href="gerenciar_frequencia_aluno.php?id_aluno=<?php echo $id_aluno; ?>&id_vinculo=<?php echo $id_vinculo; ?>" class="btn btn-secondary btn-sm" style="margin-left: 10px;">
                                           <i class="fa-solid fa-list-check"></i> Gerenciar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                             <?php if (empty($alunos_data)): ?>
                                 <tr>
                                     <td colspan="<?php echo count($avaliacoes) + 2; ?>" style="text-align: center;">Nenhum aluno ativo matriculado nesta turma.</td>
                                 </tr>
                             <?php endif; ?>
                        </tbody>
                    </table>
                 </div>
                 <?php if (!empty($alunos_data)): // Mostra botão salvar apenas se houver alunos ?>
                 <div class="button-container">
                     <button type="submit" class="btn btn-success"><i class="fa-solid fa-save"></i> Salvar Todas as Alterações</button>
                 </div>
                 <?php endif; ?>
             </form>
        </div>
    </main>

</body>
</html>