<?php
session_start();
require_once 'classes/Auth.php';
require_once 'config/database.php';
require_once 'classes/Transacao.php';
require_once 'classes/Usuario.php';
require_once 'classes/Grupo.php';
require_once 'classes/Conta.php';
require_once 'classes/Categoria.php';

// Verificar se é admin
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$transacao = new Transacao($database->getConnection());
$usuario = new Usuario($database->getConnection());
$grupo = new Grupo($database->getConnection());
$conta = new Conta($database->getConnection());
$categoria = new Categoria($database->getConnection());

// Buscar estatísticas gerais
$estatisticas_gerais = $transacao->getEstatisticasGerais();
$estatisticas_grupos = $transacao->getEstatisticasPorGrupo();
$grupos = $grupo->getAll();
$usuarios = $usuario->getAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .admin-badge {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .grupo-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .grupo-header {
            display: flex;
            justify-content-between;
            align-items-center;
            margin-bottom: 1rem;
        }
        .grupo-nome {
            font-size: 1.2rem;
            font-weight: bold;
            color: #495057;
        }
        .grupo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }
        .grupo-stat {
            text-align: center;
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 8px;
        }
        .grupo-stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .grupo-stat-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .recent-activity {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2em;
        }
        .activity-content {
            flex: 1;
        }
        .activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .activity-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Admin Header -->
                    <div class="admin-header">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h1 class="mb-2">
                                        <i class="fas fa-shield-alt me-3"></i>
                                        Dashboard Administrativo
                                    </h1>
                                    <p class="mb-0 opacity-75">
                                        Visão geral completa do sistema e todos os grupos
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="admin-badge">
                                        <i class="fas fa-crown me-2"></i>
                                        Administrador
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <!-- Estatísticas Gerais -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                    <div class="stat-number text-success"><?= number_format($estatisticas_gerais['total_transacoes']) ?></div>
                                    <div class="stat-label">Total de Transações</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #17a2b8, #138496); color: white;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-number text-info"><?= number_format($estatisticas_gerais['transacoes_confirmadas']) ?></div>
                                    <div class="stat-label">Transações Confirmadas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: white;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-number text-warning"><?= number_format($estatisticas_gerais['transacoes_pendentes']) ?></div>
                                    <div class="stat-label">Transações Pendentes</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-number text-primary"><?= number_format($estatisticas_gerais['usuarios_ativos']) ?></div>
                                    <div class="stat-label">Usuários Ativos</div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumo Financeiro -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #28a745, #20c997); color: white;">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="stat-number text-success">R$ <?= number_format($estatisticas_gerais['receitas_totais'], 2, ',', '.') ?></div>
                                    <div class="stat-label">Receitas Totais</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card text-center">
                                    <div class="stat-icon mx-auto" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div class="stat-number text-danger">R$ <?= number_format($estatisticas_gerais['despesas_totais'], 2, ',', '.') ?></div>
                                    <div class="stat-label">Despesas Totais</div>
                                </div>
                            </div>
                        </div>

                        <!-- Estatísticas por Grupo -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="recent-activity">
                                    <h5 class="mb-3">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Estatísticas por Grupo
                                    </h5>
                                    
                                    <?php foreach ($estatisticas_grupos as $grupo_stat): ?>
                                    <div class="grupo-card">
                                        <div class="grupo-header">
                                            <div class="grupo-nome"><?= htmlspecialchars($grupo_stat['grupo_nome']) ?></div>
                                        </div>
                                        
                                        <div class="grupo-stats">
                                            <div class="grupo-stat">
                                                <div class="grupo-stat-number"><?= number_format($grupo_stat['total_transacoes']) ?></div>
                                                <div class="grupo-stat-label">Transações</div>
                                            </div>
                                            <div class="grupo-stat">
                                                <div class="grupo-stat-number"><?= number_format($grupo_stat['usuarios_ativos']) ?></div>
                                                <div class="grupo-stat-label">Usuários</div>
                                            </div>
                                            <div class="grupo-stat">
                                                <div class="grupo-stat-number text-success">R$ <?= number_format($grupo_stat['receitas'], 2, ',', '.') ?></div>
                                                <div class="grupo-stat-label">Receitas</div>
                                            </div>
                                            <div class="grupo-stat">
                                                <div class="grupo-stat-number text-danger">R$ <?= number_format($grupo_stat['despesas'], 2, ',', '.') ?></div>
                                                <div class="grupo-stat-label">Despesas</div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="recent-activity">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Informações do Sistema
                                    </h5>
                                    
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: #667eea; color: white;">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">Total de Grupos</div>
                                            <div class="activity-meta"><?= count($grupos) ?> grupos cadastrados</div>
                                        </div>
                                    </div>
                                    
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: #28a745; color: white;">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">Total de Usuários</div>
                                            <div class="activity-meta"><?= count($usuarios) ?> usuários cadastrados</div>
                                        </div>
                                    </div>
                                    
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: #17a2b8; color: white;">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">Contas Bancárias</div>
                                            <div class="activity-meta"><?= $estatisticas_gerais['contas_utilizadas'] ?> contas ativas</div>
                                        </div>
                                    </div>
                                    
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: #ffc107; color: white;">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">Sistema Ativo</div>
                                            <div class="activity-meta">Desde <?= date('d/m/Y') ?></div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
