<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Conta.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$conta = new Conta($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';

// Obter contas
$stmt_contas = $conta->read($grupo_id);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

// Processar transferência
if($_POST && isset($_POST['conta_origem']) && isset($_POST['conta_destino']) && isset($_POST['valor'])) {
    $conta_origem_id = $_POST['conta_origem'];
    $conta_destino_id = $_POST['conta_destino'];
    $valor = $_POST['valor'];
    
    if($conta_origem_id == $conta_destino_id) {
        $message = 'A conta de origem deve ser diferente da conta de destino.';
        $message_type = 'danger';
    } elseif($valor <= 0) {
        $message = 'O valor deve ser maior que zero.';
        $message_type = 'danger';
    } else {
        $conta->id = $conta_origem_id;
        
        try {
            if($conta->transferir($conta_destino_id, $valor, $current_user['id'])) {
                $message = 'Transferência realizada com sucesso!';
                $message_type = 'success';
                
                // Recarregar contas para atualizar saldos
                $stmt_contas = $conta->read($grupo_id);
                $contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $message = 'Erro ao realizar transferência.';
                $message_type = 'danger';
            }
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Conta pré-selecionada da URL
$conta_pre_selecionada = $_GET['conta_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferência - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .transfer-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: rgba(255,255,255,0.5);
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
            background: rgba(255,255,255,0.15);
        }
        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .form-label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
        }
        .btn-transfer {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-transfer:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
        }
        .account-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
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
                                <i class="fas fa-exchange-alt me-2 text-primary"></i>
                                Transferência Entre Contas
                            </h2>
                            <a href="contas.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Voltar
                            </a>
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

                        <div class="row justify-content-center">
                            <div class="col-md-8 col-lg-6">
                                <div class="card transfer-card">
                                    <div class="card-body p-4">
                                        <div class="text-center mb-4">
                                            <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                                            <h3 class="mb-0">Transferir Saldo</h3>
                                            <p class="mb-0 opacity-75">Entre suas contas bancárias</p>
                                        </div>

                                        <?php if(empty($contas)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-university fa-3x mb-3 opacity-50"></i>
                                                <h5 class="mb-3">Nenhuma conta disponível</h5>
                                                <p class="mb-4 opacity-75">Você precisa ter pelo menos 2 contas para fazer transferências.</p>
                                                <a href="contas.php" class="btn btn-light">
                                                    <i class="fas fa-plus me-1"></i>Criar Contas
                                                </a>
                                            </div>
                                        <?php elseif(count($contas) < 2): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i>
                                                <h5 class="mb-3">Contas insuficientes</h5>
                                                <p class="mb-4 opacity-75">Você precisa ter pelo menos 2 contas para fazer transferências.</p>
                                                <a href="contas.php" class="btn btn-light">
                                                    <i class="fas fa-plus me-1"></i>Criar Mais Contas
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST">
                                                <div class="mb-4">
                                                    <label for="conta_origem" class="form-label">
                                                        <i class="fas fa-arrow-up me-2"></i>Conta de Origem
                                                    </label>
                                                    <select class="form-select" id="conta_origem" name="conta_origem" required>
                                                        <option value="">Selecione a conta de origem</option>
                                                        <?php foreach($contas as $conta_item): ?>
                                                            <option value="<?= $conta_item['id'] ?>" 
                                                                    <?= $conta_item['id'] == $conta_pre_selecionada ? 'selected' : '' ?>
                                                                    data-saldo="<?= $conta_item['saldo'] ?>">
                                                                <?= htmlspecialchars($conta_item['nome']) ?> 
                                                                (R$ <?= number_format($conta_item['saldo'], 2, ',', '.') ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="saldo-origem" class="account-info mt-2" style="display: none;">
                                                        <small>
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Saldo disponível: <strong id="saldo-disponivel"></strong>
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <label for="conta_destino" class="form-label">
                                                        <i class="fas fa-arrow-down me-2"></i>Conta de Destino
                                                    </label>
                                                    <select class="form-select" id="conta_destino" name="conta_destino" required>
                                                        <option value="">Selecione a conta de destino</option>
                                                        <?php foreach($contas as $conta_item): ?>
                                                            <option value="<?= $conta_item['id'] ?>">
                                                                <?= htmlspecialchars($conta_item['nome']) ?> 
                                                                (R$ <?= number_format($conta_item['saldo'], 2, ',', '.') ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="mb-4">
                                                    <label for="valor" class="form-label">
                                                        <i class="fas fa-dollar-sign me-2"></i>Valor da Transferência (R$)
                                                    </label>
                                                    <input type="number" class="form-control" id="valor" name="valor" 
                                                           step="0.01" min="0.01" required placeholder="0,00">
                                                </div>

                                                <div class="text-center">
                                                    <button type="submit" class="btn btn-transfer">
                                                        <i class="fas fa-exchange-alt me-2"></i>Realizar Transferência
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar saldo da conta de origem
        document.getElementById('conta_origem').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const saldo = selectedOption.getAttribute('data-saldo');
            const saldoElement = document.getElementById('saldo-disponivel');
            const saldoInfo = document.getElementById('saldo-origem');
            
            if(saldo !== null) {
                saldoElement.textContent = 'R$ ' + parseFloat(saldo).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                saldoInfo.style.display = 'block';
            } else {
                saldoInfo.style.display = 'none';
            }
        });

        // Filtrar conta de destino para não mostrar a mesma conta de origem
        document.getElementById('conta_origem').addEventListener('change', function() {
            const contaOrigemId = this.value;
            const contaDestinoSelect = document.getElementById('conta_destino');
            const options = contaDestinoSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if(option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                if(option.value === contaOrigemId) {
                    option.style.display = 'none';
                    if(option.selected) {
                        contaDestinoSelect.value = '';
                    }
                } else {
                    option.style.display = 'block';
                }
            });
        });

        // Validação do valor máximo
        document.getElementById('valor').addEventListener('input', function() {
            const valor = parseFloat(this.value);
            const contaOrigemSelect = document.getElementById('conta_origem');
            const selectedOption = contaOrigemSelect.options[contaOrigemSelect.selectedIndex];
            const saldoMaximo = parseFloat(selectedOption.getAttribute('data-saldo'));
            
            if(valor > saldoMaximo) {
                this.setCustomValidity('Valor excede o saldo disponível na conta de origem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Executar filtro inicial se houver conta pré-selecionada
        document.getElementById('conta_origem').dispatchEvent(new Event('change'));

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
