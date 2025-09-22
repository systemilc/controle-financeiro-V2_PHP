<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Conta.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Instanciar classe com grupo_id definido
$conta = new Conta($db);
$conta->grupo_id = $grupo_id;

$message = '';
$message_type = '';

// Processar formulário
if($_POST) {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'create') {
            $conta->nome = $_POST['nome'];
            $conta->grupo_id = $grupo_id;
            $conta->saldo = $_POST['saldo'] ?? 0;
            $conta->icone = $_POST['icone'] ?? 'fas fa-university';
            
            if($conta->create()) {
                $message = 'Conta criada com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao criar conta.';
                $message_type = 'danger';
            }
        } elseif($_POST['action'] == 'update') {
            $conta->id = $_POST['id'];
            $conta->nome = $_POST['nome'];
            $conta->saldo = $_POST['saldo'];
            $conta->icone = $_POST['icone'] ?? 'fas fa-university';
            
            if($conta->update()) {
                $message = 'Conta atualizada com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao atualizar conta.';
                $message_type = 'danger';
            }
        }
    }
}

// Processar exclusão
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $conta->id = $_GET['id'];
    
    if($conta->canDelete()) {
        if($conta->delete()) {
            $message = 'Conta excluída com sucesso!';
            $message_type = 'success';
        } else {
            $message = 'Erro ao excluir conta.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Não é possível excluir esta conta pois ela possui transações associadas ou é a última conta do grupo.';
        $message_type = 'warning';
    }
}

// Obter contas
$stmt_contas = $conta->read($grupo_id);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

// Calcular saldo total
$saldo_total = 0;
foreach($contas as $c) {
    $saldo_total += $c['saldo'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
        .account-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .account-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .stat-item h5 {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .stat-item h6 {
            font-size: 0.8rem;
            font-weight: 500;
        }
        .account-card .balance {
            font-size: 2rem;
            font-weight: bold;
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
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-university me-2 text-primary"></i>
                                Contas Bancárias
                            </h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConta">
                                    <i class="fas fa-plus me-1"></i>Nova Conta
                                </button>
                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalAjudaIcones">
                                    <i class="fas fa-question-circle me-1"></i>Como encontrar ícones
                                </button>
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

                        <!-- Resumo Total -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="account-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1">Saldo Total</h5>
                                            <div class="balance">R$ <?= number_format($saldo_total, 2, ',', '.') ?></div>
                                        </div>
                                        <div>
                                            <i class="fas fa-wallet fa-3x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Contas -->
                        <div class="row">
                            <?php if(empty($contas)): ?>
                                <div class="col-12">
                                    <div class="card card-modern">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-university fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhuma conta encontrada</h5>
                                            <p class="text-muted">Comece criando sua primeira conta bancária.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConta">
                                                <i class="fas fa-plus me-1"></i>Criar Primeira Conta
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($contas as $conta_item): ?>
                                    <?php
                                    // Buscar estatísticas da conta
                                    $query_stats = "SELECT 
                                        COUNT(*) as total_transacoes,
                                        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                                        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
                                        SUM(CASE WHEN tipo = 'receita' AND MONTH(data_transacao) = MONTH(CURDATE()) AND YEAR(data_transacao) = YEAR(CURDATE()) THEN valor ELSE 0 END) as receitas_mes,
                                        SUM(CASE WHEN tipo = 'despesa' AND MONTH(data_transacao) = MONTH(CURDATE()) AND YEAR(data_transacao) = YEAR(CURDATE()) THEN valor ELSE 0 END) as despesas_mes
                                        FROM transacoes t
                                        WHERE conta_id = :conta_id 
                                        AND usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";
                                    
                                    $stmt_stats = $database->getConnection()->prepare($query_stats);
                                    $stmt_stats->bindParam(':conta_id', $conta_item['id']);
                                    $stmt_stats->bindParam(':grupo_id', $grupo_id);
                                    $stmt_stats->execute();
                                    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card card-modern h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="account-icon me-3" style="background-color: #007bff;">
                                                            <i class="<?= $conta_item['icone'] ?? 'fas fa-university' ?>"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title mb-1"><?= htmlspecialchars($conta_item['nome']) ?></h5>
                                                            <small class="text-muted"><?= htmlspecialchars($conta_item['grupo_nome']) ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button class="dropdown-item" onclick="editarConta(<?= htmlspecialchars(json_encode($conta_item)) ?>)">
                                                                    <i class="fas fa-edit me-2"></i>Editar
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="transferencia.php?conta_id=<?= $conta_item['id'] ?>">
                                                                    <i class="fas fa-exchange-alt me-2"></i>Transferir
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="contas.php?action=delete&id=<?= $conta_item['id'] ?>" 
                                                                   onclick="return confirm('Tem certeza que deseja excluir esta conta?')">
                                                                    <i class="fas fa-trash me-2"></i>Excluir
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                
                                                <div class="account-balance mb-3">
                                                    <h4 class="mb-0 <?= $conta_item['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        R$ <?= number_format($conta_item['saldo'], 2, ',', '.') ?>
                                                    </h4>
                                                    <small class="text-muted">Saldo atual</small>
                                                </div>

                                                <div class="account-stats">
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <div class="stat-item">
                                                                <h6 class="text-muted mb-1">Transações</h6>
                                                                <h5 class="mb-0 text-primary"><?= $stats['total_transacoes'] ?></h5>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="stat-item">
                                                                <h6 class="text-muted mb-1">Receitas</h6>
                                                                <h5 class="mb-0 text-success">
                                                                    R$ <?= number_format($stats['total_receitas'] ?? 0, 0, ',', '.') ?>
                                                                </h5>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="stat-item">
                                                                <h6 class="text-muted mb-1">Despesas</h6>
                                                                <h5 class="mb-0 text-danger">
                                                                    R$ <?= number_format($stats['total_despesas'] ?? 0, 0, ',', '.') ?>
                                                                </h5>
                                                            </div>
                                                        </div>
                                                    </div>
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

    <!-- Modal para Nova/Editar Conta -->
    <div class="modal fade" id="modalConta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Nova Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formConta">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="contaId">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Conta</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   placeholder="Ex: Conta Corrente, Poupança, Cartão de Crédito">
                        </div>
                        
                        <div class="mb-3">
                            <label for="saldo" class="form-label">Saldo Inicial (R$)</label>
                            <input type="number" class="form-control" id="saldo" name="saldo" 
                                   step="0.01" value="0" placeholder="0,00">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ícone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="icon-preview" class="fas fa-university"></i></span>
                                <input type="text" class="form-control" name="icone" id="icone" placeholder="fas fa-university" value="fas fa-university">
                            </div>
                            <div class="form-text">
                                <small>Use classes do Font Awesome (ex: fas fa-university, fas fa-credit-card, fas fa-piggy-bank)</small>
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

    <!-- Modal de Ajuda para Ícones -->
    <div class="modal fade" id="modalAjudaIcones" tabindex="-1" aria-labelledby="modalAjudaIconesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalAjudaIconesLabel">
                        <i class="fas fa-question-circle me-2"></i>Como encontrar ícones do Font Awesome
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-globe me-2"></i>Site Oficial do Font Awesome
                            </h6>
                            <p class="mb-3">Acesse o site oficial para encontrar todos os ícones disponíveis:</p>
                            <a href="https://fontawesome.com/icons" target="_blank" class="btn btn-outline-primary btn-sm mb-3">
                                <i class="fas fa-external-link-alt me-2"></i>Abrir Font Awesome Icons
                            </a>
                            
                            <h6 class="fw-bold text-success mb-3">
                                <i class="fas fa-search me-2"></i>Como usar:
                            </h6>
                            <ol class="mb-3">
                                <li>Digite uma palavra-chave na busca</li>
                                <li>Clique no ícone desejado</li>
                                <li>Copie o código da classe</li>
                                <li>Cole no campo "Ícone"</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-warning mb-3">
                                <i class="fas fa-lightbulb me-2"></i>Ícones Sugeridos para Contas
                            </h6>
                            
                            <div class="row">
                                <div class="col-12">
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-university text-primary me-2"></i>fas fa-university (Banco)</li>
                                        <li><i class="fas fa-credit-card text-primary me-2"></i>fas fa-credit-card (Cartão)</li>
                                        <li><i class="fas fa-piggy-bank text-primary me-2"></i>fas fa-piggy-bank (Poupança)</li>
                                        <li><i class="fas fa-wallet text-primary me-2"></i>fas fa-wallet (Dinheiro)</li>
                                        <li><i class="fas fa-coins text-primary me-2"></i>fas fa-coins (Investimento)</li>
                                        <li><i class="fas fa-landmark text-primary me-2"></i>fas fa-landmark (Instituição)</li>
                                        <li><i class="fas fa-building text-primary me-2"></i>fas fa-building (Empresa)</li>
                                        <li><i class="fas fa-hand-holding-usd text-primary me-2"></i>fas fa-hand-holding-usd (Empréstimo)</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Dica:</strong> Sempre use o prefixo "fas fa-" antes do nome do ícone.
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
        function editarConta(conta) {
            document.getElementById('modalTitulo').textContent = 'Editar Conta';
            document.getElementById('action').value = 'update';
            document.getElementById('contaId').value = conta.id;
            document.getElementById('nome').value = conta.nome;
            document.getElementById('saldo').value = conta.saldo;
            document.getElementById('icone').value = conta.icone || 'fas fa-university';
            document.getElementById('icon-preview').className = conta.icone || 'fas fa-university';
            
            var modal = new bootstrap.Modal(document.getElementById('modalConta'));
            modal.show();
        }

        // Limpar formulário quando modal é fechado
        document.getElementById('modalConta').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitulo').textContent = 'Nova Conta';
            document.getElementById('action').value = 'create';
            document.getElementById('contaId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('saldo').value = '0';
        });

        // Atualizar preview do ícone
        document.getElementById('icone').addEventListener('input', function() {
            const iconPreview = document.getElementById('icon-preview');
            iconPreview.className = this.value || 'fas fa-university';
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
