<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Categoria.php';

$database = new Database();
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();

// Instanciar classe
$categoria = new Categoria();
$categoria->grupo_id = $current_user['grupo_id'];

// Processar ações
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $categoria->nome = $_POST['nome'];
                $categoria->tipo = $_POST['tipo'];
                $categoria->cor = $_POST['cor'];
                $categoria->icone = $_POST['icone'] ?? 'fas fa-tag';
                
                if ($categoria->create()) {
                    $message = 'Categoria criada com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao criar categoria!';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $categoria->id = $_POST['id'];
                $categoria->nome = $_POST['nome'];
                $categoria->tipo = $_POST['tipo'];
                $categoria->cor = $_POST['cor'];
                $categoria->icone = $_POST['icone'] ?? 'fas fa-tag';
                
                if ($categoria->update()) {
                    $message = 'Categoria atualizada com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar categoria!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $categoria->id = $_POST['id'];
                if ($categoria->delete()) {
                    $message = 'Categoria excluída com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao excluir categoria!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar dados
$categorias = $categoria->getAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            border-radius: 20px;
            padding: 8px 15px;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
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
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #ddd;
            display: inline-block;
            margin-right: 10px;
        }
        .category-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .category-icon {
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tags me-2"></i>Categorias</h2>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                            <i class="fas fa-plus me-2"></i>Nova Categoria
                        </button>
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalAjudaIcones">
                            <i class="fas fa-question-circle me-2"></i>Como encontrar ícones
                        </button>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Cards de Resumo -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title text-success">
                                    <i class="fas fa-arrow-up me-2"></i>Receitas
                                </h5>
                                <h3 class="text-success">
                                    <?= count(array_filter($categorias, function($c) { return $c['tipo'] == 'receita'; })) ?>
                                </h3>
                                <p class="text-muted">Categorias de receita</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title text-danger">
                                    <i class="fas fa-arrow-down me-2"></i>Despesas
                                </h5>
                                <h3 class="text-danger">
                                    <?= count(array_filter($categorias, function($c) { return $c['tipo'] == 'despesa'; })) ?>
                                </h3>
                                <p class="text-muted">Categorias de despesa</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cards de Categorias -->
                <div class="row">
                    <?php foreach ($categorias as $categoria_item): ?>
                    <?php
                    // Buscar estatísticas da categoria
                    $query_stats = "SELECT 
                        COUNT(*) as total_transacoes,
                        SUM(CASE WHEN tipo = :tipo THEN valor ELSE 0 END) as total_valor,
                        SUM(CASE WHEN tipo = :tipo AND MONTH(data_transacao) = MONTH(CURDATE()) AND YEAR(data_transacao) = YEAR(CURDATE()) THEN valor ELSE 0 END) as valor_mes_atual
                        FROM transacoes t
                        WHERE categoria_id = :categoria_id 
                        AND usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";
                    
                    $stmt_stats = $database->getConnection()->prepare($query_stats);
                    $stmt_stats->bindParam(':tipo', $categoria_item['tipo']);
                    $stmt_stats->bindParam(':categoria_id', $categoria_item['id']);
                    $stmt_stats->bindParam(':grupo_id', $grupo_id);
                    $stmt_stats->execute();
                    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="category-icon me-3" style="background-color: <?= $categoria_item['cor'] ?>">
                                            <i class="<?= $categoria_item['icone'] ?? 'fas fa-tag' ?>"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($categoria_item['nome']) ?></h5>
                                            <span class="badge bg-<?= $categoria_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                <?= $categoria_item['tipo'] == 'receita' ? 'Receita' : 'Despesa' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editCategoria(<?= htmlspecialchars(json_encode($categoria_item)) ?>)">
                                                <i class="fas fa-edit me-2"></i>Editar
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategoria(<?= $categoria_item['id'] ?>)">
                                                <i class="fas fa-trash me-2"></i>Excluir
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="category-stats">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h6 class="text-muted mb-1">Transações</h6>
                                                <h5 class="mb-0 text-primary"><?= $stats['total_transacoes'] ?></h5>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h6 class="text-muted mb-1">Total</h6>
                                                <h5 class="mb-0 text-<?= $categoria_item['tipo'] == 'receita' ? 'success' : 'danger' ?>">
                                                    R$ <?= number_format($stats['total_valor'] ?? 0, 2, ',', '.') ?>
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-item">
                                                <h6 class="text-muted mb-1">Este Mês</h6>
                                                <h5 class="mb-0 text-info">
                                                    R$ <?= number_format($stats['valor_mes_atual'] ?? 0, 2, ',', '.') ?>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Criada em <?= date('d/m/Y', strtotime($categoria_item['created_at'])) ?>
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
    
    <!-- Modal Categoria -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCategoriaTitle">Nova Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCategoria">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="nome" id="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="">Selecione...</option>
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cor *</label>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="color" class="form-control form-control-color" name="cor" id="cor" value="#007bff" required>
                                </div>
                                <div class="col-md-4">
                                    <div class="color-preview" id="colorPreview" style="background-color: #007bff"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ícone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="icon-preview" class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" name="icone" id="icone" placeholder="fas fa-tag" value="fas fa-tag">
                            </div>
                            <div class="form-text">
                                <small>Use classes do Font Awesome (ex: fas fa-shopping-cart, fas fa-home, fas fa-car)</small>
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
    
    <!-- Modal Confirmação -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmacaoTexto"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarAcao">Confirmar</button>
                </div>
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
                                <i class="fas fa-lightbulb me-2"></i>Ícones Sugeridos por Categoria
                            </h6>
                            
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="text-muted">Receitas:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-money-bill-wave text-success me-2"></i>fas fa-money-bill-wave</li>
                                        <li><i class="fas fa-hand-holding-usd text-success me-2"></i>fas fa-hand-holding-usd</li>
                                        <li><i class="fas fa-chart-line text-success me-2"></i>fas fa-chart-line</li>
                                        <li><i class="fas fa-coins text-success me-2"></i>fas fa-coins</li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-muted">Despesas:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-shopping-cart text-danger me-2"></i>fas fa-shopping-cart</li>
                                        <li><i class="fas fa-utensils text-danger me-2"></i>fas fa-utensils</li>
                                        <li><i class="fas fa-car text-danger me-2"></i>fas fa-car</li>
                                        <li><i class="fas fa-home text-danger me-2"></i>fas fa-home</li>
                                        <li><i class="fas fa-medkit text-danger me-2"></i>fas fa-medkit</li>
                                        <li><i class="fas fa-graduation-cap text-danger me-2"></i>fas fa-graduation-cap</li>
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
        // Preview da cor
        document.getElementById('cor').addEventListener('input', function() {
            document.getElementById('colorPreview').style.backgroundColor = this.value;
        });

        // Atualizar preview do ícone
        document.getElementById('icone').addEventListener('input', function() {
            const iconPreview = document.getElementById('icon-preview');
            iconPreview.className = this.value || 'fas fa-tag';
        });
        
        // Editar categoria
        function editCategoria(categoria) {
            document.getElementById('modalCategoriaTitle').textContent = 'Editar Categoria';
            document.getElementById('action').value = 'update';
            document.getElementById('id').value = categoria.id;
            document.getElementById('nome').value = categoria.nome;
            document.getElementById('tipo').value = categoria.tipo;
            document.getElementById('cor').value = categoria.cor;
            document.getElementById('icone').value = categoria.icone || 'fas fa-tag';
            document.getElementById('colorPreview').style.backgroundColor = categoria.cor;
            document.getElementById('icon-preview').className = categoria.icone || 'fas fa-tag';
            
            new bootstrap.Modal(document.getElementById('modalCategoria')).show();
        }
        
        // Excluir categoria
        function deleteCategoria(id) {
            document.getElementById('confirmacaoTexto').textContent = 'Deseja excluir esta categoria? Esta ação não pode ser desfeita.';
            document.getElementById('confirmarAcao').onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            };
            new bootstrap.Modal(document.getElementById('modalConfirmacao')).show();
        }
        
        // Resetar modal
        document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalCategoriaTitle').textContent = 'Nova Categoria';
            document.getElementById('action').value = 'create';
            document.getElementById('formCategoria').reset();
            document.getElementById('cor').value = '#007bff';
            document.getElementById('colorPreview').style.backgroundColor = '#007bff';
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