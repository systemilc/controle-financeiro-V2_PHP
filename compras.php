<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Transacao.php';
require_once 'classes/Fornecedor.php';
require_once 'classes/Produto.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$transacao = new Transacao($db);
$fornecedor = new Fornecedor($db);
$produto = new Produto($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';

// Processar formulário
if($_POST) {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'create') {
            $stmt = $db->prepare("
                INSERT INTO compras (grupo_id, fornecedor_id, numero_nota, valor_total, data_compra, categoria_id, conta_id, tipo_pagamento_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $numero_nota = $_POST['descricao']; // Usar descrição como número da nota
            $valor_total = $_POST['valor'];
            $data_compra = $_POST['data'];
            $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
            $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
            $conta_id = !empty($_POST['conta_id']) ? $_POST['conta_id'] : null;
            $tipo_pagamento_id = null;
            
            if($stmt->execute([$grupo_id, $fornecedor_id, $numero_nota, $valor_total, $data_compra, $categoria_id, $conta_id, $tipo_pagamento_id])) {
                $message = 'Compra registrada com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao registrar compra.';
                $message_type = 'danger';
            }
        }
    }
}

// Obter dados para os selects
$stmt_fornecedores = $fornecedor->read($grupo_id);
$fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);

$stmt_categorias = $db->prepare("SELECT * FROM categorias WHERE grupo_id = ? ORDER BY nome");
$stmt_categorias->execute([$grupo_id]);
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

$stmt_contas = $db->prepare("SELECT * FROM contas WHERE grupo_id = ? ORDER BY nome");
$stmt_contas->execute([$grupo_id]);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

// Obter compras recentes
$stmt_compras = $db->prepare("
    SELECT comp.*, c.nome as categoria_nome, co.nome as conta_nome, f.nome as fornecedor_nome
    FROM compras comp
    LEFT JOIN categorias c ON comp.categoria_id = c.id
    LEFT JOIN contas co ON comp.conta_id = co.id
    LEFT JOIN fornecedores f ON comp.fornecedor_id = f.id
    WHERE comp.grupo_id = ?
    ORDER BY comp.data_compra DESC, comp.created_at DESC
    LIMIT 50
");
$stmt_compras->execute([$grupo_id]);
$compras = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card-modern {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .purchase-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #dc3545;
            transition: all 0.3s ease;
        }
        .purchase-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stats-badge {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }
        .amount-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <i class="fas fa-shopping-cart me-2"></i>Compras
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#compraModal">
                                <i class="fas fa-plus me-1"></i>Nova Compra
                            </button>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card card-modern">
                                <div class="card-body text-center">
                                    <i class="fas fa-shopping-cart fa-2x text-danger mb-2"></i>
                                    <h5 class="card-title">Total de Compras</h5>
                                    <h3 class="text-danger"><?php echo count($compras); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-modern">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                    <h5 class="card-title">Valor Total</h5>
                                    <h3 class="text-success">
                                        R$ <?php echo number_format(array_sum(array_column($compras, 'valor_total')), 2, ',', '.'); ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-modern">
                                <div class="card-body text-center">
                                    <i class="fas fa-truck fa-2x text-info mb-2"></i>
                                    <h5 class="card-title">Fornecedores</h5>
                                    <h3 class="text-info"><?php echo count($fornecedores); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-modern">
                                <div class="card-body text-center">
                                    <i class="fas fa-tags fa-2x text-warning mb-2"></i>
                                    <h5 class="card-title">Categorias</h5>
                                    <h3 class="text-warning"><?php echo count($categorias); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compras List -->
                    <div class="card card-modern">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Compras Recentes
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($compras)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Nenhuma compra registrada</h5>
                                    <p class="text-muted">Comece registrando sua primeira compra!</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($compras as $compra): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="purchase-card">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($compra['numero_nota']); ?></h6>
                                                    <span class="stats-badge">
                                                        R$ <?php echo number_format($compra['valor_total'], 2, ',', '.'); ?>
                                                    </span>
                                                </div>
                                                <div class="amount-info">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo date('d/m/Y', strtotime($compra['data_compra'])); ?>
                                                    </small>
                                                    <?php if ($compra['fornecedor_nome']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-truck me-1"></i>
                                                            <?php echo htmlspecialchars($compra['fornecedor_nome']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($compra['categoria_nome']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-tag me-1"></i>
                                                            <?php echo htmlspecialchars($compra['categoria_nome']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Compra -->
    <div class="modal fade" id="compraModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Nova Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor *</label>
                            <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="data" class="form-label">Data *</label>
                            <input type="date" class="form-control" id="data" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fornecedor_id" class="form-label">Fornecedor</label>
                            <select class="form-select" id="fornecedor_id" name="fornecedor_id">
                                <option value="">Selecione um fornecedor</option>
                                <?php foreach ($fornecedores as $forn): ?>
                                    <option value="<?php echo $forn['id']; ?>">
                                        <?php echo htmlspecialchars($forn['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="conta_id" class="form-label">Conta</label>
                            <select class="form-select" id="conta_id" name="conta_id">
                                <option value="">Selecione uma conta</option>
                                <?php foreach ($contas as $conta): ?>
                                    <option value="<?php echo $conta['id']; ?>">
                                        <?php echo htmlspecialchars($conta['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Compra</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Atualizar contador de notificações
        function updateNotificationCount() {
            fetch('ajax_notificacoes.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notification-count');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(error => console.error('Erro ao atualizar notificações:', error));
        }

        // Atualizar a cada 30 segundos
        updateNotificationCount();
        setInterval(updateNotificationCount, 30000);
    </script>
</body>
</html>
