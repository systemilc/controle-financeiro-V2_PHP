<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Transacao.php';
require_once 'classes/Conta.php';
require_once 'classes/Categoria.php';
require_once 'classes/TipoPagamento.php';
require_once 'config/timezone.php';

// Verificar autenticação
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Instanciar classes com grupo_id definido
$transacao = new Transacao();
$transacao->grupo_id = $grupo_id;

$conta = new Conta();
$conta->grupo_id = $grupo_id;

$categoria = new Categoria();
$categoria->grupo_id = $grupo_id;

$tipo_pagamento = new TipoPagamento();
$tipo_pagamento->grupo_id = $grupo_id;

// Parâmetros de paginação e ordenação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Transações por página
$offset = ($page - 1) * $limit;

$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_transacao';
$order_direction = isset($_GET['order_direction']) ? $_GET['order_direction'] : 'DESC';

// Verificar mensagens de sucesso via GET
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'confirm_success':
            $message = 'Transação confirmada com sucesso!';
            $message_type = 'success';
            break;
        case 'delete_success':
            $message = 'Transação excluída com sucesso!';
            $message_type = 'success';
            break;
    }
}

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'confirm':
                $transacao_id = $_POST['transacao_id'];
                $transacao->id = $transacao_id;
                if ($transacao->confirm()) {
                    $message = 'Transação confirmada com sucesso!';
                    $message_type = 'success';
                    // Redirecionar para evitar reenvio do formulário
                    header('Location: pendentes.php?msg=confirm_success');
                    exit;
                } else {
                    $message = 'Erro ao confirmar transação!';
                    $message_type = 'danger';
                }
                break;
            case 'delete':
                $transacao_id = $_POST['transacao_id'];
                $transacao->id = $transacao_id;
                if ($transacao->delete()) {
                    $message = 'Transação excluída com sucesso!';
                    $message_type = 'success';
                    // Redirecionar para evitar reenvio do formulário
                    header('Location: pendentes.php?msg=delete_success');
                    exit;
                } else {
                    $message = 'Erro ao excluir transação!';
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
    // Admin vê todas as transações pendentes
    $transacoes_pendentes = $transacao->getPendentesForAdmin($order_by, $order_direction);
    $total_pendentes = count($transacoes_pendentes);
    $total_pages = ceil($total_pendentes / $limit);
    $transacoes_pendentes = array_slice($transacoes_pendentes, $offset, $limit);
} else {
    // Usuário normal vê apenas do seu grupo
    $transacoes_pendentes = $transacao->getPendentesWithPagination($order_by, $order_direction, $limit, $offset);
    $total_pendentes = $transacao->getPendentesCount();
    $total_pages = ceil($total_pendentes / $limit);
}

$contas = $conta->getAll();
$categorias = $categoria->getAll();
$tipos_pagamento = $tipo_pagamento->getAll();

// Calcular estatísticas
$total_valor_pendente = 0;
$total_receitas_pendentes = 0;
$total_despesas_pendentes = 0;

foreach ($transacoes_pendentes as $transacao_item) {
    $total_valor_pendente += $transacao_item['valor'];
    if ($transacao_item['tipo'] == 'receita') {
        $total_receitas_pendentes += $transacao_item['valor'];
    } else {
        $total_despesas_pendentes += $transacao_item['valor'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações Pendentes - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            min-height: 80vh;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #ffc107;
        }
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left-color: #ffc107;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
        .badge-pendente {
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            color: white;
            font-weight: 500;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
        }
        .stats-card.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .stats-card.danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        .nav-link .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
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
            <div class="col-md-9 col-lg-10 p-4">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold mb-1">
                                <i class="fas fa-clock text-warning me-2"></i>Transações Pendentes
                            </h2>
                            <p class="text-muted mb-0">Gerencie transações não confirmadas</p>
                        </div>
                        <div>
                            <a href="transacoes.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar às Transações
                            </a>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h4 class="mb-1"><?= $total_pendentes ?></h4>
                                    <small>Total Pendentes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card warning text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                    <h4 class="mb-1">R$ <?= number_format($total_valor_pendente, 2, ',', '.') ?></h4>
                                    <small>Valor Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card success text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                                    <h4 class="mb-1">R$ <?= number_format($total_receitas_pendentes, 2, ',', '.') ?></h4>
                                    <small>Receitas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card danger text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-arrow-down fa-2x mb-2"></i>
                                    <h4 class="mb-1">R$ <?= number_format($total_despesas_pendentes, 2, ',', '.') ?></h4>
                                    <small>Despesas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Transações Pendentes -->
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
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($transacoes_pendentes)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                    <h5 class="text-muted">Nenhuma transação pendente!</h5>
                                                    <p class="text-muted">Todas as transações estão confirmadas.</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($transacoes_pendentes as $transacao_item): ?>
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
                                                        <span class="badge" style="background-color: <?= $transacao_item['categoria_cor'] ?? '#6c757d' ?>; color: white;">
                                                            <?= htmlspecialchars($transacao_item['categoria_nome'] ?? '') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($transacao_item['conta_nome'] ?? '') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $transacao_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                        <?= $transacao_item['tipo'] == 'receita' ? 'Receita' : 'Despesa' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-<?= $transacao_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                        R$ <?= number_format($transacao_item['valor'], 2, ',', '.') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pendente">
                                                        <i class="fas fa-clock me-1"></i>Pendente
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="confirm">
                                                            <input type="hidden" name="transacao_id" value="<?= $transacao_item['id'] ?>">
                                                            <button type="submit" class="btn btn-success btn-sm" 
                                                                    onclick="return confirm('Confirmar esta transação?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="transacao_id" value="<?= $transacao_item['id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                                    onclick="return confirm('Excluir esta transação?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Paginação de transações pendentes" class="mt-4">
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
                                    Mostrando <?= $offset + 1 ?> a <?= min($offset + $limit, $total_pendentes) ?> 
                                    de <?= $total_pendentes ?> transações pendentes
                                    (Página <?= $page ?> de <?= $total_pages ?>)
                                </small>
                            </div>
                        </nav>
                        <?php endif; ?>
                    </div>
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
</body>
</html>
