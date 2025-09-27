<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/SugestaoCompra.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$sugestao = new SugestaoCompra($db);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'marcar_comprada') {
        $sugestao->id = $_POST['sugestao_id'];
        if ($sugestao->marcarComoComprada()) {
            $success_message = "Sugestão marcada como comprada com sucesso!";
        } else {
            $error_message = "Erro ao marcar sugestão como comprada.";
        }
    } elseif ($action == 'registrar_compra') {
        // Registrar compra completa
        require_once 'classes/Compra.php';
        require_once 'classes/ItemCompra.php';
        
        $compra = new Compra($db);
        $itemCompra = new ItemCompra($db);
        
        try {
            $db->beginTransaction();
            
            // Obter dados da sugestão
            $sugestao_data = $sugestao->getById($_POST['sugestao_id']);
            if (!$sugestao_data) {
                throw new Exception("Sugestão não encontrada");
            }
            
            // Criar compra
            $compra->fornecedor_id = $_POST['fornecedor_id'];
            $compra->grupo_id = $grupo_id;
            $compra->data_compra = $_POST['data_compra'];
            $compra->numero_nota = 'SUG-' . date('YmdHis'); // Gerar número da nota
            $compra->valor_total = $_POST['quantidade'] * $_POST['preco_unitario'];
            $compra->conta_id = $_POST['conta_id'] ?? null;
            $compra->tipo_pagamento_id = $_POST['tipo_pagamento_id'] ?? null;
            $compra->categoria_id = 1; // Assumir categoria padrão
            
            if (!$compra->create()) {
                throw new Exception("Erro ao criar compra");
            }
            
            $compra_id = $db->lastInsertId();
            
            // Criar item da compra
            $itemCompra->compra_id = $compra_id;
            $itemCompra->produto_id = $sugestao_data['produto_id'];
            $itemCompra->quantidade = $_POST['quantidade'];
            $itemCompra->preco_unitario = $_POST['preco_unitario'];
            $itemCompra->preco_total = $_POST['quantidade'] * $_POST['preco_unitario'];
            
            if (!$itemCompra->create()) {
                throw new Exception("Erro ao criar item da compra");
            }
            
            // Atualizar produto
            $query_update_produto = "UPDATE produtos SET 
                quantidade = quantidade + :quantidade,
                valor_total = valor_total + :valor_total,
                preco_medio = (valor_total + :valor_total) / (quantidade + :quantidade),
                data_ultima_compra = :data_compra,
                estoque_zerado = 0,
                data_estoque_zerado = NULL
                WHERE id = :produto_id";
            
            $stmt_update = $db->prepare($query_update_produto);
            $stmt_update->bindParam(":quantidade", $_POST['quantidade']);
            $stmt_update->bindParam(":valor_total", $itemCompra->preco_total);
            $stmt_update->bindParam(":data_compra", $_POST['data_compra']);
            $stmt_update->bindParam(":produto_id", $sugestao_data['produto_id']);
            $stmt_update->execute();
            
            // Marcar sugestão como comprada
            $sugestao->id = $_POST['sugestao_id'];
            $sugestao->status = 'comprada';
            $sugestao->observacoes = "Compra registrada em " . getCurrentDateFormatted() . " - " . ($_POST['observacoes'] ?? '');
            
            // Usar método específico para marcar como comprada
            $query_update_sugestao = "UPDATE sugestoes_compra 
                                    SET status = 'comprada', 
                                        observacoes = :observacoes,
                                        updated_at = NOW()
                                    WHERE id = :id";
            $stmt_sugestao = $db->prepare($query_update_sugestao);
            $stmt_sugestao->bindParam(":observacoes", $sugestao->observacoes);
            $stmt_sugestao->bindParam(":id", $sugestao->id);
            $stmt_sugestao->execute();
            
            $db->commit();
            $success_message = "Compra registrada e sugestão marcada como comprada com sucesso!";
            
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Erro ao registrar compra: " . $e->getMessage();
        }
    } elseif ($action == 'cancelar') {
        $sugestao->id = $_POST['sugestao_id'];
        $sugestao->status = 'cancelada';
        if ($sugestao->update()) {
            $success_message = "Sugestão cancelada com sucesso!";
        } else {
            $error_message = "Erro ao cancelar sugestão.";
        }
    } elseif ($action == 'atualizar') {
        $sugestao->id = $_POST['sugestao_id'];
        $sugestao->quantidade_sugerida = $_POST['quantidade_sugerida'];
        $sugestao->prioridade = $_POST['prioridade'];
        $sugestao->observacoes = $_POST['observacoes'];
        $sugestao->status = $_POST['status'];
        
        if ($sugestao->update()) {
            $success_message = "Sugestão atualizada com sucesso!";
        } else {
            $error_message = "Erro ao atualizar sugestão.";
        }
    }
}

// Obter filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_prioridade = $_GET['prioridade'] ?? '';
$filtro_produto = $_GET['produto'] ?? '';

// Obter sugestões - por padrão mostrar apenas ativas
$status_padrao = $filtro_status ?: 'ativa';
if (!empty($filtro_produto)) {
    $sugestoes = $sugestao->searchByProduto($grupo_id, $filtro_produto);
    // Filtrar apenas ativas se não especificado outro status
    if (!$filtro_status) {
        $sugestoes = array_filter($sugestoes, function($sugestao) {
            return $sugestao['status'] === 'ativa';
        });
    }
} elseif (!empty($filtro_prioridade)) {
    $sugestoes = $sugestao->getByPrioridade($grupo_id, $filtro_prioridade);
    // Filtrar apenas ativas se não especificado outro status
    if (!$filtro_status) {
        $sugestoes = array_filter($sugestoes, function($sugestao) {
            return $sugestao['status'] === 'ativa';
        });
    }
} else {
    $sugestoes = $sugestao->getAll($grupo_id, $status_padrao);
}

// Obter estatísticas
$estatisticas = $sugestao->getEstatisticas($grupo_id);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugestões de Compra - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .priority-badge {
            font-size: 0.75rem;
            font-weight: 600;
        }
        .consumo-info {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .sugestao-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .sugestao-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .priority-critica { border-left-color: #dc3545; }
        .priority-alta { border-left-color: #fd7e14; }
        .priority-media { border-left-color: #ffc107; }
        .priority-baixa { border-left-color: #28a745; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-shopping-cart me-2 text-primary"></i>
                                Sugestões de Compra
                            </h2>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalGerarSugestao">
                                    <i class="fas fa-plus me-1"></i>Gerar Sugestão
                                </button>
                                <a href="produtos.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-box me-1"></i>Produtos
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">Total de Sugestões</h6>
                                            <h3 class="mb-0"><?= $estatisticas['total_sugestoes'] ?? 0 ?></h3>
                                        </div>
                                        <div class="ms-3">
                                            <i class="fas fa-list fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">Ativas</h6>
                                            <h3 class="mb-0"><?= $estatisticas['sugestoes_ativas'] ?? 0 ?></h3>
                                        </div>
                                        <div class="ms-3">
                                            <i class="fas fa-clock fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">Prioridade Alta</h6>
                                            <h3 class="mb-0"><?= ($estatisticas['prioridade_critica'] ?? 0) + ($estatisticas['prioridade_alta'] ?? 0) ?></h3>
                                        </div>
                                        <div class="ms-3">
                                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">Compradas</h6>
                                            <h3 class="mb-0"><?= $estatisticas['sugestoes_compradas'] ?? 0 ?></h3>
                                        </div>
                                        <div class="ms-3">
                                            <i class="fas fa-check fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="ativa" <?= $filtro_status == 'ativa' || $filtro_status == '' ? 'selected' : '' ?>>Ativas (Padrão)</option>
                                        <option value="">Todas</option>
                                        <option value="comprada" <?= $filtro_status == 'comprada' ? 'selected' : '' ?>>Compradas</option>
                                        <option value="cancelada" <?= $filtro_status == 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="prioridade" class="form-label">Prioridade</label>
                                    <select name="prioridade" id="prioridade" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="critica" <?= $filtro_prioridade == 'critica' ? 'selected' : '' ?>>Crítica</option>
                                        <option value="alta" <?= $filtro_prioridade == 'alta' ? 'selected' : '' ?>>Alta</option>
                                        <option value="media" <?= $filtro_prioridade == 'media' ? 'selected' : '' ?>>Média</option>
                                        <option value="baixa" <?= $filtro_prioridade == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="produto" class="form-label">Produto</label>
                                    <input type="text" name="produto" id="produto" class="form-control" 
                                           placeholder="Buscar por nome ou código" value="<?= htmlspecialchars($filtro_produto) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Sugestões -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Sugestões Ativas
                                <span class="badge bg-success ms-2"><?= count($sugestoes) ?></span>
                            </h5>
                            <small class="text-muted">Produtos que você precisa comprar</small>
                        </div>
                        <div class="card-body">
                            <?php if (empty($sugestoes)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-success">🎉 Parabéns!</h5>
                                    <p class="text-muted">Você não tem produtos pendentes para comprar no momento.</p>
                                    <p class="text-muted">Todas as suas sugestões estão em dia!</p>
                                    <div class="mt-4">
                                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalGerarSugestao">
                                            <i class="fas fa-plus me-2"></i>Gerar Nova Sugestão
                                        </button>
                                        <a href="produtos.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-boxes me-2"></i>Gerenciar Produtos
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($sugestoes as $sugestao_item): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card sugestao-card priority-<?= $sugestao_item['prioridade'] ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0"><?= htmlspecialchars($sugestao_item['produto_nome']) ?></h6>
                                                        <span class="badge priority-badge bg-<?= $sugestao_item['prioridade'] == 'critica' ? 'danger' : ($sugestao_item['prioridade'] == 'alta' ? 'warning' : ($sugestao_item['prioridade'] == 'media' ? 'info' : 'success')) ?>">
                                                            <?= ucfirst($sugestao_item['prioridade']) ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if ($sugestao_item['produto_codigo']): ?>
                                                        <p class="text-muted small mb-2">
                                                            <i class="fas fa-barcode me-1"></i>
                                                            <?= htmlspecialchars($sugestao_item['produto_codigo']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <div class="text-center">
                                                                <div class="h5 text-primary mb-0"><?= number_format($sugestao_item['quantidade_sugerida'], 0) ?></div>
                                                                <small class="text-muted">Qtd. Sugerida</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="text-center">
                                                                <div class="h6 text-info mb-0"><?= number_format($sugestao_item['consumo_diario_medio'], 2) ?></div>
                                                                <small class="text-muted">Consumo/Dia</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="consumo-info mb-3">
                                                        <small>
                                                            <i class="fas fa-calendar me-1"></i>
                                                            Última compra: <?= date('d/m/Y', strtotime($sugestao_item['data_ultima_compra'])) ?><br>
                                                            <i class="fas fa-clock me-1"></i>
                                                            Consumido em: <?= $sugestao_item['dias_consumo'] ?> dias
                                                        </small>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge bg-<?= $sugestao_item['status'] == 'ativa' ? 'success' : ($sugestao_item['status'] == 'comprada' ? 'primary' : 'secondary') ?>">
                                                            <?= ucfirst($sugestao_item['status']) ?>
                                                        </span>
                                                        
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-primary" 
                                                                    onclick="editarSugestao(<?= $sugestao_item['id'] ?>)"
                                                                    title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($sugestao_item['status'] == 'ativa'): ?>
                                                                <button class="btn btn-outline-success" 
                                                                        onclick="marcarComprada(<?= $sugestao_item['id'] ?>)"
                                                                        title="Marcar como Comprada">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-outline-danger" 
                                                                        onclick="cancelarSugestao(<?= $sugestao_item['id'] ?>)"
                                                                        title="Cancelar">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sugestões Compradas Recentemente -->
                    <?php 
                    // Buscar sugestões compradas recentemente (últimas 5)
                    $sugestoes_compradas = $sugestao->getAll($grupo_id, 'comprada');
                    $sugestoes_compradas = array_slice($sugestoes_compradas, 0, 5);
                    ?>
                    
                    <?php if (!empty($sugestoes_compradas)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                Compradas Recentemente
                                <span class="badge bg-primary ms-2"><?= count($sugestoes_compradas) ?></span>
                            </h6>
                            <small class="text-muted">Últimas sugestões que foram compradas</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($sugestoes_compradas as $sugestao_comprada): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-success">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0 text-success"><?= htmlspecialchars($sugestao_comprada['produto_nome']) ?></h6>
                                                    <span class="badge bg-success">Comprada</span>
                                                </div>
                                                
                                                <?php if ($sugestao_comprada['produto_codigo']): ?>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        <?= htmlspecialchars($sugestao_comprada['produto_codigo']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <div class="text-center">
                                                            <div class="h6 text-success mb-0"><?= number_format($sugestao_comprada['quantidade_sugerida'], 0) ?></div>
                                                            <small class="text-muted">Qtd. Comprada</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-center">
                                                            <div class="h6 text-info mb-0"><?= date('d/m', strtotime($sugestao_comprada['updated_at'])) ?></div>
                                                            <small class="text-muted">Data</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($sugestao_comprada['observacoes']): ?>
                                                    <div class="alert alert-info p-2 mb-0">
                                                        <small>
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            <?= htmlspecialchars(substr($sugestao_comprada['observacoes'], 0, 100)) ?>
                                                            <?= strlen($sugestao_comprada['observacoes']) > 100 ? '...' : '' ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="sugestoes_compra.php?status=comprada" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver Todas as Compradas
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gerar Sugestão -->
    <div class="modal fade" id="modalGerarSugestao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Gerar Sugestão de Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Para gerar uma sugestão de compra, você precisa marcar um produto como acabado na página de produtos.
                        O sistema calculará automaticamente a quantidade sugerida baseada no consumo histórico.
                    </p>
                    <div class="text-center">
                        <a href="produtos.php" class="btn btn-primary">
                            <i class="fas fa-box me-2"></i>Ir para Produtos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Sugestão -->
    <div class="modal fade" id="modalEditarSugestao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Sugestão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditarSugestao">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="atualizar">
                        <input type="hidden" name="sugestao_id" id="editar_sugestao_id">
                        
                        <div class="mb-3">
                            <label for="editar_quantidade" class="form-label">Quantidade Sugerida</label>
                            <input type="number" class="form-control" id="editar_quantidade" name="quantidade_sugerida" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_prioridade" class="form-label">Prioridade</label>
                            <select class="form-select" id="editar_prioridade" name="prioridade" required>
                                <option value="baixa">Baixa</option>
                                <option value="media">Média</option>
                                <option value="alta">Alta</option>
                                <option value="critica">Crítica</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_status" class="form-label">Status</label>
                            <select class="form-select" id="editar_status" name="status" required>
                                <option value="ativa">Ativa</option>
                                <option value="comprada">Comprada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="editar_observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Registrar Compra -->
    <div class="modal fade" id="modalRegistrarCompra" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-cart me-2"></i>Registrar Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formRegistrarCompra">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="registrar_compra">
                        <input type="hidden" name="sugestao_id" id="compra_sugestao_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_fornecedor" class="form-label">Fornecedor *</label>
                                    <select class="form-select" id="compra_fornecedor" name="fornecedor_id" required>
                                        <option value="">Selecione um fornecedor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_data" class="form-label">Data da Compra *</label>
                                    <input type="date" class="form-control" id="compra_data" name="data_compra" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_quantidade" class="form-label">Quantidade *</label>
                                    <input type="number" class="form-control" id="compra_quantidade" name="quantidade" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_preco_unitario" class="form-label">Preço Unitário *</label>
                                    <input type="number" class="form-control" id="compra_preco_unitario" name="preco_unitario" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_tipo_pagamento" class="form-label">Tipo de Pagamento *</label>
                                    <select class="form-select" id="compra_tipo_pagamento" name="tipo_pagamento_id" required>
                                        <option value="">Selecione o tipo de pagamento</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="compra_conta" class="form-label">Conta *</label>
                                    <select class="form-select" id="compra_conta" name="conta_id" required>
                                        <option value="">Selecione a conta</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="compra_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="compra_observacoes" name="observacoes" rows="3" placeholder="Observações sobre a compra..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informação:</strong> Esta compra será registrada no sistema e a sugestão será marcada como comprada automaticamente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Registrar Compra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarSugestao(id) {
            // Buscar dados da sugestão via AJAX
            fetch(`ajax_sugestoes.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_sugestao_id').value = data.sugestao.id;
                        document.getElementById('editar_quantidade').value = data.sugestao.quantidade_sugerida;
                        document.getElementById('editar_prioridade').value = data.sugestao.prioridade;
                        document.getElementById('editar_status').value = data.sugestao.status;
                        document.getElementById('editar_observacoes').value = data.sugestao.observacoes || '';
                        
                        new bootstrap.Modal(document.getElementById('modalEditarSugestao')).show();
                    } else {
                        alert('Erro ao carregar dados da sugestão');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da sugestão');
                });
        }

        function marcarComprada(id) {
            // Buscar dados da sugestão via AJAX
            fetch(`ajax_sugestoes.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('compra_sugestao_id').value = data.sugestao.id;
                        document.getElementById('compra_quantidade').value = data.sugestao.quantidade_sugerida;
                        document.getElementById('compra_data').value = new Date().toISOString().split('T')[0];
                        
                        // Carregar dados dos selects
                        carregarDadosCompra();
                        
                        new bootstrap.Modal(document.getElementById('modalRegistrarCompra')).show();
                    } else {
                        alert('Erro ao carregar dados da sugestão');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da sugestão');
                });
        }

        function carregarDadosCompra() {
            // Carregar fornecedores
            fetch('ajax_sugestoes.php?action=get_fornecedores')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('compra_fornecedor');
                        select.innerHTML = '<option value="">Selecione um fornecedor</option>';
                        data.fornecedores.forEach(fornecedor => {
                            const option = document.createElement('option');
                            option.value = fornecedor.id;
                            option.textContent = fornecedor.nome;
                            select.appendChild(option);
                        });
                    }
                });

            // Carregar tipos de pagamento
            fetch('ajax_sugestoes.php?action=get_tipos_pagamento')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('compra_tipo_pagamento');
                        select.innerHTML = '<option value="">Selecione o tipo de pagamento</option>';
                        data.tipos_pagamento.forEach(tipo => {
                            const option = document.createElement('option');
                            option.value = tipo.id;
                            option.textContent = tipo.nome;
                            select.appendChild(option);
                        });
                    }
                });

            // Carregar contas
            fetch('ajax_sugestoes.php?action=get_contas')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('compra_conta');
                        select.innerHTML = '<option value="">Selecione a conta</option>';
                        data.contas.forEach(conta => {
                            const option = document.createElement('option');
                            option.value = conta.id;
                            option.textContent = conta.nome;
                            select.appendChild(option);
                        });
                    }
                });
        }

        function cancelarSugestao(id) {
            if (confirm('Deseja cancelar esta sugestão?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancelar">
                    <input type="hidden" name="sugestao_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
