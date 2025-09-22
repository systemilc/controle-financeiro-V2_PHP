<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Transacao.php';
require_once 'classes/Categoria.php';
require_once 'classes/Conta.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

// Instanciar classes com grupo_id definido
$transacao = new Transacao($db);
$transacao->grupo_id = $grupo_id;

$categoria = new Categoria($db);
$categoria->grupo_id = $grupo_id;

$conta = new Conta($db);
$conta->grupo_id = $grupo_id;

// Obter filtros de data (mês/ano)
$mes_selecionado = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano_selecionado = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Validar mês e ano
$mes_selecionado = max(1, min(12, $mes_selecionado));
$ano_selecionado = max(2020, min(2030, $ano_selecionado));

// Calcular datas do período selecionado
$data_inicio = sprintf('%04d-%02d-01', $ano_selecionado, $mes_selecionado);
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Obter resumo do período selecionado
$resumo = $transacao->getResumo($data_inicio, $data_fim, $grupo_id);

// Obter transações do período selecionado
$stmt_transacoes = $transacao->read(null, null, null, null, $grupo_id, $data_inicio, $data_fim);
$transacoes = $stmt_transacoes->fetchAll(PDO::FETCH_ASSOC);

// Obter contas
$stmt_contas = $conta->read($grupo_id);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

$saldo = ($resumo['total_receitas'] ?? 0) - ($resumo['total_despesas'] ?? 0);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .quick-action-btn {
            border-radius: 15px;
            padding: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
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
                                <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                                Dashboard
                            </h2>
                            <div class="btn-group">
                                <a href="adicionar_transacao.php?tipo=receita" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>Receita
                                </a>
                                <a href="adicionar_transacao.php?tipo=despesa" class="btn btn-danger btn-sm">
                                    <i class="fas fa-minus me-1"></i>Despesa
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros de Data -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2 text-info"></i>
                                    Período: <?php echo date('F/Y', strtotime($data_inicio)); ?>
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex gap-2">
                                    <select name="mes" class="form-select form-select-sm" style="width: auto;">
                                        <?php
                                        $meses = [
                                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                        ];
                                        foreach($meses as $num => $nome) {
                                            $selected = ($num == $mes_selecionado) ? 'selected' : '';
                                            echo "<option value=\"{$num}\" {$selected}>{$nome}</option>";
                                        }
                                        ?>
                                    </select>
                                    <select name="ano" class="form-select form-select-sm" style="width: auto;">
                                        <?php
                                        for($ano = date('Y') - 2; $ano <= date('Y') + 1; $ano++) {
                                            $selected = ($ano == $ano_selecionado) ? 'selected' : '';
                                            echo "<option value=\"{$ano}\" {$selected}>{$ano}</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-filter me-1"></i>Filtrar
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i>Limpar
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="container-fluid p-4">
                        <!-- Cards de Resumo -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card card-stat">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success me-3">
                                                <i class="fas fa-arrow-up"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-muted mb-1">Receitas</h6>
                                                <h4 class="mb-0 text-success">R$ <?= number_format($resumo['total_receitas'] ?? 0, 2, ',', '.') ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card card-stat">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-danger me-3">
                                                <i class="fas fa-arrow-down"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-muted mb-1">Despesas</h6>
                                                <h4 class="mb-0 text-danger">R$ <?= number_format($resumo['total_despesas'] ?? 0, 2, ',', '.') ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card card-stat">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon <?= $saldo >= 0 ? 'bg-info' : 'bg-warning' ?> me-3">
                                                <i class="fas fa-balance-scale"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-muted mb-1">Saldo</h6>
                                                <h4 class="mb-0 <?= $saldo >= 0 ? 'text-info' : 'text-warning' ?>">R$ <?= number_format($saldo, 2, ',', '.') ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card card-stat">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-secondary me-3">
                                                <i class="fas fa-list"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-muted mb-1">Transações</h6>
                                                <h4 class="mb-0 text-secondary"><?= ($resumo['qtd_receitas'] ?? 0) + ($resumo['qtd_despesas'] ?? 0) ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

        <!-- Botões de Ação Rápida -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="adicionar_transacao.php?tipo=receita" class="btn btn-success btn-lg w-100 mb-2">
                                    <i class="fas fa-plus me-2"></i>Adicionar Receita
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="adicionar_transacao.php?tipo=despesa" class="btn btn-danger btn-lg w-100 mb-2">
                                    <i class="fas fa-minus me-2"></i>Adicionar Despesa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transações Recentes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Transações Recentes</h5>
                        <a href="transacoes.php" class="btn btn-outline-primary btn-sm">
                            Ver Todas
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($transacoes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhuma transação encontrada.</p>
                                <a href="adicionar_transacao.php" class="btn btn-primary">
                                    Adicionar Primeira Transação
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Descrição</th>
                                            <th>Categoria</th>
                                            <th>Tipo</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_slice($transacoes, 0, 10) as $transacao_item): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($transacao_item['data_transacao'])) ?></td>
                                                <td><?= htmlspecialchars($transacao_item['descricao']) ?></td>
                                                <td>
                                                    <span class="badge" style="background-color: <?= $transacao_item['categoria_cor'] ?>">
                                                        <?= htmlspecialchars($transacao_item['categoria_nome'] ?? 'Sem categoria') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $transacao_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst($transacao_item['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td class="<?= $transacao_item['tipo'] == 'receita' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $transacao_item['tipo'] == 'receita' ? '+' : '-' ?>R$ <?= number_format($transacao_item['valor'], 2, ',', '.') ?>
                                                </td>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>
</html>
