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

// Filtros - Por padrão mostra todas as transações
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$conta_id = $_GET['conta_id'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';

// Buscar dados
$contas = $conta->getAll();
$categorias = $categoria->getAll();

// Resumo financeiro do período selecionado
$resumo = $transacao->getResumo($data_inicio, $data_fim, $current_user['grupo_id']);

// Resumo geral (todas as transações)
$resumo_geral = $transacao->getResumo(null, null, $current_user['grupo_id']);

// Resumo do mês atual
$mes_atual = date('n');
$ano_atual = date('Y');
$data_inicio_mes_atual = sprintf('%04d-%02d-01', $ano_atual, $mes_atual);
$data_fim_mes_atual = date('Y-m-t', strtotime($data_inicio_mes_atual));
$resumo_mes_atual = $transacao->getResumo($data_inicio_mes_atual, $data_fim_mes_atual, $current_user['grupo_id']);

// Transações por categoria
$transacoes_categoria = $transacao->getByCategory($data_inicio, $data_fim);

// Transações por conta
$transacoes_conta = $transacao->getByAccount($data_inicio, $data_fim);

// Evolução mensal
$evolucao_mensal = $transacao->getMonthlyEvolution($data_inicio, $data_fim);

// Transações por tipo de pagamento
$transacoes_tipo_pagamento = $transacao->getByPaymentType($data_inicio, $data_fim);
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
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros de Relatório
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Por padrão, os relatórios mostram TODAS as transações.</strong> 
                            Use os filtros abaixo para analisar períodos específicos, contas ou categorias.
                        </div>
                        
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Data Início
                                    <small class="text-muted d-block">Deixe vazio para mostrar desde o início</small>
                                </label>
                                <input type="date" class="form-control" name="data_inicio" value="<?= $data_inicio ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Data Fim
                                    <small class="text-muted d-block">Deixe vazio para mostrar até hoje</small>
                                </label>
                                <input type="date" class="form-control" name="data_fim" value="<?= $data_fim ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-university me-1"></i>Conta
                                    <small class="text-muted d-block">Selecione uma conta específica</small>
                                </label>
                                <select class="form-select" name="conta_id">
                                    <option value="">Todas as Contas</option>
                                    <?php foreach ($contas as $conta_item): ?>
                                    <option value="<?= $conta_item['id'] ?>" <?= $conta_id == $conta_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($conta_item['nome'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-tags me-1"></i>Categoria
                                    <small class="text-muted d-block">Selecione uma categoria específica</small>
                                </label>
                                <select class="form-select" name="categoria_id">
                                    <option value="">Todas as Categorias</option>
                                    <?php foreach ($categorias as $categoria_item): ?>
                                    <option value="<?= $categoria_item['id'] ?>" <?= $categoria_id == $categoria_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria_item['nome'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Aplicar Filtros
                                </button>
                                <a href="relatorios.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Limpar Filtros (Mostrar Todas)
                                </a>
                                <small class="text-muted ms-3">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Dica: Para ver dados de um ano específico, defina "Data Início" como 01/01/AAAA e "Data Fim" como 31/12/AAAA
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Visão Geral do Financeiro -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Visão Geral do Financeiro
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Totais Gerais -->
                                    <div class="col-md-6 mb-4">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-globe me-1"></i>Totais Gerais (Todas as Transações)
                                        </h6>
                                        <div class="row">
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-success me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-arrow-up text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Receitas</small><br>
                                                        <strong class="text-success">R$ <?= number_format($resumo_geral['total_receitas'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-danger me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-arrow-down text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Despesas</small><br>
                                                        <strong class="text-danger">R$ <?= number_format($resumo_geral['total_despesas'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon <?= $resumo_geral['saldo'] >= 0 ? 'bg-info' : 'bg-warning' ?> me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-balance-scale text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Saldo Geral</small><br>
                                                        <strong class="<?= $resumo_geral['saldo'] >= 0 ? 'text-info' : 'text-warning' ?>">R$ <?= number_format($resumo_geral['saldo'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-secondary me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-list text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Total Transações</small><br>
                                                        <strong class="text-secondary"><?= ($resumo_geral['qtd_receitas'] ?? 0) + ($resumo_geral['qtd_despesas'] ?? 0) ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Mês Atual -->
                                    <div class="col-md-6 mb-4">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-calendar-alt me-1"></i>Mês Atual (<?= date('m/Y') ?>)
                                        </h6>
                                        <div class="row">
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-success me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-arrow-up text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Receitas</small><br>
                                                        <strong class="text-success">R$ <?= number_format($resumo_mes_atual['total_receitas'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-danger me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-arrow-down text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Despesas</small><br>
                                                        <strong class="text-danger">R$ <?= number_format($resumo_mes_atual['total_despesas'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon <?= $resumo_mes_atual['saldo'] >= 0 ? 'bg-info' : 'bg-warning' ?> me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-balance-scale text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Saldo do Mês</small><br>
                                                        <strong class="<?= $resumo_mes_atual['saldo'] >= 0 ? 'text-info' : 'text-warning' ?>">R$ <?= number_format($resumo_mes_atual['saldo'] ?? 0, 2, ',', '.') ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon bg-secondary me-2" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-list text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Transações</small><br>
                                                        <strong class="text-secondary"><?= ($resumo_mes_atual['qtd_receitas'] ?? 0) + ($resumo_mes_atual['qtd_despesas'] ?? 0) ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards do Período Selecionado -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>
                                    <?php if ($data_inicio && $data_fim): ?>
                                        Período Selecionado (<?= date('d/m/Y', strtotime($data_inicio)) ?> - <?= date('d/m/Y', strtotime($data_fim)) ?>)
                                    <?php elseif ($data_inicio): ?>
                                        Período Selecionado (desde <?= date('d/m/Y', strtotime($data_inicio)) ?>)
                                    <?php elseif ($data_fim): ?>
                                        Período Selecionado (até <?= date('d/m/Y', strtotime($data_fim)) ?>)
                                    <?php else: ?>
                                        Todas as Transações (sem filtro de data)
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
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
                                <h5><i class="fas fa-credit-card me-2"></i>Por Tipo de Pagamento</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartTiposPagamento"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
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
                                                        <div class="color-preview" style="background-color: <?= $item['cor'] ?? '#6c757d' ?>; width: 15px; height: 15px; border-radius: 50%; margin-right: 8px;"></div>
                                                        <?= htmlspecialchars($item['nome'] ?? 'Sem categoria') ?>
                                                    </div>
                                                </td>
                                                <td class="text-<?= $item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                    R$ <?= number_format($item['valor'] ?? 0, 2, ',', '.') ?>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-<?= $item['tipo'] == 'receita' ? 'success' : 'danger' ?>" 
                                                             style="width: <?= $item['percentual'] ?? 0 ?>%">
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
                                <h5><i class="fas fa-credit-card me-2"></i>Tipos de Pagamento</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Receitas</th>
                                                <th>Despesas</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transacoes_tipo_pagamento as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="color-preview" style="background-color: <?= $item['cor'] ?? '#6c757d' ?>; width: 15px; height: 15px; border-radius: 50%; margin-right: 8px;"></div>
                                                        <?= htmlspecialchars($item['nome'] ?? 'Sem tipo') ?>
                                                    </div>
                                                </td>
                                                <td class="text-success">
                                                    R$ <?= number_format($item['receitas'] ?? 0, 2, ',', '.') ?>
                                                </td>
                                                <td class="text-danger">
                                                    R$ <?= number_format($item['despesas'] ?? 0, 2, ',', '.') ?>
                                                </td>
                                                <td class="text-primary">
                                                    R$ <?= number_format($item['total'] ?? 0, 2, ',', '.') ?>
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
                    data: [<?= $resumo['total_receitas'] ?? 0 ?>, <?= $resumo['total_despesas'] ?? 0 ?>],
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
                labels: [<?php foreach ($transacoes_categoria as $item): ?>'<?= htmlspecialchars($item['nome'] ?? 'Sem categoria') ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Valor',
                    data: [<?php foreach ($transacoes_categoria as $item): ?><?= $item['valor'] ?? 0 ?>,<?php endforeach; ?>],
                    backgroundColor: [<?php foreach ($transacoes_categoria as $item): ?>'<?= $item['cor'] ?? '#6c757d' ?>',<?php endforeach; ?>],
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
                labels: [<?php foreach ($transacoes_conta as $item): ?>'<?= htmlspecialchars($item['nome'] ?? 'Sem conta') ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Receitas',
                    data: [<?php foreach ($transacoes_conta as $item): ?><?= $item['receitas'] ?? 0 ?>,<?php endforeach; ?>],
                    backgroundColor: '#28a745'
                }, {
                    label: 'Despesas',
                    data: [<?php foreach ($transacoes_conta as $item): ?><?= $item['despesas'] ?? 0 ?>,<?php endforeach; ?>],
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
        
        // Gráfico por Tipo de Pagamento
        const ctx4 = document.getElementById('chartTiposPagamento').getContext('2d');
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($transacoes_tipo_pagamento as $item): ?>'<?= htmlspecialchars($item['nome'] ?? 'Sem tipo') ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Receitas',
                    data: [<?php foreach ($transacoes_tipo_pagamento as $item): ?><?= $item['receitas'] ?? 0 ?>,<?php endforeach; ?>],
                    backgroundColor: '#28a745'
                }, {
                    label: 'Despesas',
                    data: [<?php foreach ($transacoes_tipo_pagamento as $item): ?><?= $item['despesas'] ?? 0 ?>,<?php endforeach; ?>],
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
        const ctx5 = document.getElementById('chartEvolucao').getContext('2d');
        new Chart(ctx5, {
            type: 'line',
            data: {
                labels: [<?php foreach ($evolucao_mensal as $item): ?>'<?= $item['mes_formatado'] ?? $item['mes'] ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Receitas',
                    data: [<?php foreach ($evolucao_mensal as $item): ?><?= $item['receitas'] ?? 0 ?>,<?php endforeach; ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Despesas',
                    data: [<?php foreach ($evolucao_mensal as $item): ?><?= $item['despesas'] ?? 0 ?>,<?php endforeach; ?>],
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