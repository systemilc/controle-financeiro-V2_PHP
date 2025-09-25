<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Transacao.php';
require_once 'classes/Conta.php';
require_once 'classes/Categoria.php';

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

// Definir grupo_id do usuário atual
$transacao->grupo_id = $current_user['grupo_id'];
$conta->grupo_id = $current_user['grupo_id'];
$categoria->grupo_id = $current_user['grupo_id'];

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$conta_id = $_GET['conta_id'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';

// Buscar dados
$contas = $conta->getAll();
$categorias = $categoria->getAll();

// Resumo financeiro
$resumo = $transacao->getResumo($data_inicio, $data_fim, $current_user['grupo_id']);

// Transações por categoria
$transacoes_categoria = $transacao->getByCategory($data_inicio, $data_fim);

// Transações por conta
$transacoes_conta = $transacao->getByAccount($data_inicio, $data_fim);

// Evolução mensal
$evolucao_mensal = $transacao->getMonthlyEvolution($data_inicio, $data_fim);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
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
                    <h2><i class="fas fa-chart-bar me-2"></i>Relatórios</h2>
                    <button class="btn btn-primary" onclick="exportarRelatorio()">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Data Início</label>
                                <input type="date" class="form-control" name="data_inicio" value="<?= $data_inicio ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data Fim</label>
                                <input type="date" class="form-control" name="data_fim" value="<?= $data_fim ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Conta</label>
                                <select class="form-select" name="conta_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($contas as $conta_item): ?>
                                    <option value="<?= $conta_item['id'] ?>" <?= $conta_id == $conta_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($conta_item['nome'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoria</label>
                                <select class="form-select" name="categoria_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria_item): ?>
                                    <option value="<?= $categoria_item['id'] ?>" <?= $categoria_id == $categoria_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria_item['nome'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filtrar
                                </button>
                                <a href="relatorios.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Receitas</h6>
                                    <h3>R$ <?= number_format($resumo['total_receitas'] ?? 0, 2, ',', '.') ?></h3>
                                </div>
                                <i class="fas fa-arrow-up fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Despesas</h6>
                                    <h3>R$ <?= number_format($resumo['total_despesas'] ?? 0, 2, ',', '.') ?></h3>
                                </div>
                                <i class="fas fa-arrow-down fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card <?= $resumo['saldo'] >= 0 ? 'success' : 'danger' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Saldo</h6>
                                    <h3>R$ <?= number_format($resumo['saldo'] ?? 0, 2, ',', '.') ?></h3>
                                </div>
                                <i class="fas fa-balance-scale fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Transações</h6>
                                    <h3><?= $resumo['total_transacoes'] ?></h3>
                                </div>
                                <i class="fas fa-exchange-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>Receitas vs Despesas</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartReceitasDespesas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Por Categoria</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartCategorias"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-university me-2"></i>Por Conta</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartContas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Evolução Mensal</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartEvolucao"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabelas Detalhadas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list me-2"></i>Top Categorias</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Categoria</th>
                                                <th>Valor</th>
                                                <th>%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transacoes_categoria as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="color-preview" style="background-color: <?= $item['cor'] ?>; width: 15px; height: 15px; border-radius: 50%; margin-right: 8px;"></div>
                                                        <?= htmlspecialchars($item['nome'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td class="text-<?= $item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                    R$ <?= number_format($item['valor'] ?? 0, 2, ',', '.') ?>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-<?= $item['tipo'] == 'receita' ? 'success' : 'danger' ?>" 
                                                             style="width: <?= $item['percentual'] ?>%">
                                                            <?= number_format($item['percentual'] ?? 0, 1) ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-university me-2"></i>Por Conta</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Conta</th>
                                                <th>Receitas</th>
                                                <th>Despesas</th>
                                                <th>Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transacoes_conta as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['nome'] ?? '') ?></td>
                                                <td class="text-success">R$ <?= number_format($item['receitas'] ?? 0, 2, ',', '.') ?></td>
                                                <td class="text-danger">R$ <?= number_format($item['despesas'] ?? 0, 2, ',', '.') ?></td>
                                                <td class="fw-bold <?= ($item['saldo'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    R$ <?= number_format($item['saldo'] ?? 0, 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
        // Gráfico Receitas vs Despesas
        const ctx1 = document.getElementById('chartReceitasDespesas').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Receitas', 'Despesas'],
                datasets: [{
                    data: [<?= $resumo['total_receitas'] ?>, <?= $resumo['total_despesas'] ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Gráfico por Categoria
        const ctx2 = document.getElementById('chartCategorias').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($transacoes_categoria as $item): ?>'<?= $item['nome'] ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Valor',
                    data: [<?php foreach ($transacoes_categoria as $item): ?><?= $item['valor'] ?>,<?php endforeach; ?>],
                    backgroundColor: [<?php foreach ($transacoes_categoria as $item): ?>'<?= $item['cor'] ?>',<?php endforeach; ?>],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico por Conta
        const ctx3 = document.getElementById('chartContas').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($transacoes_conta as $item): ?>'<?= $item['nome'] ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Receitas',
                    data: [<?php foreach ($transacoes_conta as $item): ?><?= $item['receitas'] ?>,<?php endforeach; ?>],
                    backgroundColor: '#28a745'
                }, {
                    label: 'Despesas',
                    data: [<?php foreach ($transacoes_conta as $item): ?><?= $item['despesas'] ?>,<?php endforeach; ?>],
                    backgroundColor: '#dc3545'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico Evolução Mensal
        const ctx4 = document.getElementById('chartEvolucao').getContext('2d');
        new Chart(ctx4, {
            type: 'line',
            data: {
                labels: [<?php foreach ($evolucao_mensal as $item): ?>'<?= $item['mes'] ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Receitas',
                    data: [<?php foreach ($evolucao_mensal as $item): ?><?= $item['receitas'] ?>,<?php endforeach; ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Despesas',
                    data: [<?php foreach ($evolucao_mensal as $item): ?><?= $item['despesas'] ?>,<?php endforeach; ?>],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Exportar relatório
        function exportarRelatorio() {
            window.print();
        }

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