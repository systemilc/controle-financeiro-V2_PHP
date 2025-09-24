<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Produto.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
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
            $produto->nome = $_POST['nome'];
            $produto->grupo_id = $grupo_id;
            $produto->codigo = $_POST['codigo'] ?? '';
            $produto->quantidade = 0;
            $produto->valor_total = 0;
            $produto->preco_medio = 0;
            $produto->data_ultima_compra = null;
            
            if($produto->exists($produto->nome, $produto->codigo, $grupo_id)) {
                $message = 'Já existe um produto com este nome e código no grupo.';
                $message_type = 'warning';
            } else {
                if($produto->create()) {
                    $message = 'Produto criado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao criar produto.';
                    $message_type = 'danger';
                }
            }
        } elseif($_POST['action'] == 'update') {
            $produto->id = $_POST['id'];
            $produto->nome = $_POST['nome'];
            $produto->codigo = $_POST['codigo'] ?? '';
            
            if($produto->exists($produto->nome, $produto->codigo, $grupo_id, $produto->id)) {
                $message = 'Já existe um produto com este nome e código no grupo.';
                $message_type = 'warning';
            } else {
                if($produto->update()) {
                    $message = 'Produto atualizado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar produto.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Processar exclusão
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $produto->id = $_GET['id'];
    
    if($produto->canDelete()) {
        if($produto->delete()) {
            $message = 'Produto excluído com sucesso!';
            $message_type = 'success';
        } else {
            $message = 'Erro ao excluir produto.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Não é possível excluir este produto pois ele possui compras associadas.';
        $message_type = 'warning';
    }
}

// Filtros
$filtro_nome = $_GET['nome'] ?? '';
$filtro_codigo = $_GET['codigo'] ?? '';

// Obter produtos
$stmt_produtos = $produto->read($grupo_id, $filtro_nome, $filtro_codigo);
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Controle Financeiro</title>
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
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #17a2b8;
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stats-badge {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }
        .price-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        .price-trend {
            font-size: 0.9rem;
            font-weight: 600;
        }
        .price-up { color: #dc3545; }
        .price-down { color: #28a745; }
        .price-stable { color: #6c757d; }
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
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-box me-2 text-primary"></i>
                                Produtos
                            </h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduto">
                                    <i class="fas fa-plus me-1"></i>Novo Produto
                                </button>
                                <a href="analise_produtos.php" class="btn btn-outline-info">
                                    <i class="fas fa-chart-line me-1"></i>Análise de Preços
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="container-fluid p-4">
                        <?php if($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Filtros -->
                        <div class="card card-modern mb-4">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-filter me-2"></i>Filtros
                                </h6>
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="nome" class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               value="<?= htmlspecialchars($filtro_nome) ?>" 
                                               placeholder="Digite o nome do produto">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="codigo" class="form-label">Código do Produto</label>
                                        <input type="text" class="form-control" id="codigo" name="codigo" 
                                               value="<?= htmlspecialchars($filtro_codigo) ?>" 
                                               placeholder="Digite o código do produto">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <a href="produtos.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times"></i> Limpar
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Lista de Produtos -->
                        <div class="row">
                            <?php if(empty($produtos)): ?>
                                <div class="col-12">
                                    <div class="card card-modern">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhum produto encontrado</h5>
                                            <p class="text-muted">
                                                <?php if($filtro_nome || $filtro_codigo): ?>
                                                    Nenhum produto encontrado com os filtros aplicados.
                                                <?php else: ?>
                                                    Comece criando seus primeiros produtos ou importe de planilhas.
                                                <?php endif; ?>
                                            </p>
                                            <?php if(!$filtro_nome && !$filtro_codigo): ?>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduto">
                                                        <i class="fas fa-plus me-1"></i>Criar Primeiro Produto
                                                    </button>
                                                    <a href="importar_compras.php" class="btn btn-outline-success">
                                                        <i class="fas fa-file-import me-1"></i>Importar de Planilha
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($produtos as $produto_item): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="product-card">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1"><?= htmlspecialchars($produto_item['nome']) ?></h5>
                                                    <?php if($produto_item['codigo']): ?>
                                                        <small class="text-muted">Código: <?= htmlspecialchars($produto_item['codigo']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editarProduto(<?= htmlspecialchars(json_encode($produto_item)) ?>)">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" onclick="verHistorico(<?= $produto_item['id'] ?>)">
                                                                <i class="fas fa-history me-2"></i>Ver Histórico
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="produtos.php?action=delete&id=<?= $produto_item['id'] ?>" 
                                                               onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                                <i class="fas fa-trash me-2"></i>Excluir
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <?php if($produto_item['quantidade'] > 0 || $produto_item['valor_total'] > 0): ?>
                                                <div class="price-info">
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <div class="fw-bold"><?= number_format($produto_item['quantidade'], 0, ',', '.') ?></div>
                                                            <small class="text-muted">Qtd Total</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-success">R$ <?= number_format($produto_item['valor_total'], 2, ',', '.') ?></div>
                                                            <small class="text-muted">Valor Total</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-info">R$ <?= number_format($produto_item['preco_medio'], 2, ',', '.') ?></div>
                                                            <small class="text-muted">Preço Médio</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php if($produto_item['data_ultima_compra']): ?>
                                                            Última compra: <?= date('d/m/Y', strtotime($produto_item['data_ultima_compra'])) ?>
                                                        <?php else: ?>
                                                            Nunca comprado
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <span class="stats-badge">
                                                        <i class="fas fa-chart-line me-1"></i>
                                                        Análise
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Novo/Editar Produto -->
    <div class="modal fade" id="modalProduto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formProduto">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="produtoId">
                        
                        <div class="mb-3">
                            <label for="nome_modal" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="nome_modal" name="nome" required 
                                   placeholder="Ex: Arroz 5kg, Leite Integral 1L">
                        </div>
                        
                        <div class="mb-3">
                            <label for="codigo_modal" class="form-label">Código do Produto</label>
                            <input type="text" class="form-control" id="codigo_modal" name="codigo" 
                                   placeholder="Ex: ARR001, LEI001">
                            <small class="form-text text-muted">
                                Código único para identificação do produto (opcional)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Histórico de Compras -->
    <div class="modal fade" id="modalHistorico" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i>Histórico de Compras
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historicoContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando histórico...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarProduto(produto) {
            document.getElementById('modalTitulo').textContent = 'Editar Produto';
            document.getElementById('action').value = 'update';
            document.getElementById('produtoId').value = produto.id;
            document.getElementById('nome_modal').value = produto.nome;
            document.getElementById('codigo_modal').value = produto.codigo;
            
            var modal = new bootstrap.Modal(document.getElementById('modalProduto'));
            modal.show();
        }

        function verHistorico(produtoId) {
            const modal = new bootstrap.Modal(document.getElementById('modalHistorico'));
            modal.show();
            
            // Carregar histórico via AJAX
            fetch(`ajax_historico_produto.php?id=${produtoId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('historicoContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('historicoContent').innerHTML = 
                        '<div class="alert alert-danger">Erro ao carregar histórico</div>';
                });
        }

        // Limpar formulário quando modal é fechado
        document.getElementById('modalProduto').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitulo').textContent = 'Novo Produto';
            document.getElementById('action').value = 'create';
            document.getElementById('produtoId').value = '';
            document.getElementById('nome_modal').value = '';
            document.getElementById('codigo_modal').value = '';
        });

        // Atualizar contador de notificações
        function updateNotificationCount() {
            fetch('ajax_notificacoes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_count'
            })
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
