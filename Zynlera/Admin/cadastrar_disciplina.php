<?php
require_once 'restricao_acesso.php';
$link = require_once '../conexão.php';

$id_curso = $nome_disciplina = $codigo = $carga_horaria = "";
$id_curso_err = $nome_disciplina_err = $codigo_err = $carga_horaria_err = $erro_geral = "";

// Consulta para buscar todos os cursos (necessário para o <select>)
$cursos_result = mysqli_query($link, "SELECT id_curso, nome_curso FROM cursos ORDER BY nome_curso");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validação dos Campos
    $id_curso = trim($_POST["id_curso"]);
    $nome_disciplina = trim($_POST["nome_disciplina"]);
    $codigo = trim($_POST["codigo"]);
    $carga_horaria = trim($_POST["carga_horaria"]);

    if (empty($id_curso) || $id_curso == '0') { $id_curso_err = "Selecione o curso."; }
    if (empty($nome_disciplina)) { $nome_disciplina_err = "Insira o nome da disciplina."; }
    if (empty($codigo)) { $codigo_err = "Insira o código."; }
    if (empty($carga_horaria) || !is_numeric($carga_horaria) || $carga_horaria <= 0) { $carga_horaria_err = "Carga horária inválida."; }

    if (empty($id_curso_err) && empty($nome_disciplina_err) && empty($codigo_err) && empty($carga_horaria_err)) {
        $sql = "INSERT INTO disciplinas (id_curso, nome_disciplina, codigo_disciplina, carga_horaria) VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issi", $param_id_curso, $param_nome, $param_codigo, $param_carga);
            $param_id_curso = $id_curso;
            $param_nome = $nome_disciplina;
            $param_codigo = $codigo;
            $param_carga = $carga_horaria;

            if (mysqli_stmt_execute($stmt)) {
                header("location: gerenciar_disciplinas.php?sucesso=cadastro");
                exit;
            } else {
                // Erro pode ser devido à restrição UNIQUE no 'codigo'
                $erro_geral = "Erro ao inserir disciplina. O código da disciplina pode já existir: " . mysqli_error($link);
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
    <title>Cadastrar Disciplina - Administrador</title>
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
            <a href="gerenciar_disciplinas.php"><i class="fa-solid fa-book"></i> Disciplinas</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </nav>
    </header>

    <main class="container">
        <div class="content-card">
            <h2><i class="fa-solid fa-plus-circle"></i> Cadastrar Nova Disciplina</h2>

            <?php if (!empty($erro_geral)) { echo '<div class="alert-geral">' . htmlspecialchars($erro_geral) . '</div>'; } ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome da Disciplina</label>
                        <input type="text" name="nome_disciplina" class="form-control" value="<?php echo htmlspecialchars($nome_disciplina); ?>">
                        <span class="form-error"><?php echo $nome_disciplina_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Curso Vinculado</label>
                        <select name="id_curso" class="form-control">
                            <option value="0">Selecione o Curso</option>
                            <?php 
                            // Garante que o ponteiro está no início caso o resultado já tenha sido usado
                            if ($cursos_result) mysqli_data_seek($cursos_result, 0); 
                            while($row = mysqli_fetch_assoc($cursos_result)): ?>
                                <option value="<?php echo $row['id_curso']; ?>" <?php echo ($id_curso == $row['id_curso']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['nome_curso']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span class="form-error"><?php echo $id_curso_err; ?></span>
                    </div>

                    <div class="form-group">
                        <label>Código da Disciplina (Ex: ADS001)</label>
                        <input type="text" name="codigo" class="form-control" value="<?php echo htmlspecialchars($codigo); ?>">
                        <span class="form-error"><?php echo $codigo_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Carga Horária (em horas)</label>
                        <input type="number" name="carga_horaria" class="form-control" value="<?php echo htmlspecialchars($carga_horaria); ?>" placeholder="Ex: 80">
                        <span class="form-error"><?php echo $carga_horaria_err; ?></span>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Cadastrar Disciplina</button>
                    <a href="gerenciar_disciplinas.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
<?php mysqli_close($link); ?>