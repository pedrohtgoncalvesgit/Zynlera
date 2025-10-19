<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_disc_turma = $id_turma = $id_professor = "";
$disciplina_nome = $professor_atual_nome = "";
$erro_geral = "";

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_disc_turma = trim($_GET["id"]);

    // Buscar dados da associação
    $sql = "SELECT dt.id_turma, d.nome_disciplina, dt.id_professor FROM disciplinas_turmas dt JOIN disciplinas d ON dt.id_disciplina = d.id_disciplina WHERE dt.id_disc_turma = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_disc_turma);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_turma = $row['id_turma'];
                $disciplina_nome = $row['nome_disciplina'];
                $id_professor = $row['id_professor']; // ID do professor atualmente associado
            } else {
                $erro_geral = "Nenhum registro encontrado.";
            }
        } else {
            $erro_geral = "Erro ao executar a consulta.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $erro_geral = "Erro ao preparar a consulta.";
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST"){ // Apenas redireciona se não for POST e não tiver ID
    header("location: gerenciar_turmas.php");
    exit();
}

// Buscar todos os professores para o dropdown
$sql_professores = "SELECT p.id_professor, u.nome_completo FROM professores p JOIN usuarios u ON p.id_usuario = u.id_usuario ORDER BY u.nome_completo";
$result_professores = mysqli_query($link, $sql_professores);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // É importante pegar o id_disc_turma do POST ou GET, dependendo de como você o envia
    // Vamos assumir que ele está na URL, então usamos o $id_disc_turma já definido.
     if (!isset($id_disc_turma) || empty($id_disc_turma)) {
         // Se não veio pela URL e nem pelo POST (se tivesse campo hidden), erro.
         // Mas como pegamos no GET antes, essa condição é só segurança extra.
         $erro_geral = "ID da associação não encontrado.";
     }
    elseif (isset($_POST['id_professor']) && !empty($_POST['id_professor'])) {
        $novo_id_professor = $_POST['id_professor'];
        // Pegar id_turma do POST (campo hidden adicionado)
        $id_turma = $_POST['id_turma'];

        $sql_update = "UPDATE disciplinas_turmas SET id_professor = ? WHERE id_disc_turma = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ii", $novo_id_professor, $id_disc_turma);
            if (mysqli_stmt_execute($stmt_update)) {
                // Redireciona de volta para a página de gerenciamento da turma específica
                header("location: gerenciar_disciplinas_turma.php?id=" . $id_turma . "&sucesso=edit_prof"); // Adiciona um parâmetro de sucesso se desejar
                exit();
            } else {
                $erro_geral = "Erro ao atualizar o professor.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
             $erro_geral = "Erro ao preparar a atualização: " . mysqli_error($link);
        }
    } else {
        $erro_geral = "Por favor, selecione um professor.";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Professor da Disciplina</title>
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
            flex: 1; padding: 30px; max-width: 700px; /* Reduzido para formulário menor */
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

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: #555;
        }
        .form-control {
            width: 100%; padding: 12px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: #fff; /* Fundo branco padrão */
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
        }
        /* Estilo para campo desabilitado */
         .form-control:disabled {
            background-color: #e9ecef; /* Cor de fundo cinza claro */
            opacity: 0.7; /* Leve transparência */
            cursor: not-allowed;
         }


        .form-error {
            color: #dc3545; font-size: 0.85em; display: block; margin-top: 5px;
        }
        
        .alert-geral {
            background-color: #f8d7da; color: #721c24; padding: 15px;
            border: 1px solid #f5c6cb; border-radius: 8px; margin-bottom: 20px;
        }

        .button-group {
            margin-top: 30px; display: flex; gap: 15px; border-top: 2px solid #eee;
            padding-top: 25px;
        }

        .btn {
            padding: 12px 25px; text-decoration: none; border-radius: 8px;
            font-weight: 500; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Área do admin</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-pen-to-square"></i> Editar Professor da Disciplina</h2>

            <?php if(!empty($erro_geral)): ?>
                <div class="alert-geral"><?php echo $erro_geral; ?></div>
            <?php endif; ?>

            <?php if (!empty($disciplina_nome)): // Só mostra o form se a disciplina foi encontrada ?>
            <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
                <input type="hidden" name="id_turma" value="<?php echo $id_turma; ?>">
                 <div class="form-group">
                    <label>Disciplina</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($disciplina_nome); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Professor Responsável</label>
                    <select name="id_professor" class="form-control">
                        <option value="">Selecione um professor...</option>
                        <?php 
                        // Garante que o ponteiro está no início caso já tenha sido usado
                        if ($result_professores) mysqli_data_seek($result_professores, 0); 
                        while($professor = mysqli_fetch_assoc($result_professores)): ?>
                            <option value="<?php echo $professor['id_professor']; ?>" <?php echo ($id_professor == $professor['id_professor']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($professor['nome_completo']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Alteração</button>
                    <a href="gerenciar_disciplinas_turma.php?id=<?php echo $id_turma; ?>" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
             <?php else: ?>
                 <?php if(empty($erro_geral)) { echo '<div class="alert-geral">Dados da associação não encontrados.</div>'; } ?>
                  <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
             <?php endif; ?>
        </div>
    </main>

</body>
</html>
<?php mysqli_close($link); ?>