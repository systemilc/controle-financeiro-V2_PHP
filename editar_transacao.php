<?php
require_once 'config/database.php';
require_once 'classes/Transacao.php';
require_once 'classes/Categoria.php';

$database = new Database();
$db = $database->getConnection();

$transacao = new Transacao($db);
$categoria = new Categoria($db);

$message = '';
$message_type = '';

// Obter ID da transação
$transacao->id = $_GET['id'] ?? 0;

if($transacao->id == 0) {
    header('Location: transacoes.php');
    exit;
}

// Obter dados da transação
$stmt = $transacao->read();
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$transacao_atual = null;

foreach($transacoes as $t) {
    if($t['id'] == $transacao->id) {
        $transacao_atual = $t;
        break;
    }
}

if(!$transacao_atual) {
    header('Location: transacoes.php');
    exit;
}

// Processar formulário
if($_POST) {
    $transacao->descricao = $_POST['descricao'];
    $transacao->valor = $_POST['valor'];
    $transacao->tipo = $_POST['tipo'];
    $transacao->categoria_id = $_POST['categoria_id'];
    $transacao->data_transacao = $_POST['data_transacao'];
    $transacao->observacoes = $_POST['observacoes'];

    if($transacao->update()) {
        $message = 'Transação atualizada com sucesso!';
        $message_type = 'success';
        // Atualizar dados da transação
        $transacao_atual = array_merge($transacao_atual, [
            'descricao' => $transacao->descricao,
            'valor' => $transacao->valor,
            'tipo' => $transacao->tipo,
            'categoria_id' => $transacao->categoria_id,
            'data_transacao' => $transacao->data_transacao,
            'observacoes' => $transacao->observacoes
        ]);
    } else {
        $message = 'Erro ao atualizar transação.';
        $message_type = 'danger';
    }
}

// Obter categorias
$stmt_categorias = $categoria->read();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Transação - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-wallet me-2"></i>Controle Financeiro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transacoes.php">
                            <i class="fas fa-exchange-alt me-1"></i>Transações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-1"></i>Categorias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-1"></i>Relatórios
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Editar Transação
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tipo" class="form-label">Tipo de Transação</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="receita" <?= $transacao_atual['tipo'] == 'receita' ? 'selected' : '' ?>>
                                            Receita
                                        </option>
                                        <option value="despesa" <?= $transacao_atual['tipo'] == 'despesa' ? 'selected' : '' ?>>
                                            Despesa
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" class="form-control" id="valor" name="valor" 
                                           step="0.01" min="0" required 
                                           value="<?= $transacao_atual['valor'] ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" 
                                       required value="<?= htmlspecialchars($transacao_atual['descricao']) ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="categoria_id" class="form-label">Categoria</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    data-tipo="<?= $cat['tipo'] ?>"
                                                    <?= $transacao_atual['categoria_id'] == $cat['id'] ? 'selected' : '' ?>
                                                    style="color: <?= $cat['cor'] ?>">
                                                <?= htmlspecialchars($cat['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="data_transacao" class="form-label">Data da Transação</label>
                                    <input type="date" class="form-control" id="data_transacao" name="data_transacao" 
                                           value="<?= $transacao_atual['data_transacao'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações (Opcional)</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" 
                                          rows="3"><?= htmlspecialchars($transacao_atual['observacoes']) ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="transacoes.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrar categorias baseado no tipo selecionado
        document.getElementById('tipo').addEventListener('change', function() {
            const tipo = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            const options = categoriaSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if(option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                const categoriaTipo = option.getAttribute('data-tipo');
                if(categoriaTipo === tipo) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });

        // Executar filtro inicial
        document.getElementById('tipo').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
