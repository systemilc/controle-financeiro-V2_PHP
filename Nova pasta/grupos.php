<?php
session_start();
require_once 'classes/Auth.php';
require_once 'config/database.php';
require_once 'classes/Grupo.php';
require_once 'classes/Usuario.php';

// Verificar se é admin
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$grupo = new Grupo($database->getConnection());
$usuario = new Usuario($database->getConnection());

$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $grupo->nome = $_POST['nome'];
                $grupo->descricao = $_POST['descricao'];
                
                if ($grupo->create()) {
                    $message = 'Grupo criado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao criar grupo!';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $grupo->id = $_POST['id'];
                $grupo->nome = $_POST['nome'];
                $grupo->descricao = $_POST['descricao'];
                
                if ($grupo->update()) {
                    $message = 'Grupo atualizado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar grupo!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $grupo->id = $_POST['id'];
                if ($grupo->delete()) {
                    $message = 'Grupo excluído com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao excluir grupo!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar dados
$grupos = $grupo->getAllWithDetails();
$estatisticas = $grupo->getEstatisticas();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Grupos - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .grupo-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }
        .grupo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .grupo-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
        }
        .grupo-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin-bottom: 1rem;
        }
        .grupo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-item {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        .feature-badge {
            font-size: 0.8rem;
            margin: 2px;
        }
        /* CSS do plano-badge removido - sistema sem planos */
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
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                Gestão de Grupos
                            </h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGrupo">
                                <i class="fas fa-plus me-1"></i>Novo Grupo
                            </button>
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
                                        <i class="fas fa-layer-group fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['total_grupos'] ?></h4>
                                        <small>Total de Grupos</small>
                                    </div>
                                </div>
                            </div>
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
                                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['usuarios_ativos'] ?></h4>
                                        <small>Usuários Ativos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-building fa-2x mb-2"></i>
                                        <h4><?= $estatisticas['total_grupos'] ?></h4>
                                        <small>Total de Grupos</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cards de Grupos -->
                        <div class="row">
                            <?php foreach ($grupos as $grupo_item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card grupo-card h-100">
                                    <div class="grupo-header">
                                        <div class="grupo-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        
                                        <h4 class="mb-2"><?= htmlspecialchars($grupo_item['nome']) ?></h4>
                                        <p class="mb-0 opacity-75"><?= htmlspecialchars($grupo_item['descricao']) ?></p>
                                        
                                        <div class="grupo-stats">
                                            <div class="stat-item">
                                                <div class="stat-number"><?= $grupo_item['total_usuarios'] ?></div>
                                                <div class="stat-label">Usuários</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-number"><?= $grupo_item['total_transacoes'] ?></div>
                                                <div class="stat-label">Transações</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-number"><?= $grupo_item['total_contas'] ?></div>
                                                <div class="stat-label">Contas</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">Informações do Grupo</h6>
                                        
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Usuários:</small><br>
                                                <strong><?= $grupo_item['total_usuarios'] ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Transações:</small><br>
                                                <strong><?= $grupo_item['total_transacoes'] ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Contas:</small><br>
                                                <strong><?= $grupo_item['total_contas'] ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Criado em:</small><br>
                                                <strong><?= date('d/m/Y', strtotime($grupo_item['created_at'])) ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editarGrupo(<?= htmlspecialchars(json_encode($grupo_item)) ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="excluirGrupo(<?= $grupo_item['id'] ?>)">
                                                <i class="fas fa-trash"></i> Excluir
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3 text-center">
                                            <small class="text-muted">
                                                Criado em: <?= date('d/m/Y', strtotime($grupo_item['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Grupo -->
    <div class="modal fade" id="modalGrupo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitulo"><i class="fas fa-layer-group me-2"></i>Novo Grupo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="action">
                        <input type="hidden" name="id" id="grupoId">
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nome do Grupo</label>
                                    <input type="text" class="form-control" name="nome" id="nome" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="3"></textarea>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarGrupo(grupo) {
            document.getElementById('modalTitulo').textContent = 'Editar Grupo';
            document.getElementById('action').value = 'update';
            document.getElementById('grupoId').value = grupo.id;
            document.getElementById('nome').value = grupo.nome;
            document.getElementById('descricao').value = grupo.descricao;
            
            new bootstrap.Modal(document.getElementById('modalGrupo')).show();
        }

        function excluirGrupo(id) {
            if (confirm('Deseja realmente excluir este grupo? Esta ação não pode ser desfeita e afetará todos os usuários do grupo.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
