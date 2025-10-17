<?php
require_once 'restricao_acesso.php';
require_once '../conexão.php';

// Buscar cursos para o dropdown
$sql_cursos = "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso";
$result_cursos = mysqli_query($link, $sql_cursos);

$nome_turma = $ano = $semestre = $id_curso = "";
$nome_turma_err = $ano_err = $semestre_err = $id_curso_err = $erro_geral = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["nome_turma"]))) {
        $nome_turma_err = "Insira o nome da turma.";
    } else {
        $nome_turma = trim($_POST["nome_turma"]);
    }

    if (empty(trim($_POST["ano"]))) {
        $ano_err = "Insira o ano.";
    } else {
        $ano = trim($_POST["ano"]);
    }

    if (empty(trim($_POST["semestre"]))) {
        $semestre_err = "Insira o semestre.";
    } else {
        $semestre = trim($_POST["semestre"]);
    }

    if (empty($_POST["id_curso"])) {
        $id_curso_err = "Selecione o curso.";
    } else {
        $id_curso = $_POST["id_curso"];
    }

    if (empty($nome_turma_err) && empty($ano_err) && empty($semestre_err) && empty($id_curso_err)) {
        $sql = "INSERT INTO turmas (nome_turma, ano, semestre, id_curso) VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $nome_turma, $ano, $semestre, $id_curso);

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_turmas.php");
                exit();
            } else {
                $erro_geral = "Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Note: mysqli_close($link) foi movido para o final do HTML para o result_cursos funcionar
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Nova Turma</title>
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
            margin-bottom: 10px; border-bottom: 2px solid #eee;
            padding-bottom: 15px; display: flex; align-items: center;
        }
        .content-card h2 i { margin-right: 15px; color: #007bff; }
        .content-card p { margin-top: 0; margin-bottom: 30px; color: #666; }

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
        
        /* Adiciona borda vermelha para inputs inválidos */
        .is-invalid {
            border-color: #dc3545;
        }

        .form-error {
            color: #dc3545; font-size: 0.85em; display: block; margin-top: 5px;
            min-height: 1em; /* Evita que o layout pule */
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
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">Sistema Escolar</div>
        <nav class="main-nav">
            <a href="../dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fa-solid fa-users-rectangle"></i> Turmas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-plus-circle"></i> Cadastrar Nova Turma</h2>
            <p>Preencha o formulário para criar uma nova turma no sistema.</p>

            <?php if(!empty($erro_geral)): ?>
                <div class="alert-geral"><?php echo $erro_geral; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome da Turma</label>
                        <input type="text" name="nome_turma" class="form-control <?php echo (!empty($nome_turma_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nome_turma; ?>">
                        <span class="form-error"><?php echo $nome_turma_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Curso</label>
                        <select name="id_curso" class="form-control <?php echo (!empty($id_curso_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Selecione...</option>
                            <?php 
                            // Reset pointer para garantir que o loop funcione mesmo se usado antes
                            mysqli_data_seek($result_cursos, 0); 
                            while($curso = mysqli_fetch_assoc($result_cursos)) : ?>
                                <option value="<?php echo $curso['id_curso']; ?>" <?php echo ($id_curso == $curso['id_curso']) ? 'selected' : ''; ?>><?php echo $curso['nome_curso']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <span class="form-error"><?php echo $id_curso_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Ano</label>
                        <input type="number" name="ano" class="form-control <?php echo (!empty($ano_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $ano; ?>" placeholder="Ex: 2024">
                        <span class="form-error"><?php echo $ano_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Semestre</label>
                        <input type="number" name="semestre" class="form-control <?php echo (!empty($semestre_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $semestre; ?>" placeholder="Ex: 1">
                        <span class="form-error"><?php echo $semestre_err; ?></span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Cadastrar Turma</button>
                    <a href="gerenciar_turmas.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
<?php
// Fecha a conexão com o banco de dados
mysqli_close($link);
?>