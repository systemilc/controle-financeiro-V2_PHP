<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Notificacao.php';
require_once 'config/timezone.php';

// Verificar autenticação
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Instanciar classe de notificações
$notificacao = new Notificacao();
$notificacao->grupo_id = $grupo_id;

// Parâmetros de paginação e filtros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Notificações por página
$offset = ($page - 1) * $limit;

$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_prioridade = isset($_GET['prioridade']) ? $_GET['prioridade'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Buscar notificações com filtros e paginação
$notificacoes = $notificacao->getAllWithFilters($filtro_tipo, $filtro_prioridade, $filtro_status, $limit, $offset);
$total_notificacoes = $notificacao->getTotalCount($filtro_tipo, $filtro_prioridade, $filtro_status);
$total_pages = ceil($total_notificacoes / $limit);

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'marcar_lida':
                $notificacao_id = $_POST['notificacao_id'];
                if ($notificacao->marcarComoLida($notificacao_id)) {
                    $message = 'Notificação marcada como lida!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao marcar notificação!';
                    $message_type = 'danger';
                }
                break;
            case 'marcar_todas_lidas':
                if ($notificacao->marcarTodasComoLidas($current_user['id'])) {
                    $message = 'Todas as notificações foram marcadas como lidas!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao marcar notificações!';
                    $message_type = 'danger';
                }
                break;
            case 'excluir':
                $notificacao_id = $_POST['notificacao_id'];
                if ($notificacao->delete($notificacao_id)) {
                    $message = 'Notificação excluída!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao excluir notificação!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar notificações
$notificacoes = $notificacao->getAll($current_user['id']);
$total_nao_lidas = $notificacao->contarNaoLidas($current_user['id']);

// Gerar notificações automáticas (executar apenas uma vez por dia)
$ultima_execucao = $_SESSION['ultima_execucao_notificacoes'] ?? null;
$hoje = date('Y-m-d');
if ($ultima_execucao !== $hoje) {
    $notificacoes_criadas = $notificacao->gerarNotificacoesAutomaticas();
    $_SESSION['ultima_execucao_notificacoes'] = $hoje;
    if ($notificacoes_criadas > 0) {
        $message = "{$notificacoes_criadas} novas notificações geradas automaticamente!";
        $message_type = 'info';
    }
    // Recarregar notificações após gerar novas
    $notificacoes = $notificacao->getAll($current_user['id']);
    $total_nao_lidas = $notificacao->contarNaoLidas($current_user['id']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Controle Financeiro</title>
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
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .notification-card {
            border-left: 4px solid #dee2e6;
            margin-bottom: 15px;
        }
        .notification-card.unread {
            border-left-color: #007bff;
            background: #f8f9ff;
        }
        .notification-card.high {
            border-left-color: #dc3545;
        }
        .notification-card.critical {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .notification-card.medium {
            border-left-color: #ffc107;
        }
        .notification-card.low {
            border-left-color: #28a745;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        .icon-vencimento-proximo { background: #ffc107; }
        .icon-vencimento-atrasado { background: #dc3545; }
        .icon-pagamento-confirmado { background: #28a745; }
        .icon-saldo-baixo { background: #fd7e14; }
        .icon-meta-atingida { background: #17a2b8; }
        .icon-transferencia-realizada { background: #6f42c1; }
        .icon-conta-criada { background: #20c997; }
        .icon-categoria-criada { background: #e83e8c; }
        .icon-sistema { background: #6c757d; }
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .badge-priority {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
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
                                <i class="fas fa-bell text-primary me-2"></i>Notificações
                            </h2>
                            <p class="text-muted mb-0">Acompanhe todos os eventos do sistema</p>
                        </div>
                        <div>
                            <?php if ($total_nao_lidas > 0): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="marcar_todas_lidas">
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="fas fa-check-double me-2"></i>Marcar Todas como Lidas
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center p-3 bg-primary text-white">
                                <div class="card-body">
                                    <i class="fas fa-bell fa-2x mb-2"></i>
                                    <h4 class="mb-1"><?= count($notificacoes) ?></h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center p-3 bg-warning text-white">
                                <div class="card-body">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                    <h4 class="mb-1"><?= $total_nao_lidas ?></h4>
                                    <small>Não Lidas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center p-3 bg-success text-white">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h4 class="mb-1"><?= count($notificacoes) - $total_nao_lidas ?></h4>
                                    <small>Lidas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center p-3 bg-info text-white">
                                <div class="card-body">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <h4 class="mb-1"><?= date('H:i') ?></h4>
                                    <small>Última Atualização</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="">Todos os tipos</option>
                                        <option value="vencimento_proximo" <?= $filtro_tipo == 'vencimento_proximo' ? 'selected' : '' ?>>Vencimento Próximo</option>
                                        <option value="vencimento_atrasado" <?= $filtro_tipo == 'vencimento_atrasado' ? 'selected' : '' ?>>Vencimento Atrasado</option>
                                        <option value="pagamento_confirmado" <?= $filtro_tipo == 'pagamento_confirmado' ? 'selected' : '' ?>>Pagamento Confirmado</option>
                                        <option value="saldo_baixo" <?= $filtro_tipo == 'saldo_baixo' ? 'selected' : '' ?>>Saldo Baixo</option>
                                        <option value="meta_atingida" <?= $filtro_tipo == 'meta_atingida' ? 'selected' : '' ?>>Meta Atingida</option>
                                        <option value="transferencia_realizada" <?= $filtro_tipo == 'transferencia_realizada' ? 'selected' : '' ?>>Transferência Realizada</option>
                                        <option value="conta_criada" <?= $filtro_tipo == 'conta_criada' ? 'selected' : '' ?>>Conta Criada</option>
                                        <option value="categoria_criada" <?= $filtro_tipo == 'categoria_criada' ? 'selected' : '' ?>>Categoria Criada</option>
                                        <option value="sistema" <?= $filtro_tipo == 'sistema' ? 'selected' : '' ?>>Sistema</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Prioridade</label>
                                    <select name="prioridade" class="form-select">
                                        <option value="">Todas as prioridades</option>
                                        <option value="baixa" <?= $filtro_prioridade == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                                        <option value="media" <?= $filtro_prioridade == 'media' ? 'selected' : '' ?>>Média</option>
                                        <option value="alta" <?= $filtro_prioridade == 'alta' ? 'selected' : '' ?>>Alta</option>
                                        <option value="critica" <?= $filtro_prioridade == 'critica' ? 'selected' : '' ?>>Crítica</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">Todos os status</option>
                                        <option value="nao_lidas" <?= $filtro_status == 'nao_lidas' ? 'selected' : '' ?>>Não Lidas</option>
                                        <option value="lidas" <?= $filtro_status == 'lidas' ? 'selected' : '' ?>>Lidas</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Filtrar
                                        </button>
                                        <a href="notificacoes.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Limpar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Notificações -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($notificacoes)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Nenhuma notificação!</h5>
                                    <p class="text-muted">Você está em dia com todas as informações.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notificacoes as $notif): ?>
                                    <div class="notification-card card p-3 <?= $notif['is_lida'] ? '' : 'unread' ?> <?= $notif['prioridade'] ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="notification-icon icon-<?= $notif['tipo'] ?> me-3">
                                                <?php
                                                $icons = [
                                                    'vencimento_proximo' => 'fas fa-clock',
                                                    'vencimento_atrasado' => 'fas fa-exclamation-triangle',
                                                    'pagamento_confirmado' => 'fas fa-check-circle',
                                                    'saldo_baixo' => 'fas fa-wallet',
                                                    'meta_atingida' => 'fas fa-trophy',
                                                    'transferencia_realizada' => 'fas fa-exchange-alt',
                                                    'conta_criada' => 'fas fa-university',
                                                    'categoria_criada' => 'fas fa-tag',
                                                    'sistema' => 'fas fa-cog'
                                                ];
                                                echo '<i class="' . ($icons[$notif['tipo']] ?? 'fas fa-bell') . '"></i>';
                                                ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-1 fw-bold"><?= $notif['titulo'] ?></h6>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge badge-priority bg-<?= $notif['prioridade'] == 'critica' ? 'danger' : ($notif['prioridade'] == 'alta' ? 'warning' : ($notif['prioridade'] == 'media' ? 'info' : 'success')) ?>">
                                                            <?= ucfirst($notif['prioridade']) ?>
                                                        </span>
                                                        <?php if (!$notif['is_lida']): ?>
                                                            <span class="badge bg-primary">Nova</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="mb-2 text-muted"><?= $notif['mensagem'] ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="notification-time">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('d/m/Y H:i', strtotime($notif['data_notificacao'])) ?>
                                                    </small>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if (!$notif['is_lida']): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="marcar_lida">
                                                                <input type="hidden" name="notificacao_id" value="<?= $notif['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-success" title="Marcar como lida">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="excluir">
                                                            <input type="hidden" name="notificacao_id" value="<?= $notif['id'] ?>">
                                                            <button type="submit" class="btn btn-outline-danger" 
                                                                    onclick="return confirm('Excluir esta notificação?')" title="Excluir">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Paginação -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Paginação de notificações" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Página anterior -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&tipo=<?= $filtro_tipo ?>&prioridade=<?= $filtro_prioridade ?>&status=<?= $filtro_status ?>">
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
                                    <a class="page-link" href="?page=<?= $i ?>&tipo=<?= $filtro_tipo ?>&prioridade=<?= $filtro_prioridade ?>&status=<?= $filtro_status ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Página seguinte -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&tipo=<?= $filtro_tipo ?>&prioridade=<?= $filtro_prioridade ?>&status=<?= $filtro_status ?>">
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
                                Mostrando <?= $offset + 1 ?> a <?= min($offset + $limit, $total_notificacoes) ?> 
                                de <?= $total_notificacoes ?> notificações
                                (Página <?= $page ?> de <?= $total_pages ?>)
                            </small>
                        </div>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
