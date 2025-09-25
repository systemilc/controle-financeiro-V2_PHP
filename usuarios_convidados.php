<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/UsuarioConvidado.php';
require_once 'classes/Convite.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();

// Instanciar classes
$usuario_convidado = new UsuarioConvidado();
$convite = new Convite();

// Processar ações
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remover_usuario':
                $usuario_id = $_POST['usuario_id'];
                
                if ($usuario_convidado->removerAcesso($usuario_id, $current_user['grupo_id'])) {
                    $message = 'Usuário removido com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao remover usuário.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar dados
$usuarios_convidados = $usuario_convidado->getByGrupo($current_user['grupo_id']);
$estatisticas_usuarios = $usuario_convidado->getEstatisticasByGrupo($current_user['grupo_id']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários Convidados - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php 
            $currentPage = 'usuarios_convidados.php';
            include 'includes/sidebar.php'; 
            ?>
            
            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-user-plus me-2"></i>Usuários Convidados</h2>
                            <a href="convites.php" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Gerenciar Convites
                            </a>
                        </div>
                    </div>
                    
                    <!-- Mensagens -->
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cards de Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Total de Convidados</h6>
                                            <h3><?= $estatisticas_usuarios['total_usuarios_convidados'] ?></h3>
                                        </div>
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Usuários Ativos</h6>
                                            <h3><?= $estatisticas_usuarios['usuarios_ativos'] ?></h3>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Usuários Inativos</h6>
                                            <h3><?= $estatisticas_usuarios['usuarios_inativos'] ?></h3>
                                        </div>
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Usuários Convidados -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Usuários Convidados</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($usuarios_convidados)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-user-plus fa-4x text-muted mb-3"></i>
                                    <h4>Nenhum usuário convidado</h4>
                                    <p class="text-muted">Você ainda não convidou nenhum usuário para este grupo.</p>
                                    <a href="convites.php" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Primeiro Convite
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Usuário</th>
                                                <th>Email</th>
                                                <th>Convite Aceito em</th>
                                                <th>Status</th>
                                                <th>Permissões</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usuarios_convidados as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1em;">
                                                            <?= strtoupper(substr($usuario['username'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($usuario['username']) ?></strong>
                                                            <br><small class="text-muted">Convidado</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                                <td>
                                                    <small><?= date('d/m/Y H:i', strtotime($usuario['data_aceite'])) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $usuario['is_ativo'] ? 'success' : 'secondary' ?>">
                                                        <i class="fas fa-<?= $usuario['is_ativo'] ? 'check' : 'times' ?> me-1"></i>
                                                        <?= $usuario['is_ativo'] ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $permissoes = json_decode($usuario['permissoes'], true);
                                                    if ($permissoes) {
                                                        $permissoes_texto = [];
                                                        if ($permissoes['visualizar_transacoes']) $permissoes_texto[] = 'Ver Transações';
                                                        if ($permissoes['criar_transacoes']) $permissoes_texto[] = 'Criar Transações';
                                                        if ($permissoes['editar_transacoes']) $permissoes_texto[] = 'Editar Transações';
                                                        if ($permissoes['deletar_transacoes']) $permissoes_texto[] = 'Deletar Transações';
                                                        if ($permissoes['visualizar_relatorios']) $permissoes_texto[] = 'Ver Relatórios';
                                                        if ($permissoes['gerenciar_categorias']) $permissoes_texto[] = 'Gerenciar Categorias';
                                                        if ($permissoes['gerenciar_contas']) $permissoes_texto[] = 'Gerenciar Contas';
                                                        if ($permissoes['gerenciar_usuarios']) $permissoes_texto[] = 'Gerenciar Usuários';
                                                        
                                                        echo '<small>' . implode(', ', $permissoes_texto) . '</small>';
                                                    } else {
                                                        echo '<small class="text-muted">Permissões padrão</small>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($usuario['is_ativo']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="removerUsuario(<?= $usuario['usuario_id'] ?>)">
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
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Informações Adicionais -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5><i class="fas fa-info-circle me-2"></i>Informações sobre Usuários Convidados</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>O que os usuários convidados podem fazer:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Visualizar todas as transações do grupo</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Adicionar novas transações</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Acessar relatórios financeiros</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Gerenciar categorias e contas</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Limitações:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-times text-danger me-2"></i>Não podem convidar outros usuários</li>
                                        <li><i class="fas fa-times text-danger me-2"></i>Não podem alterar configurações do grupo</li>
                                        <li><i class="fas fa-times text-danger me-2"></i>Não podem remover outros usuários</li>
                                        <li><i class="fas fa-times text-danger me-2"></i>Acesso pode ser revogado a qualquer momento</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulário oculto para ações -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="actionInput">
        <input type="hidden" name="usuario_id" id="usuarioIdInput">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removerUsuario(usuarioId) {
            if (confirm('Tem certeza que deseja remover este usuário do grupo? Ele perderá acesso a todas as transações.')) {
                document.getElementById('actionInput').value = 'remover_usuario';
                document.getElementById('usuarioIdInput').value = usuarioId;
                document.getElementById('actionForm').submit();
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
