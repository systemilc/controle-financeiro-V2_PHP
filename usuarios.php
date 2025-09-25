<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Usuario.php';
require_once 'classes/Grupo.php';
require_once 'classes/UsuarioConvidado.php';

// Verificar se é admin
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$usuario = new Usuario($database->getConnection());
$grupo = new Grupo($database->getConnection());
$usuario_convidado = new UsuarioConvidado($database->getConnection());

$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'aprovar':
                $usuario->id = $_POST['usuario_id'];
                if ($usuario->aprovar()) {
                    $message = 'Usuário aprovado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao aprovar usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'desaprovar':
                $usuario->id = $_POST['usuario_id'];
                if ($usuario->desaprovar()) {
                    $message = 'Usuário desaprovado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao desaprovar usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'ativar':
                $usuario->id = $_POST['usuario_id'];
                if ($usuario->ativar()) {
                    $message = 'Usuário ativado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao ativar usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'desativar':
                $usuario->id = $_POST['usuario_id'];
                if ($usuario->desativar()) {
                    $message = 'Usuário desativado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao desativar usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'bloquear':
                $usuario->id = $_POST['usuario_id'];
                $duracao = (int)($_POST['duracao'] ?? 1);
                $unidade = $_POST['unidade'] ?? 'horas';
                
                // Converter para minutos
                $minutos = 0;
                switch($unidade) {
                    case 'minutos':
                        $minutos = $duracao;
                        break;
                    case 'horas':
                        $minutos = $duracao * 60;
                        break;
                    case 'dias':
                        $minutos = $duracao * 60 * 24;
                        break;
                    case 'semanas':
                        $minutos = $duracao * 60 * 24 * 7;
                        break;
                    case 'anos':
                        $minutos = $duracao * 60 * 24 * 365;
                        break;
                }
                
                if ($usuario->bloquear($minutos)) {
                    $tempo_texto = $duracao . ' ' . $unidade;
                    if ($duracao > 1) {
                        $tempo_texto = $duracao . ' ' . $unidade;
                    }
                    $message = "Usuário bloqueado por {$tempo_texto}!";
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao bloquear usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'desbloquear':
                $usuario->id = $_POST['usuario_id'];
                if ($usuario->desbloquear()) {
                    $message = 'Usuário desbloqueado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao desbloquear usuário!';
                    $message_type = 'danger';
                }
                break;
            case 'remover_usuario_convidado':
                $usuario_id = $_POST['usuario_id'];
                $grupo_id = $_POST['grupo_id'];
                
                if ($usuario_convidado->removerAcesso($usuario_id, $grupo_id)) {
                    $message = 'Usuário convidado removido com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao remover usuário convidado!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar dados
$usuarios = $usuario->getAll();
$grupos = $grupo->getAll();
$estatisticas = $usuario->getEstatisticas();
$pendentes = $usuario->getPendentesAprovacao();

// Buscar usuários convidados por grupo
$usuarios_convidados_por_grupo = [];
foreach ($grupos as $grupo_item) {
    $usuarios_convidados_por_grupo[$grupo_item['id']] = $usuario_convidado->getByGrupo($grupo_item['id']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <style>
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .user-card {
            transition: transform 0.2s;
        }
        .user-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8em;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card .card-body {
            padding: 1.5rem;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #28a745);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }
        .action-btn {
            margin: 2px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
                                <i class="fas fa-users me-2 text-primary"></i>
                                Gestão de Usuários
                            </h2>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEstatisticas">
                                    <i class="fas fa-chart-bar me-1"></i>Estatísticas
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalPendentes">
                                    <i class="fas fa-clock me-1"></i>Pendentes (<?= count($pendentes) ?>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Estatísticas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['total_usuarios'] ?></h4>
                                        <small>Total de Usuários</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['usuarios_ativos'] ?></h4>
                                        <small>Usuários Ativos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['usuarios_aprovados'] ?></h4>
                                        <small>Usuários Aprovados</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-ban fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['usuarios_bloqueados'] ?></h4>
                                        <small>Usuários Bloqueados</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                <!-- Tabela de usuários -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Usuários</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Grupo</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Último Acesso</th>
                                        <th>Tentativas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                                    <?php if ($user['email']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['grupo_nome']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'user' ? 'primary' : 'secondary') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success status-badge">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary status-badge">Inativo</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['is_approved']): ?>
                                                    <span class="badge bg-primary status-badge">Aprovado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning status-badge">Pendente</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()): ?>
                                                    <span class="badge bg-danger status-badge">Bloqueado</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['data_ultimo_acesso']): ?>
                                                <small><?= date('d/m/Y H:i', strtotime($user['data_ultimo_acesso'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Nunca</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['tentativas_login'] > 0): ?>
                                                <span class="badge bg-warning"><?= $user['tentativas_login'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <?php if (!$user['is_approved']): ?>
                                                    <button class="btn btn-success btn-sm action-btn" onclick="aprovarUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-check"></i> Aprovar
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-warning btn-sm action-btn" onclick="desaprovarUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-times"></i> Desaprovar
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['is_active']): ?>
                                                    <button class="btn btn-secondary btn-sm action-btn" onclick="desativarUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-pause"></i> Desativar
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary btn-sm action-btn" onclick="ativarUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-play"></i> Ativar
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()): ?>
                                                    <button class="btn btn-success btn-sm action-btn" onclick="desbloquearUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-unlock"></i> Desbloquear
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-danger btn-sm action-btn" onclick="bloquearUsuario(<?= $user['id'] ?>)">
                                                        <i class="fas fa-lock"></i> Bloquear
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Seção de Usuários Convidados -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Usuários Convidados por Grupo</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($usuarios_convidados_por_grupo) || array_sum(array_map('count', $usuarios_convidados_por_grupo)) == 0): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                <h5>Nenhum usuário convidado</h5>
                                <p class="text-muted">Não há usuários convidados em nenhum grupo.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="accordionConvidados">
                                <?php foreach ($grupos as $grupo_item): ?>
                                    <?php $usuarios_convidados = $usuarios_convidados_por_grupo[$grupo_item['id']]; ?>
                                    <?php if (!empty($usuarios_convidados)): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?= $grupo_item['id'] ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $grupo_item['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $grupo_item['id'] ?>">
                                                    <i class="fas fa-layer-group me-2"></i>
                                                    <?= htmlspecialchars($grupo_item['nome']) ?>
                                                    <span class="badge bg-primary ms-2"><?= count($usuarios_convidados) ?> convidado(s)</span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?= $grupo_item['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $grupo_item['id'] ?>" data-bs-parent="#accordionConvidados">
                                                <div class="accordion-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Usuário</th>
                                                                    <th>Email</th>
                                                                    <th>Convite Aceito em</th>
                                                                    <th>Status</th>
                                                                    <th>Ações</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($usuarios_convidados as $convidado): ?>
                                                                <tr>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9em;">
                                                                                <?= strtoupper(substr($convidado['username'], 0, 2)) ?>
                                                                            </div>
                                                                            <strong><?= htmlspecialchars($convidado['username']) ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($convidado['email']) ?></td>
                                                                    <td>
                                                                        <small><?= date('d/m/Y H:i', strtotime($convidado['data_aceite'])) ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-<?= $convidado['is_ativo'] ? 'success' : 'secondary' ?>">
                                                                            <i class="fas fa-<?= $convidado['is_ativo'] ? 'check' : 'times' ?> me-1"></i>
                                                                            <?= $convidado['is_ativo'] ? 'Ativo' : 'Inativo' ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($convidado['is_ativo']): ?>
                                                                            <button class="btn btn-sm btn-outline-danger" onclick="removerUsuarioConvidado(<?= $convidado['usuario_id'] ?>, <?= $grupo_item['id'] ?>)">
                                                                                <i class="fas fa-user-times"></i> Remover
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Removido</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Bloqueio -->
    <div class="modal fade" id="modalBloquear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-lock me-2"></i>Bloquear Usuário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="bloquear">
                        <input type="hidden" name="usuario_id" id="bloquear_usuario_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duração</label>
                                    <input type="number" name="duracao" class="form-control" value="1" min="1" max="999" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unidade de Tempo</label>
                                    <select name="unidade" class="form-select" required>
                                        <option value="minutos">Minutos</option>
                                        <option value="horas" selected>Horas</option>
                                        <option value="dias">Dias</option>
                                        <option value="semanas">Semanas</option>
                                        <option value="anos">Anos</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Opções Rápidas</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTempoBloqueio(30, 'minutos')">30 min</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTempoBloqueio(1, 'horas')">1 hora</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTempoBloqueio(4, 'horas')">4 horas</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTempoBloqueio(1, 'dias')">1 dia</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setTempoBloqueio(7, 'dias')">1 semana</button>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Duração selecionada:</strong> <span id="tempo-selecionado">1 hora</span>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            O usuário ficará bloqueado pelo tempo selecionado e não poderá fazer login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-lock me-2"></i>Bloquear Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Estatísticas -->
    <div class="modal fade" id="modalEstatisticas" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>Estatísticas de Usuários</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Resumo Geral</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-users text-primary me-2"></i>Total: <?= $estatisticas['total_usuarios'] ?></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Ativos: <?= $estatisticas['usuarios_ativos'] ?></li>
                                <li><i class="fas fa-user-check text-info me-2"></i>Aprovados: <?= $estatisticas['usuarios_aprovados'] ?></li>
                                <li><i class="fas fa-ban text-danger me-2"></i>Bloqueados: <?= $estatisticas['usuarios_bloqueados'] ?></li>
                                <li><i class="fas fa-clock text-warning me-2"></i>Ativos (30 dias): <?= $estatisticas['usuarios_ativos_30dias'] ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Distribuição por Status</h6>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?= $estatisticas['total_usuarios'] > 0 ? round(($estatisticas['usuarios_ativos'] / $estatisticas['total_usuarios']) * 100) : 0 ?>%">
                                    Ativos
                                </div>
                            </div>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-primary" style="width: <?= $estatisticas['total_usuarios'] > 0 ? round(($estatisticas['usuarios_aprovados'] / $estatisticas['total_usuarios']) * 100) : 0 ?>%">
                                    Aprovados
                                </div>
                            </div>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-danger" style="width: <?= $estatisticas['total_usuarios'] > 0 ? round(($estatisticas['usuarios_bloqueados'] / $estatisticas['total_usuarios']) * 100) : 0 ?>%">
                                    Bloqueados
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Pendentes -->
    <div class="modal fade" id="modalPendentes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Usuários Pendentes de Aprovação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($pendentes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>Nenhum usuário pendente!</h5>
                            <p class="text-muted">Todos os usuários já foram aprovados.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Grupo</th>
                                        <th>Data de Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendentes as $pendente): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?= strtoupper(substr($pendente['username'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($pendente['username']) ?></strong>
                                                    <?php if ($pendente['email']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($pendente['email']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($pendente['grupo_nome']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($pendente['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-success btn-sm" onclick="aprovarUsuario(<?= $pendente['id'] ?>)">
                                                    <i class="fas fa-check"></i> Aprovar
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="desativarUsuario(<?= $pendente['id'] ?>)">
                                                    <i class="fas fa-times"></i> Rejeitar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function aprovarUsuario(id) {
            if (confirm('Deseja aprovar este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="aprovar">
                    <input type="hidden" name="usuario_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function desaprovarUsuario(id) {
            if (confirm('Deseja desaprovar este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="desaprovar">
                    <input type="hidden" name="usuario_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function ativarUsuario(id) {
            if (confirm('Deseja ativar este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="ativar">
                    <input type="hidden" name="usuario_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function desativarUsuario(id) {
            if (confirm('Deseja desativar este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="desativar">
                    <input type="hidden" name="usuario_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function bloquearUsuario(id) {
            document.getElementById('bloquear_usuario_id').value = id;
            // Resetar valores padrão
            document.querySelector('input[name="duracao"]').value = 1;
            document.querySelector('select[name="unidade"]').value = 'horas';
            atualizarTempoSelecionado();
            new bootstrap.Modal(document.getElementById('modalBloquear')).show();
        }

        function setTempoBloqueio(duracao, unidade) {
            document.querySelector('input[name="duracao"]').value = duracao;
            document.querySelector('select[name="unidade"]').value = unidade;
            atualizarTempoSelecionado();
        }

        function atualizarTempoSelecionado() {
            const duracao = document.querySelector('input[name="duracao"]').value;
            const unidade = document.querySelector('select[name="unidade"]').value;
            
            let texto = duracao + ' ' + unidade;
            if (duracao > 1) {
                // Pluralizar
                if (unidade === 'minutos') texto = duracao + ' minutos';
                else if (unidade === 'horas') texto = duracao + ' horas';
                else if (unidade === 'dias') texto = duracao + ' dias';
                else if (unidade === 'semanas') texto = duracao + ' semanas';
                else if (unidade === 'anos') texto = duracao + ' anos';
            }
            
            document.getElementById('tempo-selecionado').textContent = texto;
        }

        // Adicionar event listeners para atualizar o tempo em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const duracaoInput = document.querySelector('input[name="duracao"]');
            const unidadeSelect = document.querySelector('select[name="unidade"]');
            
            if (duracaoInput && unidadeSelect) {
                duracaoInput.addEventListener('input', atualizarTempoSelecionado);
                unidadeSelect.addEventListener('change', atualizarTempoSelecionado);
            }
        });

        function desbloquearUsuario(id) {
            if (confirm('Deseja desbloquear este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="desbloquear">
                    <input type="hidden" name="usuario_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function removerUsuarioConvidado(usuarioId, grupoId) {
            if (confirm('Deseja remover este usuário convidado do grupo?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="remover_usuario_convidado">
                    <input type="hidden" name="usuario_id" value="${usuarioId}">
                    <input type="hidden" name="grupo_id" value="${grupoId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
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
