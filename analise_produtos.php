<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Produto.php';
require_once 'classes/Transacao.php';
require_once 'classes/Fornecedor.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$produto = new Produto($db);
$transacao = new Transacao($db);
$fornecedor = new Fornecedor($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Obter dados para análise
$produtos = $produto->read($grupo_id)->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas gerais
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT p.id) as total_produtos,
        COUNT(DISTINCT ic.id) as total_itens,
        COALESCE(SUM(ic.preco_total), 0) as total_gasto_itens,
        COALESCE(AVG(ic.preco_total), 0) as media_gasto_itens
    FROM produtos p
    LEFT JOIN itens_compra ic ON ic.produto_id = p.id
    LEFT JOIN compras c ON ic.compra_id = c.id
    WHERE p.grupo_id = ?
");
$stmt->execute([$grupo_id]);
$stats_itens = $stmt->fetch(PDO::FETCH_ASSOC);

// Estatísticas de transações (para produtos importados)
$stmt = $db->prepare("
    SELECT 
        COUNT(t.id) as total_transacoes,
        SUM(t.valor) as total_gasto_transacoes,
        AVG(t.valor) as media_gasto_transacoes
    FROM transacoes t
    WHERE t.tipo = 'despesa' AND t.descricao LIKE 'Compra -%'
");
$stmt->execute();
$stats_transacoes = $stmt->fetch(PDO::FETCH_ASSOC);

// Combinar estatísticas
$stats = [
    'total_produtos' => $stats_itens['total_produtos'],
    'total_itens' => $stats_itens['total_itens'] + $stats_transacoes['total_transacoes'],
    'total_gasto' => $stats_itens['total_gasto_itens'] + $stats_transacoes['total_gasto_transacoes'],
    'media_gasto' => ($stats_itens['total_gasto_itens'] + $stats_transacoes['total_gasto_transacoes']) / max(1, $stats_itens['total_itens'] + $stats_transacoes['total_transacoes'])
];

// Produtos mais comprados (baseado em itens de compra)
$stmt = $db->prepare("
    SELECT 
        p.nome,
        p.codigo,
        COUNT(ic.id) as qtd_compras,
        SUM(ic.preco_total) as valor_total,
        AVG(ic.preco_unitario) as valor_medio,
        MAX(c.data_compra) as ultima_compra
    FROM produtos p
    LEFT JOIN itens_compra ic ON ic.produto_id = p.id
    LEFT JOIN compras c ON ic.compra_id = c.id
    WHERE p.grupo_id = ?
    GROUP BY p.id, p.nome, p.codigo
    HAVING qtd_compras > 0
    ORDER BY qtd_compras DESC, valor_total DESC
    LIMIT 10
");
$stmt->execute([$grupo_id]);
$produtos_mais_comprados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Análise por fornecedor
$stmt = $db->prepare("
    SELECT 
        f.nome as fornecedor,
        COUNT(DISTINCT ic.produto_id) as produtos_diferentes,
        COUNT(ic.id) as total_compras,
        SUM(ic.preco_total) as valor_total,
        AVG(ic.preco_total) as valor_medio
    FROM fornecedores f
    LEFT JOIN compras c ON c.fornecedor_id = f.id
    LEFT JOIN itens_compra ic ON ic.compra_id = c.id
    WHERE f.grupo_id = ?
    GROUP BY f.id, f.nome
    HAVING total_compras > 0
    ORDER BY valor_total DESC
    LIMIT 10
");
$stmt->execute([$grupo_id]);
$analise_fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Análise temporal (últimos 12 meses) - baseado em transações
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(t.data_transacao, '%Y-%m') as mes,
        COUNT(t.id) as qtd_compras,
        SUM(t.valor) as valor_total,
        COUNT(DISTINCT t.descricao) as produtos_diferentes
    FROM transacoes t
    WHERE t.tipo = 'despesa' 
    AND t.descricao LIKE 'Compra -%'
    AND t.data_transacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(t.data_transacao, '%Y-%m')
    ORDER BY mes DESC
");
$stmt->execute();
$analise_temporal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Produtos - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .container-fluid {
            padding: 0;
        }
        main {
            margin-top: 0 !important;
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-12 col-md-9 col-lg-10 ms-sm-auto px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <i class="fas fa-chart-line me-2"></i>Análise de Produtos
                        </h1>
                    </div>

                    <!-- Estatísticas Gerais -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title"><?php echo $stats['total_produtos'] ?? 0; ?></h4>
                                            <p class="card-text">Total de Produtos</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title"><?php echo $stats['total_itens'] ?? 0; ?></h4>
                                            <p class="card-text">Itens Comprados</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-shopping-cart fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title">R$ <?php echo number_format($stats['total_gasto'] ?? 0, 2, ',', '.'); ?></h4>
                                            <p class="card-text">Total Gasto</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title">R$ <?php echo number_format($stats['media_gasto'] ?? 0, 2, ',', '.'); ?></h4>
                                            <p class="card-text">Média por Compra</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calculator fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Produtos Mais Comprados -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>Produtos Mais Comprados
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($produtos_mais_comprados)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Nenhuma compra registrada</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Produto</th>
                                                        <th>Qtd</th>
                                                        <th>Total</th>
                                                        <th>Média</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($produtos_mais_comprados as $prod): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($prod['nome']); ?></strong>
                                                                <?php if ($prod['codigo']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($prod['codigo']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><span class="badge bg-primary"><?php echo $prod['qtd_compras']; ?></span></td>
                                                            <td>R$ <?php echo number_format($prod['valor_total'], 2, ',', '.'); ?></td>
                                                            <td>R$ <?php echo number_format($prod['valor_medio'], 2, ',', '.'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Análise por Fornecedor -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-truck me-2"></i>Análise por Fornecedor
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($analise_fornecedores)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Nenhum fornecedor com compras</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Fornecedor</th>
                                                        <th>Produtos</th>
                                                        <th>Compras</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($analise_fornecedores as $forn): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($forn['fornecedor']); ?></strong></td>
                                                            <td><span class="badge bg-info"><?php echo $forn['produtos_diferentes']; ?></span></td>
                                                            <td><span class="badge bg-primary"><?php echo $forn['total_compras']; ?></span></td>
                                                            <td>R$ <?php echo number_format($forn['valor_total'], 2, ',', '.'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Temporal -->
                    <?php if (!empty($analise_temporal)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Evolução das Compras (12 meses)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoEvolucao" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($analise_temporal)): ?>
    <script>
        // Gráfico de evolução temporal
        const ctx = document.getElementById('graficoEvolucao').getContext('2d');
        const dados = <?php echo json_encode($analise_temporal); ?>;
        
        const labels = dados.map(item => {
            const [ano, mes] = item.mes.split('-');
            const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                          'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return `${meses[parseInt(mes) - 1]}/${ano}`;
        }).reverse();
        
        const valores = dados.map(item => parseFloat(item.valor_total)).reverse();
        const quantidades = dados.map(item => parseInt(item.qtd_compras)).reverse();
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: valores,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Quantidade de Compras',
                    data: quantidades,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Valor (R$)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Quantidade'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <!-- Script Mobile Menu -->
    <script>
        // JavaScript para menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const offcanvasElement = document.getElementById('mobileSidebar');
            const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
            
            // Fechar offcanvas ao clicar em um link
            const offcanvasLinks = document.querySelectorAll('#mobileSidebar .nav-link');
            offcanvasLinks.forEach(link => {
                link.addEventListener('click', () => {
                    setTimeout(() => {
                        offcanvas.hide();
                    }, 100);
                });
            });
        });
    </script>
</body>
</html>
