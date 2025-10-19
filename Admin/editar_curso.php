<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_curso = $nome_curso = $descricao = "";
$nome_curso_err = $erro_geral = "";

// 1. Carregar dados
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_curso_param = trim($_GET["id"]);

    $sql = "SELECT id_curso, nome_curso, descricao FROM cursos WHERE id_curso = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_curso);
        $param_id_curso = $id_curso_param;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_curso = $row["id_curso"];
                $nome_curso = $row["nome_curso"];
                $descricao = $row["descricao"];
            } else {
                header("location: gerenciar_cursos.php?erro=nao_encontrado");
                exit();
            }
        } else {
             $erro_geral = "Erro ao buscar dados do curso."; // Mensagem de erro mais específica
        }
        mysqli_stmt_close($stmt);
    } else {
         $erro_geral = "Erro ao preparar a busca.";
    }
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") { // Só redireciona se não for POST e não tiver ID
    header("location: gerenciar_cursos.php?erro=id_faltando");
    exit();
}


// 2. Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // É crucial pegar o id_curso do campo hidden no POST
    if(isset($_POST["id_curso"])) {
        $id_curso = $_POST["id_curso"];
    } else {
        // Se o ID não veio no POST, algo está errado
        $erro_geral = "ID do curso não encontrado no formulário.";
        // Você pode querer parar aqui ou tentar pegar do GET como fallback, mas o hidden é mais seguro
        // $id_curso = isset($_GET["id"]) ? trim($_GET["id"]) : ''; // Fallback (menos seguro)
    }


    if (empty(trim($_POST["nome_curso"]))) {
        $nome_curso_err = "Insira o nome do curso.";
    } else {
        $nome_curso = trim($_POST["nome_curso"]);
    }
    $descricao = trim($_POST["descricao"]);

    // Recarregar os dados atuais se houver erro para exibição correta no form
    if (!empty($nome_curso_err) || !empty($erro_geral)) {
         // Se o erro foi na validação, $nome_curso e $descricao já estão com os valores do POST
         // Se o erro foi no DB, mas o ID veio, busca novamente
         if (empty($nome_curso_err) && !empty($id_curso)) {
             $sql_reload = "SELECT nome_curso, descricao FROM cursos WHERE id_curso = ?";
             if ($stmt_reload = mysqli_prepare($link, $sql_reload)) {
                 mysqli_stmt_bind_param($stmt_reload, "i", $id_curso);
                 if (mysqli_stmt_execute($stmt_reload)) {
                     $result_reload = mysqli_stmt_get_result($stmt_reload);
                     if ($row_reload = mysqli_fetch_assoc($result_reload)) {
                         // Mantém o nome do POST se ele era válido, mas usa a descrição original
                         // Isso evita perder o nome digitado se o erro foi no DB
                          if(empty($nome_curso_err)) $nome_curso = $_POST["nome_curso"]; // Mantem o nome digitado
                          $descricao = $_POST["descricao"]; // Mantem a descricao digitada
                     }
                 }
                 mysqli_stmt_close($stmt_reload);
             }
         }
    }
    // Prossegue para o DB apenas se não houver erro de validação e o ID existir
    elseif (!empty($id_curso) && empty($nome_curso_err)) {
        
        $sql = "UPDATE cursos SET nome_curso = ?, descricao = ? WHERE id_curso = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $param_nome, $param_descricao, $param_id_curso);
            $param_nome = $nome_curso;
            $param_descricao = $descricao;
            $param_id_curso = $id_curso;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_cursos.php?sucesso=edicao");
                exit;
            } else {
                $erro_geral = "Erro ao atualizar curso (Nome duplicado?): " . mysqli_error($link);
                // É importante recarregar os dados aqui também para exibir o erro com os campos preenchidos
                $nome_curso = $_POST["nome_curso"];
                $descricao = $_POST["descricao"];
            }
            mysqli_stmt_close($stmt);
        } else {
            $erro_geral = "Erro de preparação: " . mysqli_error($link);
            // Recarrega dados
            $nome_curso = $_POST["nome_curso"];
            $descricao = $_POST["descricao"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Curso - Administrador</title>
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
            flex: 1; padding: 30px; max-width: 800px; /* Ajuste a largura conforme necessário */
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

        .form-group { margin-bottom: 20px; } /* Aumenta o espaçamento entre os campos */
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: #555;
        }
        .form-control {
            width: 100%; padding: 12px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
         /* Ajuste altura do textarea */
        textarea.form-control {
             min-height: 120px; 
             resize: vertical; /* Permite redimensionar verticalmente */
         }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            outline: none;
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
            <a href="gerenciar_cursos.php"><i class="fa-solid fa-graduation-cap"></i> Cursos</a> 
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-pen-to-square"></i> Editar Curso: <?php echo htmlspecialchars($nome_curso); ?></h2>

            <?php if (!empty($erro_geral)) { echo '<div class="alert-geral">' . htmlspecialchars($erro_geral) . '</div>'; } ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_curso; ?>" method="post">
                <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">

                <div class="form-group">
                    <label>Nome do Curso</label>
                    <input type="text" name="nome_curso" class="form-control" value="<?php echo htmlspecialchars($nome_curso); ?>">
                    <span class="form-error"><?php echo $nome_curso_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" class="form-control" placeholder="Descreva brevemente o curso..."><?php echo htmlspecialchars($descricao); ?></textarea>
                    </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                    <a href="gerenciar_cursos.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
<?php 
// Fechar a conexão somente se ela foi aberta e não fechada anteriormente (no POST)
if (isset($link) && $link) {
    mysqli_close($link); 
}
?>