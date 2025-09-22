<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Fornecedor.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$fornecedor = new Fornecedor($db);

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
            $fornecedor->nome = $_POST['nome'];
            $fornecedor->grupo_id = $grupo_id;
            $fornecedor->cnpj = $_POST['cnpj'] ?? '';
            $fornecedor->email = $_POST['email'] ?? '';
            $fornecedor->telefone = $_POST['telefone'] ?? '';
            $fornecedor->endereco = $_POST['endereco'] ?? '';
            
            if($fornecedor->exists($fornecedor->nome, $grupo_id)) {
                $message = 'Já existe um fornecedor com este nome no grupo.';
                $message_type = 'warning';
            } else {
                if($fornecedor->create()) {
                    $message = 'Fornecedor criado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao criar fornecedor.';
                    $message_type = 'danger';
                }
            }
        } elseif($_POST['action'] == 'update') {
            $fornecedor->id = $_POST['id'];
            $fornecedor->nome = $_POST['nome'];
            $fornecedor->cnpj = $_POST['cnpj'] ?? '';
            $fornecedor->email = $_POST['email'] ?? '';
            $fornecedor->telefone = $_POST['telefone'] ?? '';
            $fornecedor->endereco = $_POST['endereco'] ?? '';
            
            if($fornecedor->exists($fornecedor->nome, $grupo_id, $fornecedor->id)) {
                $message = 'Já existe um fornecedor com este nome no grupo.';
                $message_type = 'warning';
            } else {
                if($fornecedor->update()) {
                    $message = 'Fornecedor atualizado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar fornecedor.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Processar exclusão
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $fornecedor->id = $_GET['id'];
    
    if($fornecedor->canDelete()) {
        if($fornecedor->delete()) {
            $message = 'Fornecedor excluído com sucesso!';
            $message_type = 'success';
        } else {
            $message = 'Erro ao excluir fornecedor.';
            $message_type = 'danger';
        }
    } else {
        $message = 'Não é possível excluir este fornecedor pois ele possui compras associadas.';
        $message_type = 'warning';
    }
}

// Filtros
$filtro_nome = $_GET['nome'] ?? '';

// Obter fornecedores
$stmt_fornecedores = $fornecedor->read($grupo_id, $filtro_nome);
$fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
        .supplier-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #28a745;
            transition: all 0.3s ease;
        }
        .supplier-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stats-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }
        .contact-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
                        <?php include 'includes/sidebar.php'; ?>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                        <a class="nav-link" href="transacoes.php">
                            <i class="fas fa-exchange-alt"></i>Transações
                        </a>
                        <a class="nav-link" href="pendentes.php">
                            <i class="fas fa-clock"></i>Pendentes
                        </a>
                        <a class="nav-link" href="contas.php">
                            <i class="fas fa-university"></i>Contas
                        </a>
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags"></i>Categorias
                        </a>
                        <a class="nav-link" href="tipos_pagamento.php">
                            <i class="fas fa-credit-card"></i>Tipos de Pagamento
                        </a>
                        <a class="nav-link active" href="fornecedores.php">
                            <i class="fas fa-truck"></i>Fornecedores
                        </a>
                        <a class="nav-link" href="produtos.php">
                            <i class="fas fa-box"></i>Produtos
                        </a>
                        <a class="nav-link" href="compras.php">
                            <i class="fas fa-shopping-cart"></i>Compras
                        </a>
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar"></i>Relatórios
                        </a>
                        <a class="nav-link" href="notificacoes.php">
                            <i class="fas fa-bell"></i>Notificações
                            <span id="notification-count" class="badge bg-danger ms-2" style="display: none;">0</span>
                        </a>
                        <?php if($auth->isAdmin()): ?>
                        <hr class="text-white-50">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users"></i>Usuários
                        </a>
                        <a class="nav-link" href="grupos.php">
                            <i class="fas fa-layer-group"></i>Grupos
                        </a>
                        <?php endif; ?>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>Sair
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-truck me-2 text-primary"></i>
                                Fornecedores
                            </h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFornecedor">
                                <i class="fas fa-plus me-1"></i>Novo Fornecedor
                            </button>
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

                        <!-- Filtros -->
                        <div class="card card-modern mb-4">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-filter me-2"></i>Filtros
                                </h6>
                                <form method="GET" class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome do Fornecedor</label>
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               value="<?= htmlspecialchars($filtro_nome) ?>" 
                                               placeholder="Digite o nome do fornecedor">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <a href="fornecedores.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times"></i> Limpar
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Lista de Fornecedores -->
                        <div class="row">
                            <?php if(empty($fornecedores)): ?>
                                <div class="col-12">
                                    <div class="card card-modern">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nenhum fornecedor encontrado</h5>
                                            <p class="text-muted">
                                                <?php if($filtro_nome): ?>
                                                    Nenhum fornecedor encontrado com o nome "<?= htmlspecialchars($filtro_nome) ?>".
                                                <?php else: ?>
                                                    Comece criando seus primeiros fornecedores.
                                                <?php endif; ?>
                                            </p>
                                            <?php if(!$filtro_nome): ?>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFornecedor">
                                                    <i class="fas fa-plus me-1"></i>Criar Primeiro Fornecedor
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($fornecedores as $fornecedor_item): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="supplier-card">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1"><?= htmlspecialchars($fornecedor_item['nome']) ?></h5>
                                                    <small class="text-muted"><?= htmlspecialchars($fornecedor_item['grupo_nome']) ?></small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button class="dropdown-item" onclick="editarFornecedor(<?= htmlspecialchars(json_encode($fornecedor_item)) ?>)">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="compras.php?fornecedor_id=<?= $fornecedor_item['id'] ?>">
                                                                <i class="fas fa-shopping-cart me-2"></i>Ver Compras
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="fornecedores.php?action=delete&id=<?= $fornecedor_item['id'] ?>" 
                                                               onclick="return confirm('Tem certeza que deseja excluir este fornecedor?')">
                                                                <i class="fas fa-trash me-2"></i>Excluir
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <?php if($fornecedor_item['cnpj'] || $fornecedor_item['email'] || $fornecedor_item['telefone']): ?>
                                                <div class="contact-info">
                                                    <?php if($fornecedor_item['cnpj']): ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-card me-1"></i>
                                                                CNPJ: <?= htmlspecialchars($fornecedor_item['cnpj']) ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($fornecedor_item['email']): ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted">
                                                                <i class="fas fa-envelope me-1"></i>
                                                                <?= htmlspecialchars($fornecedor_item['email']) ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($fornecedor_item['telefone']): ?>
                                                        <div class="mb-1">
                                                            <small class="text-muted">
                                                                <i class="fas fa-phone me-1"></i>
                                                                <?= htmlspecialchars($fornecedor_item['telefone']) ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Cadastrado em <?= date('d/m/Y', strtotime($fornecedor_item['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <span class="stats-badge">
                                                        <i class="fas fa-shopping-cart me-1"></i>
                                                        Ver Compras
                                                    </span>
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

    <!-- Modal para Novo/Editar Fornecedor -->
    <div class="modal fade" id="modalFornecedor" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Novo Fornecedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formFornecedor">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="fornecedorId">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nome" class="form-label">Nome do Fornecedor *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required 
                                       placeholder="Ex: Supermercado Central, Farmácia São João">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                       placeholder="00.000.000/0000-00">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="contato@fornecedor.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" 
                                       placeholder="(XX) XXXXX-XXXX">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="3" 
                                      placeholder="Rua, número, bairro, cidade, estado"></textarea>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarFornecedor(fornecedor) {
            document.getElementById('modalTitulo').textContent = 'Editar Fornecedor';
            document.getElementById('action').value = 'update';
            document.getElementById('fornecedorId').value = fornecedor.id;
            document.getElementById('nome').value = fornecedor.nome;
            document.getElementById('cnpj').value = fornecedor.cnpj;
            document.getElementById('email').value = fornecedor.email;
            document.getElementById('telefone').value = fornecedor.telefone;
            document.getElementById('endereco').value = fornecedor.endereco;
            
            var modal = new bootstrap.Modal(document.getElementById('modalFornecedor'));
            modal.show();
        }

        // Limpar formulário quando modal é fechado
        document.getElementById('modalFornecedor').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitulo').textContent = 'Novo Fornecedor';
            document.getElementById('action').value = 'create';
            document.getElementById('fornecedorId').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('cnpj').value = '';
            document.getElementById('email').value = '';
            document.getElementById('telefone').value = '';
            document.getElementById('endereco').value = '';
        });

        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if(value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if(value.length <= 11) {
                if(value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
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
</body>
</html>
