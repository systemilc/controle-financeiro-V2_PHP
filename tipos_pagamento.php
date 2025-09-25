<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/TipoPagamento.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$tipo_pagamento = new TipoPagamento($db);

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
            $tipo_pagamento->nome = $_POST['nome'];
            $tipo_pagamento->grupo_id = $grupo_id;
            $tipo_pagamento->is_income = isset($_POST['is_income']) ? 1 : 0;
            $tipo_pagamento->is_expense = isset($_POST['is_expense']) ? 1 : 0;
            $tipo_pagamento->is_asset = isset($_POST['is_asset']) ? 1 : 0;
            $tipo_pagamento->is_active = isset($_POST['is_active']) ? 1 : 0;
            $tipo_pagamento->icone = $_POST['icone'] ?? 'fas fa-credit-card';
            
            if($tipo_pagamento->create()) {
                $message = 'Tipo de pagamento criado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao criar tipo de pagamento.';
                $message_type = 'danger';
            }
        } elseif($_POST['action'] == 'update') {
            $tipo_pagamento->id = $_POST['id'];
            $tipo_pagamento->nome = $_POST['nome'];
            $tipo_pagamento->is_income = isset($_POST['is_income']) ? 1 : 0;
            $tipo_pagamento->is_expense = isset($_POST['is_expense']) ? 1 : 0;
            $tipo_pagamento->is_asset = isset($_POST['is_asset']) ? 1 : 0;
            $tipo_pagamento->is_active = isset($_POST['is_active']) ? 1 : 0;
            $tipo_pagamento->icone = $_POST['icone'] ?? 'fas fa-credit-card';
            
            if($tipo_pagamento->update()) {
                $message = 'Tipo de pagamento atualizado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao atualizar tipo de pagamento.';
                $message_type = 'danger';
            }
        }
    }
}

// Processar exclusão
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $tipo_pagamento->id = $_GET['id'];
    
    if($tipo_pagamento->canDelete()) {
        if($tipo_pagamento->delete()) {
            $message = 'Tipo de pagamento excluído com sucesso!';
            $message_type = 'success';
        } else {
            $message = 'Erro ao excluir tipo de pagamento.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Não é possível excluir este tipo de pagamento pois ele possui transações associadas.';
        $message_type = 'warning';
    }
}

// Processar ativar/desativar
if(isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $tipo_pagamento->id = $_GET['id'];
    
    if($tipo_pagamento->toggleStatus()) {
        $message = 'Status do tipo de pagamento alterado com sucesso!';
        $message_type = 'success';
    } else {
        $message = 'Erro ao alterar status do tipo de pagamento.';
        $message_type = 'danger';
    }
}

// Obter tipos de pagamento
$stmt_tipos = $tipo_pagamento->read($grupo_id);
$tipos = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Pagamento - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
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
        .type-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #007bff;
            transition: all 0.3s ease;
        }
        .type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .type-card.inactive {
            opacity: 0.6;
            border-left-color: #6c757d;
        }
        .type-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .badge-usage {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }
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
                                <i class="fas fa-credit-card me-2 text-primary"></i>
                                Tipos de Pagamento
                            </h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipoPagamento">
                                    <i class="fas fa-plus me-1"></i>Novo Tipo
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

                        <!-- Lista de Tipos de Pagamento -->
                        <div class="row">
                            <?php if(empty($tipos)): ?>
                                <div class="col-12">
                                    <div class="card card-modern">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhum tipo de pagamento encontrado</h5>
                                            <p class="text-muted">Comece criando seus primeiros tipos de pagamento.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipoPagamento">
                                                <i class="fas fa-plus me-1"></i>Criar Primeiro Tipo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($tipos as $tipo): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="type-card <?= !$tipo['is_active'] ? 'inactive' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="type-icon me-3" style="background-color: #007bff;">
                                                        <i class="<?= $tipo['icone'] ?? 'fas fa-credit-card' ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="card-title mb-1"><?= htmlspecialchars($tipo['nome']) ?></h5>
                                                        <small class="text-muted"><?= htmlspecialchars($tipo['grupo_nome']) ?></small>
                                                    </div>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editarTipoPagamento(<?= htmlspecialchars(json_encode($tipo)) ?>)">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="tipos_pagamento.php?action=toggle&id=<?= $tipo['id'] ?>">
                                                                <i class="fas fa-power-off me-2"></i>
                                                                <?= $tipo['is_active'] ? 'Desativar' : 'Ativar' ?>
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="tipos_pagamento.php?action=delete&id=<?= $tipo['id'] ?>" 
                                                               onclick="return confirm('Tem certeza que deseja excluir este tipo de pagamento?')">
                                                                <i class="fas fa-trash me-2"></i>Excluir
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php if($tipo['is_income']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-arrow-up me-1"></i>Entrada
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if($tipo['is_expense']): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-arrow-down me-1"></i>Saída
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if($tipo['is_asset']): ?>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-building me-1"></i>Ativo
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-<?= $tipo['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $tipo['is_active'] ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d/m/Y', strtotime($tipo['created_at'])) ?>
                                                    </small>
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

    <!-- Modal para Novo/Editar Tipo de Pagamento -->
    <div class="modal fade" id="modalTipoPagamento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Tipo de Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formTipoPagamento">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="tipoPagamentoId">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Tipo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   placeholder="Ex: Dinheiro, Cartão de Crédito, PIX">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ícone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="icon-preview" class="fas fa-credit-card"></i></span>
                                <input type="text" class="form-control" name="icone" id="icone" placeholder="fas fa-credit-card" value="fas fa-credit-card">
                            </div>
                            <div class="form-text">
                                <small>Use classes do Font Awesome (ex: fas fa-credit-card, fas fa-money-bill-wave, fas fa-university)</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Aplicabilidade</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_income" name="is_income">
                                <label class="form-check-label" for="is_income">
                                    <i class="fas fa-arrow-up text-success me-1"></i>Pode ser usado para Entradas (Receitas)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_expense" name="is_expense">
                                <label class="form-check-label" for="is_expense">
                                    <i class="fas fa-arrow-down text-danger me-1"></i>Pode ser usado para Saídas (Despesas)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_asset" name="is_asset">
                                <label class="form-check-label" for="is_asset">
                                    <i class="fas fa-building text-info me-1"></i>É considerado um Ativo
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-power-off text-success me-1"></i>Tipo ativo (aparece nas transações)
                            </label>
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
                                <i class="fas fa-lightbulb me-2"></i>Ícones Sugeridos para Tipos de Pagamento
                            </h6>
                            
                            <div class="row">
                                <div class="col-12">
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-credit-card text-primary me-2"></i>fas fa-credit-card (Cartão)</li>
                                        <li><i class="fas fa-money-bill-wave text-primary me-2"></i>fas fa-money-bill-wave (Dinheiro)</li>
                                        <li><i class="fas fa-university text-primary me-2"></i>fas fa-university (Transferência)</li>
                                        <li><i class="fas fa-hand-holding-usd text-primary me-2"></i>fas fa-hand-holding-usd (PIX)</li>
                                        <li><i class="fas fa-receipt text-primary me-2"></i>fas fa-receipt (Boleto)</li>
                                        <li><i class="fas fa-mobile-alt text-primary me-2"></i>fas fa-mobile-alt (Celular)</li>
                                        <li><i class="fas fa-desktop text-primary me-2"></i>fas fa-desktop (Internet)</li>
                                        <li><i class="fas fa-exchange-alt text-primary me-2"></i>fas fa-exchange-alt (Débito)</li>
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
        function editarTipoPagamento(tipo) {
            document.getElementById('modalTitulo').textContent = 'Editar Tipo de Pagamento';
            document.getElementById('action').value = 'update';
            document.getElementById('tipoPagamentoId').value = tipo.id;
            document.getElementById('nome').value = tipo.nome;
            document.getElementById('icone').value = tipo.icone || 'fas fa-credit-card';
            document.getElementById('icon-preview').className = tipo.icone || 'fas fa-credit-card';
            document.getElementById('is_income').checked = tipo.is_income == 1;
            document.getElementById('is_expense').checked = tipo.is_expense == 1;
            document.getElementById('is_asset').checked = tipo.is_asset == 1;
            document.getElementById('is_active').checked = tipo.is_active == 1;
            
            var modal = new bootstrap.Modal(document.getElementById('modalTipoPagamento'));
            modal.show();
        }

        // Atualizar preview do ícone
        document.getElementById('icone').addEventListener('input', function() {
            const iconPreview = document.getElementById('icon-preview');
            iconPreview.className = this.value || 'fas fa-credit-card';
        });

        // Limpar formulário quando modal é fechado
        document.getElementById('modalTipoPagamento').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitulo').textContent = 'Novo Tipo de Pagamento';
            document.getElementById('action').value = 'create';
            document.getElementById('tipoPagamentoId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('icone').value = 'fas fa-credit-card';
            document.getElementById('icon-preview').className = 'fas fa-credit-card';
            document.getElementById('is_income').checked = false;
            document.getElementById('is_expense').checked = false;
            document.getElementById('is_asset').checked = false;
            document.getElementById('is_active').checked = true;
        });

        // Validação: pelo menos uma aplicabilidade deve ser selecionada
        document.getElementById('formTipoPagamento').addEventListener('submit', function(e) {
            const isIncome = document.getElementById('is_income').checked;
            const isExpense = document.getElementById('is_expense').checked;
            const isAsset = document.getElementById('is_asset').checked;
            
            if(!isIncome && !isExpense && !isAsset) {
                e.preventDefault();
                alert('Selecione pelo menos uma aplicabilidade para o tipo de pagamento.');
                return false;
            }
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
    
    <!-- Script Mobile Menu -->
    <script>
        // JavaScript para menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            try {
                console.log('🚀 Mobile menu script carregado');
                
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
                    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
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
                
                console.log('✅ Mobile menu script inicializado com sucesso');
                
            } catch (error) {
                console.error('❌ Erro geral no script mobile:', error);
            }
        });
    </script>
</body>
</html>
