<?php
session_start();
require_once 'config/database.php';
require_once 'config/timezone.php';
require_once 'classes/Auth.php';
require_once 'classes/Transacao.php';
require_once 'classes/Conta.php';
require_once 'classes/Categoria.php';
require_once 'classes/TipoPagamento.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();

// Instanciar classes
$transacao = new Transacao();
$conta = new Conta();
$categoria = new Categoria();
$tipo_pagamento = new TipoPagamento();

// Definir grupo_id do usuário atual
$transacao->grupo_id = $current_user['grupo_id'];
$conta->grupo_id = $current_user['grupo_id'];
$categoria->grupo_id = $current_user['grupo_id'];
$tipo_pagamento->grupo_id = $current_user['grupo_id'];

// Parâmetros de paginação e ordenação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Transações por página
$offset = ($page - 1) * $limit;

$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_transacao';
$order_direction = isset($_GET['order_direction']) ? $_GET['order_direction'] : 'DESC';

// Processar ações
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $transacao->usuario_id = $current_user['id'];
                $transacao->conta_id = $_POST['conta_id'];
                $transacao->categoria_id = $_POST['categoria_id'];
                $transacao->tipo_pagamento_id = $_POST['tipo_pagamento_id'];
                $transacao->descricao = $_POST['descricao'];
                $transacao->valor = $_POST['valor'];
                $transacao->tipo = $_POST['tipo'];
                $transacao->data_transacao = getCurrentDate(); // Sempre data atual
                $transacao->data_vencimento = null; // Sempre null, usa data atual
                $transacao->observacoes = $_POST['observacoes'];
                $transacao->is_confirmed = isset($_POST['is_confirmed']) ? 1 : 0;
                
                $quantidade_parcelas = $_POST['quantidade_parcelas'] ?: 1;
                $tipo_parcelamento = $_POST['tipo_parcelamento'] ?: 'sem';
                
                try {
                    if ($tipo_parcelamento == 'sem' || $quantidade_parcelas == 1) {
                        // Transação única
                        if ($transacao->create()) {
                            $message = 'Transação criada com sucesso!';
                            $message_type = 'success';
                        } else {
                            $message = 'Erro ao criar transação!';
                            $message_type = 'danger';
                        }
                    } else {
                        // Criar parcelas
                        $parcelas_criadas = $transacao->createParcelas($quantidade_parcelas, $tipo_parcelamento);
                        if ($parcelas_criadas > 0) {
                            $message = "Transação criada com {$parcelas_criadas} parcelas com sucesso!";
                            $message_type = 'success';
                        } else {
                            $message = 'Erro ao criar parcelas!';
                            $message_type = 'danger';
                        }
                    }
                } catch (Exception $e) {
                    $message = 'Erro: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $transacao->id = $_POST['id'];
                $transacao->usuario_id = $current_user['id'];
                $transacao->conta_id = $_POST['conta_id'];
                $transacao->categoria_id = $_POST['categoria_id'];
                $transacao->tipo_pagamento_id = $_POST['tipo_pagamento_id'];
                $transacao->descricao = $_POST['descricao'];
                $transacao->valor = $_POST['valor'];
                $transacao->tipo = $_POST['tipo'];
                $transacao->data_transacao = getCurrentDate(); // Sempre data atual
                $transacao->data_vencimento = null; // Sempre null, usa data atual
                $transacao->observacoes = $_POST['observacoes'];
                $transacao->is_confirmed = isset($_POST['is_confirmed']) ? 1 : 0;
                
                if ($transacao->update()) {
                    $message = 'Transação atualizada com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar transação!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $transacao->id = $_POST['id'];
                if ($transacao->delete()) {
                    $message = 'Transação excluída com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao excluir transação!';
                    $message_type = 'danger';
                }
                break;
                
            case 'confirm':
                $transacao->id = $_POST['id'];
                if ($transacao->confirm()) {
                    $message = 'Transação confirmada com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao confirmar transação!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Ordenação
$order_by = $_GET['order_by'] ?? 'data_transacao';
$order_direction = $_GET['order_direction'] ?? 'DESC';

// Buscar dados
if ($auth->getUserRole() === 'admin') {
    // Admin vê todas as transações
    $transacoes = $transacao->getAllForAdmin($order_by, $order_direction);
    $total_transacoes = count($transacoes);
    $total_pages = ceil($total_transacoes / $limit);
    $transacoes = array_slice($transacoes, $offset, $limit);
} else {
    // Usuário normal vê apenas do seu grupo
    $transacoes = $transacao->getAllWithPagination($order_by, $order_direction, $limit, $offset);
    $total_transacoes = $transacao->getTotalCount();
    $total_pages = ceil($total_transacoes / $limit);
}

$contas = $conta->getAll();
$categorias = $categoria->getAll();
$tipos_pagamento = $tipo_pagamento->getAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            border-radius: 20px;
            padding: 8px 15px;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .table th a {
            transition: all 0.3s ease;
        }
        .table th a:hover {
            color: #ffc107 !important;
            transform: translateY(-1px);
        }
        .table th a i {
            transition: all 0.3s ease;
        }
        .table th a:hover i {
            color: #ffc107 !important;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .cursor-pointer:hover {
            transform: scale(1.2);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-exchange-alt me-2"></i>Transações</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTransacao">
                        <i class="fas fa-plus me-2"></i>Nova Transação
                    </button>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" id="filtroTipo">
                                    <option value="">Todos</option>
                                    <option value="receita">Receita</option>
                                    <option value="despesa">Despesa</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Conta</label>
                                <select class="form-select" id="filtroConta">
                                    <option value="">Todas</option>
                                    <?php foreach ($contas as $conta_item): ?>
                                    <option value="<?= $conta_item['id'] ?>"><?= htmlspecialchars($conta_item['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="filtroStatus">
                                    <option value="">Todos</option>
                                    <option value="1">Confirmado</option>
                                    <option value="0">Pendente</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data</label>
                                <input type="date" class="form-control" id="filtroData">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Transações -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>
                                            <a href="?order_by=data_transacao&order_direction=<?= $order_by == 'data_transacao' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Data
                                                <?php if($order_by == 'data_transacao'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=descricao&order_direction=<?= $order_by == 'descricao' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Descrição
                                                <?php if($order_by == 'descricao'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=categoria_nome&order_direction=<?= $order_by == 'categoria_nome' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Categoria
                                                <?php if($order_by == 'categoria_nome'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=conta_nome&order_direction=<?= $order_by == 'conta_nome' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Conta
                                                <?php if($order_by == 'conta_nome'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=tipo&order_direction=<?= $order_by == 'tipo' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Tipo
                                                <?php if($order_by == 'tipo'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=valor&order_direction=<?= $order_by == 'valor' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Valor
                                                <?php if($order_by == 'valor'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?order_by=is_confirmed&order_direction=<?= $order_by == 'is_confirmed' && $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>" 
                                               class="text-white text-decoration-none">
                                                Status
                                                <?php if($order_by == 'is_confirmed'): ?>
                                                    <i class="fas fa-sort-<?= $order_direction == 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-sort ms-1 text-white-50"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transacoes as $transacao_item): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($transacao_item['data_transacao'])) ?></td>
                                        <td>
                                            <?= htmlspecialchars($transacao_item['descricao'] ?? '') ?>
                                            <?php if (!empty($transacao_item['observacoes'])): ?>
                                                <i class="fas fa-info-circle text-info ms-2 cursor-pointer" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#modalResumoTransacao"
                                                   data-transacao-id="<?= $transacao_item['id'] ?>"
                                                   data-descricao="<?= htmlspecialchars($transacao_item['descricao'] ?? '') ?>"
                                                   data-valor="<?= $transacao_item['valor'] ?>"
                                                   data-tipo="<?= $transacao_item['tipo'] ?>"
                                                   data-data="<?= date('d/m/Y', strtotime($transacao_item['data_transacao'])) ?>"
                                                   data-conta="<?= htmlspecialchars($transacao_item['conta_nome'] ?? '') ?>"
                                                   data-categoria="<?= htmlspecialchars($transacao_item['categoria_nome'] ?? '') ?>"
                                                   data-tipo-pagamento="<?= htmlspecialchars($transacao_item['tipo_pagamento_nome'] ?? '') ?>"
                                                   data-observacoes="<?= htmlspecialchars($transacao_item['observacoes'] ?? '') ?>"
                                                   data-confirmado="<?= $transacao_item['is_confirmed'] ? 'Sim' : 'Não' ?>"
                                                   title="Ver resumo completo"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transacao_item['categoria_nome']): ?>
                                            <span class="badge" style="background-color: <?= $transacao_item['categoria_cor'] ?? '#6c757d' ?>">
                                                <?= htmlspecialchars($transacao_item['categoria_nome'] ?? '') ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Sem categoria</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($transacao_item['conta_nome'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $transacao_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($transacao_item['tipo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-<?= $transacao_item['tipo'] == 'receita' ? 'success' : 'danger' ?> fw-bold">
                                                R$ <?= number_format($transacao_item['valor'], 2, ',', '.') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transacao_item['is_confirmed']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Confirmado
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pendente
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editTransacao(<?= htmlspecialchars(json_encode($transacao_item)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$transacao_item['is_confirmed']): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="confirmTransacao(<?= $transacao_item['id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTransacao(<?= $transacao_item['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Paginação -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Paginação de transações" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Página anterior -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&order_by=<?= $order_by ?>&order_direction=<?= $order_direction ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </span>
                                </li>
                            <?php endif; ?>

                            <!-- Páginas numeradas -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&order_by=<?= $order_by ?>&order_direction=<?= $order_direction ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Página seguinte -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&order_by=<?= $order_by ?>&order_direction=<?= $order_direction ?>">
                                        Próxima <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        Próxima <i class="fas fa-chevron-right"></i>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- Informações da paginação -->
                        <div class="text-center text-muted mt-2">
                            <small>
                                Mostrando <?= $offset + 1 ?> a <?= min($offset + $limit, $total_transacoes) ?> 
                                de <?= $total_transacoes ?> transações
                                (Página <?= $page ?> de <?= $total_pages ?>)
                            </small>
                        </div>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Transação -->
    <div class="modal fade" id="modalTransacao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTransacaoTitle">Nova Transação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formTransacao">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="id">
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" name="tipo" id="tipo" required>
                                    <option value="">Selecione...</option>
                                    <option value="receita">Receita</option>
                                    <option value="despesa">Despesa</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Conta *</label>
                                <select class="form-select" name="conta_id" id="conta_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($contas as $conta_item): ?>
                                    <option value="<?= $conta_item['id'] ?>"><?= htmlspecialchars($conta_item['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <select class="form-select" name="categoria_id" id="categoria_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $categoria_item): ?>
                                    <option value="<?= $categoria_item['id'] ?>" data-tipo="<?= $categoria_item['tipo'] ?>">
                                        <?= htmlspecialchars($categoria_item['nome']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Pagamento</label>
                                <select class="form-select" name="tipo_pagamento_id" id="tipo_pagamento_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($tipos_pagamento as $tipo_item): ?>
                                    <option value="<?= $tipo_item['id'] ?>"><?= htmlspecialchars($tipo_item['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor *</label>
                                <input type="number" step="0.01" class="form-control" name="valor" id="valor" required>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Descrição *</label>
                                <input type="text" class="form-control" name="descricao" id="descricao" required>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Parcelas</label>
                                <input type="number" class="form-control" name="quantidade_parcelas" id="quantidade_parcelas" value="1" min="1" max="24">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Tipo de Parcelamento</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_parcelamento" id="sem_parcelas" value="sem" checked>
                                    <label class="form-check-label" for="sem_parcelas">
                                        <i class="fas fa-times-circle me-1"></i>Sem parcelas (transação única)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_parcelamento" id="multiplicar" value="multiplicar">
                                    <label class="form-check-label" for="multiplicar">
                                        <i class="fas fa-times-circle me-1"></i>Multiplicar (mesmo valor em cada parcela)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_parcelamento" id="dividir" value="dividir">
                                    <label class="form-check-label" for="dividir">
                                        <i class="fas fa-divide me-1"></i>Dividir (valor total dividido pelas parcelas)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes" id="observacoes" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_confirmed" id="is_confirmed">
                                    <label class="form-check-label" for="is_confirmed">
                                        Transação confirmada
                                    </label>
                                </div>
                            </div>
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
    
    <!-- Modal Confirmação -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmacaoTexto"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarAcao">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Resumo da Transação -->
    <div class="modal fade" id="modalResumoTransacao" tabindex="-1" aria-labelledby="modalResumoTransacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalResumoTransacaoLabel">
                        <i class="fas fa-info-circle me-2"></i>Resumo da Transação
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Descrição:</label>
                                <p class="form-control-plaintext" id="resumo-descricao"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Valor:</label>
                                <p class="form-control-plaintext" id="resumo-valor"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Tipo:</label>
                                <p class="form-control-plaintext" id="resumo-tipo"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Data:</label>
                                <p class="form-control-plaintext" id="resumo-data"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Conta:</label>
                                <p class="form-control-plaintext" id="resumo-conta"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Categoria:</label>
                                <p class="form-control-plaintext" id="resumo-categoria"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Tipo de Pagamento:</label>
                                <p class="form-control-plaintext" id="resumo-tipo-pagamento"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Status:</label>
                                <p class="form-control-plaintext" id="resumo-confirmado"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">Observações:</label>
                                <div class="alert alert-info" id="resumo-observacoes">
                                    <i class="fas fa-sticky-note me-2"></i>
                                    <span id="resumo-observacoes-texto"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrar categorias por tipo
        document.getElementById('tipo').addEventListener('change', function() {
            const tipo = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            const options = categoriaSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const optionTipo = option.getAttribute('data-tipo');
                    option.style.display = optionTipo === tipo ? 'block' : 'none';
                }
            });
            
            categoriaSelect.value = '';
        });
        
        // Data sempre atual - campo removido
        
        // Editar transação
        function editTransacao(transacao) {
            document.getElementById('modalTransacaoTitle').textContent = 'Editar Transação';
            document.getElementById('action').value = 'update';
            document.getElementById('id').value = transacao.id;
            document.getElementById('tipo').value = transacao.tipo;
            // Data sempre atual - campo removido
            document.getElementById('conta_id').value = transacao.conta_id;
            document.getElementById('categoria_id').value = transacao.categoria_id || '';
            document.getElementById('tipo_pagamento_id').value = transacao.tipo_pagamento_id || '';
            document.getElementById('valor').value = transacao.valor;
            document.getElementById('descricao').value = transacao.descricao;
            // Data de vencimento removida - sempre usa data atual
            document.getElementById('quantidade_parcelas').value = 1;
            document.getElementById('sem_parcelas').checked = true;
            document.getElementById('observacoes').value = transacao.observacoes || '';
            document.getElementById('is_confirmed').checked = transacao.is_confirmed == 1;
            
            new bootstrap.Modal(document.getElementById('modalTransacao')).show();
        }
        
        // Confirmar transação
        function confirmTransacao(id) {
            document.getElementById('confirmacaoTexto').textContent = 'Deseja confirmar esta transação?';
            document.getElementById('confirmarAcao').onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            };
            new bootstrap.Modal(document.getElementById('modalConfirmacao')).show();
        }
        
        // Excluir transação
        function deleteTransacao(id) {
            document.getElementById('confirmacaoTexto').textContent = 'Deseja excluir esta transação? Esta ação não pode ser desfeita.';
            document.getElementById('confirmarAcao').onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            };
            new bootstrap.Modal(document.getElementById('modalConfirmacao')).show();
        }
        
        // Resetar modal
        document.getElementById('modalTransacao').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalTransacaoTitle').textContent = 'Nova Transação';
            document.getElementById('action').value = 'create';
            document.getElementById('formTransacao').reset();
            // Data sempre atual - campo removido
            document.getElementById('quantidade_parcelas').value = 1;
            document.getElementById('sem_parcelas').checked = true;
        });
        
        // Filtros
        function aplicarFiltros() {
            const tipo = document.getElementById('filtroTipo').value;
            const conta = document.getElementById('filtroConta').value;
            const status = document.getElementById('filtroStatus').value;
            const data = document.getElementById('filtroData').value;
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                let show = true;
                
                if (tipo && !row.cells[4].textContent.toLowerCase().includes(tipo)) show = false;
                if (conta && !row.cells[3].textContent.includes(conta)) show = false;
                if (status !== '' && !row.cells[6].textContent.includes(status === '1' ? 'Confirmado' : 'Pendente')) show = false;
                if (data && !row.cells[0].textContent.includes(data.split('-').reverse().join('/'))) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        document.getElementById('filtroTipo').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroConta').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroStatus').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroData').addEventListener('change', aplicarFiltros);

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

        // Preencher modal de resumo da transação
        document.getElementById('modalResumoTransacao').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const transacaoId = button.getAttribute('data-transacao-id');
            const descricao = button.getAttribute('data-descricao');
            const valor = button.getAttribute('data-valor');
            const tipo = button.getAttribute('data-tipo');
            const data = button.getAttribute('data-data');
            const conta = button.getAttribute('data-conta');
            const categoria = button.getAttribute('data-categoria');
            const tipoPagamento = button.getAttribute('data-tipo-pagamento');
            const observacoes = button.getAttribute('data-observacoes');
            const confirmado = button.getAttribute('data-confirmado');

            // Preencher campos do modal
            document.getElementById('resumo-descricao').textContent = descricao;
            document.getElementById('resumo-valor').innerHTML = '<span class="fw-bold text-' + (tipo === 'receita' ? 'success' : 'danger') + '">R$ ' + parseFloat(valor).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '</span>';
            document.getElementById('resumo-tipo').innerHTML = '<span class="badge bg-' + (tipo === 'receita' ? 'success' : 'danger') + '">' + (tipo === 'receita' ? 'Receita' : 'Despesa') + '</span>';
            document.getElementById('resumo-data').textContent = data;
            document.getElementById('resumo-conta').textContent = conta || 'Não informado';
            document.getElementById('resumo-categoria').textContent = categoria || 'Não informado';
            document.getElementById('resumo-tipo-pagamento').textContent = tipoPagamento || 'Não informado';
            document.getElementById('resumo-confirmado').innerHTML = '<span class="badge bg-' + (confirmado === 'Sim' ? 'success' : 'warning') + '">' + confirmado + '</span>';
            document.getElementById('resumo-observacoes-texto').textContent = observacoes;
        });
    </script>
    
    <!-- Script Mobile Menu -->
    <script>
        // JavaScript para menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            try {
                console.log('🚀 Mobile menu script carregado - Transações');
                
                // Verificar se Bootstrap está disponível
                if (typeof bootstrap === 'undefined') {
                    console.error('Bootstrap não está carregado!');
                    return;
                }
                
                // Sincronizar contador de notificações
                function syncNotificationCount() {
                    try {
                        const desktopCount = document.getElementById('notification-count');
                        const mobileCount = document.getElementById('notification-count-mobile');
                        
                        if (desktopCount && mobileCount) {
                            const count = desktopCount.textContent;
                            mobileCount.textContent = count;
                            mobileCount.style.display = count > 0 ? 'inline' : 'none';
                        }
                    } catch (error) {
                        console.error('Erro ao sincronizar notificações:', error);
                    }
                }
                
                // Sincronizar inicialmente
                syncNotificationCount();
                
                // Fechar menu mobile ao clicar em um link
                const mobileLinks = document.querySelectorAll('#mobileSidebar .nav-link');
                console.log('Links encontrados:', mobileLinks.length);
                
                mobileLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        console.log('Link clicado:', this.href);
                        
                        // Fechar o offcanvas após um delay
                        setTimeout(function() {
                            try {
                                const offcanvasElement = document.getElementById('mobileSidebar');
                                if (offcanvasElement) {
                                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                                    if (offcanvas) {
                                        offcanvas.hide();
                                    }
                                }
                            } catch (error) {
                                console.error('Erro ao fechar menu:', error);
                            }
                        }, 150);
                    });
                });
                
                // Adicionar indicador visual para página ativa
                try {
                    const currentPage = window.location.pathname.split('/').pop() || 'transacoes.php';
                    const activeLinks = document.querySelectorAll('#mobileSidebar .nav-link');
                    
                    // Remover todas as classes ativas primeiro
                    activeLinks.forEach(function(link) {
                        link.classList.remove('active');
                    });
                    
                    // Adicionar classe ativa para a página atual
                    activeLinks.forEach(function(link) {
                        const href = link.getAttribute('href');
                        if (href === currentPage) {
                            link.classList.add('active');
                            console.log('Página ativa definida:', href);
                        }
                    });
                } catch (error) {
                    console.error('Erro ao definir página ativa:', error);
                }
                
                console.log('✅ Mobile menu script inicializado com sucesso - Transações');
                
            } catch (error) {
                console.error('❌ Erro geral no script mobile:', error);
            }
        });
    </script>
</body>
</html>