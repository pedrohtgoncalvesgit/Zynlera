<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

$id_turma = $id_curso = $nome_turma = $ano = $semestre = "";
$nome_turma_err = $id_curso_err = $ano_err = $semestre_err = $erro_geral = "";

// Consulta para buscar todos os cursos (necessário para o <select>)
$cursos_result = mysqli_query($link, "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso");

// 1. Carregar dados
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id_turma_param = trim($_GET["id"]);

    $sql = "SELECT id_turma, id_curso, nome_turma, ano, semestre FROM turmas WHERE id_turma = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id_turma);
        $param_id_turma = $id_turma_param;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $id_turma = $row["id_turma"];
                $id_curso = $row["id_curso"];
                $nome_turma = $row["nome_turma"];
                $ano = $row["ano"];
                $semestre = $row["semestre"];
            } else {
                header("location: gerenciar_turmas.php?erro=nao_encontrado");
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
    
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: gerenciar_turmas.php?erro=id_faltando");
    exit();
}


// 2. Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_turma = $_POST["id_turma"];
    
    $id_curso = trim($_POST["id_curso"]);
    $nome_turma = trim($_POST["nome_turma"]);
    $ano = trim($_POST["ano"]);
    $semestre = trim($_POST["semestre"]);

    // Validação
    if (empty($id_curso) || $id_curso == '0') { $id_curso_err = "Selecione o curso."; }
    if (empty($nome_turma)) { $nome_turma_err = "Insira o nome da turma."; }
    if (empty($ano) || !is_numeric($ano) || strlen($ano) != 4) { $ano_err = "Ano inválido."; }
    if (empty($semestre) || !in_array($semestre, [1, 2])) { $semestre_err = "Semestre inválido."; }

    if (empty($id_curso_err) && empty($nome_turma_err) && empty($ano_err) && empty($semestre_err)) {
        
        $sql = "UPDATE turmas SET id_curso = ?, nome_turma = ?, ano = ?, semestre = ? WHERE id_turma = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isisi", $param_id_curso, $param_nome_turma, $param_ano, $param_semestre, $param_id_turma);
            $param_id_curso = $id_curso;
            $param_nome_turma = $nome_turma;
            $param_ano = $ano;
            $param_semestre = $semestre;
            $param_id_turma = $id_turma;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_turmas.php?sucesso=edicao");
                exit;
            } else {
                $erro_geral = "Erro ao atualizar turma. A combinação pode já existir: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $erro_geral = "Erro de preparação: " . mysqli_error($link);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Turma - Administrador</title>
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
            flex: 1; padding: 30px; max-width: 900px;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group { margin-bottom: 10px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 500; color: #555;
        }
        .form-control {
            width: 100%; padding: 12px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
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
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-pen-to-square"></i> Editar Turma: <?php echo htmlspecialchars($nome_turma); ?></h2>

            <?php if (!empty($erro_geral)) { echo '<div class="alert-geral">' . htmlspecialchars($erro_geral) . '</div>'; } ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_turma; ?>" method="post">
                <input type="hidden" name="id_turma" value="<?php echo $id_turma; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome da Turma</label>
                        <input type="text" name="nome_turma" class="form-control" value="<?php echo htmlspecialchars($nome_turma); ?>">
                        <span class="form-error"><?php echo $nome_turma_err; ?></span>
                    </div>

                    <div class="form-group">
                        <label>Curso Vinculado</label>
                        <select name="id_curso" class="form-control">
                            <option value="0">Selecione o Curso</option>
                            <?php 
                            mysqli_data_seek($cursos_result, 0); 
                            while($row = mysqli_fetch_assoc($cursos_result)): ?>
                                <option value="<?php echo $row['id_curso']; ?>" <?php echo ($id_curso == $row['id_curso']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['nome_curso']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span class="form-error"><?php echo $id_curso_err; ?></span>
                    </div>

                    <div class="form-group">
                        <label>Ano</label>
                        <input type="number" name="ano" class="form-control" value="<?php echo htmlspecialchars($ano); ?>" placeholder="Ex: 2024">
                        <span class="form-error"><?php echo $ano_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Semestre</label>
                        <select name="semestre" class="form-control">
                            <option value="">Selecione</option>
                            <option value="1" <?php echo ($semestre == 1) ? 'selected' : ''; ?>>1º Semestre</option>
                            <option value="2" <?php echo ($semestre == 2) ? 'selected' : ''; ?>>2º Semestre</option>
                        </select>
                        <span class="form-error"><?php echo $semestre_err; ?></span>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                    <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
<?php mysqli_close($link); ?>