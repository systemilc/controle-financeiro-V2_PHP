<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Convite.php';
require_once 'classes/UsuarioConvidado.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();

// Instanciar classes
$convite = new Convite();
$usuario_convidado = new UsuarioConvidado();

// Processar ações
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'enviar_convite':
                $email = $_POST['email'];
                $observacoes = $_POST['observacoes'] ?? '';
                
                // Verificação de limites removida - sistema sem planos
                
                // Verificar se usuário já está no grupo
                if ($convite->usuarioJaNoGrupo($email, $current_user['grupo_id'])) {
                    $message = 'Este usuário já está no grupo.';
                    $message_type = 'warning';
                    break;
                }
                
                $convite->grupo_id = $current_user['grupo_id'];
                $convite->convidado_por = $current_user['id'];
                $convite->email_convidado = $email;
                $convite->observacoes = $observacoes;
                
                if ($convite->create()) {
                    $message = 'Convite enviado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao enviar convite.';
                    $message_type = 'danger';
                }
                break;
                
            case 'gerar_link':
                $observacoes = $_POST['observacoes'] ?? '';
                
                $convite->grupo_id = $current_user['grupo_id'];
                $convite->convidado_por = $current_user['id'];
                $convite->email_convidado = ''; // Link genérico
                $convite->observacoes = $observacoes;
                
                if ($convite->create()) {
                    $message = 'Link de convite gerado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao gerar link de convite.';
                    $message_type = 'danger';
                }
                break;
                
            case 'cancelar_convite':
                $convite_id = $_POST['convite_id'];
                $convite->id = $convite_id;
                
                if ($convite->cancelar()) {
                    $message = 'Convite cancelado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao cancelar convite.';
                    $message_type = 'danger';
                }
                break;
                
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
$convites = $convite->getByGrupo($current_user['grupo_id']);
$usuarios_convidados = $usuario_convidado->getByGrupo($current_user['grupo_id']);
$estatisticas_convites = $convite->getEstatisticasByGrupo($current_user['grupo_id']);
$estatisticas_usuarios = $usuario_convidado->getEstatisticasByGrupo($current_user['grupo_id']);
// Limites removidos - sistema sem planos
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convites - Controle Financeiro</title>
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
            $currentPage = 'convites.php';
            include 'includes/sidebar.php'; 
            ?>
            
            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-user-plus me-2"></i>Convites</h2>
                            <div class="btn-group">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEnviarConvite">
                                    <i class="fas fa-envelope me-2"></i>Enviar por Email
                                </button>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGerarLink">
                                    <i class="fas fa-link me-2"></i>Gerar Link
                                </button>
                                <button class="btn btn-warning" onclick="processarFilaEmails()" id="btnProcessarEmails">
                                    <i class="fas fa-cog me-2"></i>Processar Emails
                                </button>
                            </div>
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
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Convites Enviados</h6>
                                            <h3><?= $estatisticas_convites['total_convites'] ?></h3>
                                        </div>
                                        <i class="fas fa-paper-plane fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Pendentes</h6>
                                            <h3><?= $estatisticas_convites['pendentes'] ?></h3>
                                        </div>
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Aceitos</h6>
                                            <h3><?= $estatisticas_convites['aceitos'] ?></h3>
                                        </div>
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Limite do plano removido - sistema sem planos -->
                    </div>
                    
                    <!-- Abas -->
                    <ul class="nav nav-tabs" id="convitesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="convites-tab" data-bs-toggle="tab" data-bs-target="#convites" type="button" role="tab">
                                <i class="fas fa-paper-plane me-2"></i>Convites Enviados
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Usuários Convidados
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="convitesTabsContent">
                        <!-- Tab Convites -->
                        <div class="tab-pane fade show active" id="convites" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Email/Link</th>
                                                    <th>Status</th>
                                                    <th>Enviado em</th>
                                                    <th>Expira em</th>
                                                    <th>Link de Convite</th>
                                                    <th>Observações</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($convites as $convite_item): ?>
                                                <tr>
                                                    <td>
                                                        <?php if(empty($convite_item['email_convidado'])): ?>
                                                            <span class="badge bg-info">Link de Convite</span>
                                                            <br><small class="text-muted">Token: <?= substr($convite_item['token'], 0, 8) ?>...</small>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($convite_item['email_convidado']) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        $status_icon = '';
                                                        switch ($convite_item['status']) {
                                                            case 'pendente':
                                                                $status_class = 'warning';
                                                                $status_icon = 'clock';
                                                                break;
                                                            case 'aceito':
                                                                $status_class = 'success';
                                                                $status_icon = 'check';
                                                                break;
                                                            case 'recusado':
                                                                $status_class = 'danger';
                                                                $status_icon = 'times';
                                                                break;
                                                            case 'expirado':
                                                                $status_class = 'secondary';
                                                                $status_icon = 'exclamation';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?= $status_class ?>">
                                                            <i class="fas fa-<?= $status_icon ?> me-1"></i>
                                                            <?= ucfirst($convite_item['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($convite_item['data_envio'])) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($convite_item['data_expiracao'])) ?></td>
                                                    <td>
                                                        <?php if(empty($convite_item['email_convidado'])): ?>
                                                            <?php 
                                                            require_once 'classes/EmailManager.php';
                                                            $emailManager = new EmailManager();
                                                            $link_convite = $emailManager->gerarLinkConvite($convite_item['token']);
                                                            ?>
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" class="form-control" value="<?= htmlspecialchars($link_convite) ?>" readonly>
                                                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLink('<?= htmlspecialchars($link_convite) ?>')">
                                                                    <i class="fas fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($convite_item['observacoes']) ?></td>
                                                    <td>
                                                        <?php if ($convite_item['status'] === 'pendente'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="cancelarConvite(<?= $convite_item['id'] ?>)">
                                                            <i class="fas fa-times"></i> Cancelar
                                                        </button>
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
                        
                        <!-- Tab Usuários -->
                        <div class="tab-pane fade" id="usuarios" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuário</th>
                                                    <th>Email</th>
                                                    <th>Convite Aceito em</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios_convidados as $usuario): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($usuario['username']) ?></td>
                                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($usuario['data_aceite'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $usuario['is_ativo'] ? 'success' : 'secondary' ?>">
                                                            <i class="fas fa-<?= $usuario['is_ativo'] ? 'check' : 'times' ?> me-1"></i>
                                                            <?= $usuario['is_ativo'] ? 'Ativo' : 'Inativo' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($usuario['is_ativo']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="removerUsuario(<?= $usuario['usuario_id'] ?>)">
                                                            <i class="fas fa-user-times"></i> Remover
                                                        </button>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Enviar Convite -->
    <div class="modal fade" id="modalEnviarConvite" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar Convite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="enviar_convite">
                        <div class="mb-3">
                            <label class="form-label">Email do Convidado</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observações (opcional)</label>
                            <textarea class="form-control" name="observacoes" rows="3" 
                                      placeholder="Mensagem personalizada para o convidado..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            O convite expira em 7 dias. O usuário receberá um link para aceitar o convite.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Convite</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Gerar Link -->
    <div class="modal fade" id="modalGerarLink" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerar Link de Convite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="gerar_link">
                        <div class="mb-3">
                            <label class="form-label">Observações (opcional)</label>
                            <textarea class="form-control" name="observacoes" rows="3" 
                                      placeholder="Mensagem personalizada para o convidado..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Um link único será gerado que pode ser compartilhado com qualquer pessoa. 
                            O link expira em 7 dias.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Gerar Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulário oculto para ações -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="actionInput">
        <input type="hidden" name="convite_id" id="conviteIdInput">
        <input type="hidden" name="usuario_id" id="usuarioIdInput">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelarConvite(conviteId) {
            if (confirm('Tem certeza que deseja cancelar este convite?')) {
                document.getElementById('actionInput').value = 'cancelar_convite';
                document.getElementById('conviteIdInput').value = conviteId;
                document.getElementById('actionForm').submit();
            }
        }
        
        function removerUsuario(usuarioId) {
            if (confirm('Tem certeza que deseja remover este usuário do grupo?')) {
                document.getElementById('actionInput').value = 'remover_usuario';
                document.getElementById('usuarioIdInput').value = usuarioId;
                document.getElementById('actionForm').submit();
            }
        }
        
        function copiarLink(link) {
            navigator.clipboard.writeText(link).then(function() {
                // Mostrar feedback visual
                const button = event.target.closest('button');
                const originalIcon = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(function() {
                    button.innerHTML = originalIcon;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
                alert('Erro ao copiar o link. Tente selecionar e copiar manualmente.');
            });
        }

        // Função para processar fila de emails
        function processarFilaEmails() {
            const btn = document.getElementById('btnProcessarEmails');
            const originalText = btn.innerHTML;
            
            // Mostrar loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
            btn.disabled = true;
            
            // Fazer requisição AJAX
            fetch('ajax_processar_emails.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const resultado = data.resultado;
                    const stats = data.estatisticas;
                    
                    alert(`Emails processados com sucesso!\n\n` +
                          `Processados: ${resultado.processados}\n` +
                          `Falhas: ${resultado.falhas}\n` +
                          `Total: ${resultado.total}\n\n` +
                          `Estatísticas da fila:\n` +
                          `Pendentes: ${stats.pendentes}\n` +
                          `Enviados: ${stats.enviados}\n` +
                          `Falhas: ${stats.falhas}`);
                    
                    // Recarregar página para atualizar dados
                    location.reload();
                } else {
                    alert('Erro ao processar emails: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar emails. Tente novamente.');
            })
            .finally(() => {
                // Restaurar botão
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
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
