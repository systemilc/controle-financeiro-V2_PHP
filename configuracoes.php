<?php
session_start();
require_once 'classes/Auth.php';
require_once 'config/database.php';
require_once 'classes/Usuario.php';

// Verificar se está logado
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$usuario = new Usuario($database->getConnection());

$message = '';
$message_type = '';

// Processar ações
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $usuario->id = $_SESSION['user_id'];
                $usuario->nome = $_POST['nome'];
                $usuario->email = $_POST['email'];
                $usuario->telefone = $_POST['telefone'];
                
                if ($usuario->update()) {
                    $_SESSION['username'] = $usuario->nome;
                    $message = 'Perfil atualizado com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao atualizar perfil!';
                    $message_type = 'danger';
                }
                break;
                
            case 'change_password':
                $usuario->id = $_SESSION['user_id'];
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verificar senha atual
                if (!$usuario->verifyPassword($current_password)) {
                    $message = 'Senha atual incorreta!';
                    $message_type = 'danger';
                    break;
                }
                
                // Verificar se as novas senhas coincidem
                if ($new_password !== $confirm_password) {
                    $message = 'As novas senhas não coincidem!';
                    $message_type = 'danger';
                    break;
                }
                
                // Verificar força da senha
                if (strlen($new_password) < 6) {
                    $message = 'A nova senha deve ter pelo menos 6 caracteres!';
                    $message_type = 'danger';
                    break;
                }
                
                $usuario->senha = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($usuario->updatePassword()) {
                    $message = 'Senha alterada com sucesso!';
                    $message_type = 'success';
                } else {
                    $message = 'Erro ao alterar senha!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar dados do usuário
$usuario->id = $_SESSION['user_id'];
$usuario_data = $usuario->readById();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
    <style>
        .config-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .config-header {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .config-header h4 {
            color: #495057;
            margin: 0;
        }
        .config-header i {
            color: #667eea;
            margin-right: 10px;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="fas fa-cog me-2 text-primary"></i>
                                Configurações
                            </h2>
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

                        <!-- Informações do Perfil -->
                        <div class="config-section">
                            <div class="config-header">
                                <h4><i class="fas fa-user"></i>Informações do Perfil</h4>
                            </div>
                            
                            <form method="POST" class="form-section">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($usuario_data['nome']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">E-mail</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($usuario_data['email']) ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" name="telefone" value="<?= htmlspecialchars($usuario_data['telefone']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Grupo</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($usuario_data['grupo_nome']) ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Função</label>
                                            <input type="text" class="form-control" value="<?= ucfirst($usuario_data['role']) ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Data de Cadastro</label>
                                            <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($usuario_data['created_at'])) ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                </button>
                            </form>
                        </div>

                        <!-- Alteração de Senha -->
                        <div class="config-section">
                            <div class="config-header">
                                <h4><i class="fas fa-lock"></i>Alteração de Senha</h4>
                            </div>
                            
                            <form method="POST" class="form-section">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Senha Atual</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Nova Senha</label>
                                            <input type="password" class="form-control" name="new_password" id="new_password" required>
                                            <div class="password-strength" id="password_strength"></div>
                                            <small class="text-muted">Mínimo de 6 caracteres</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Confirmar Nova Senha</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Alterar Senha
                                </button>
                            </form>
                        </div>

                        <!-- Configurações do Sistema -->
                        <div class="config-section">
                            <div class="config-header">
                                <h4><i class="fas fa-cogs"></i>Configurações do Sistema</h4>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fuso Horário</label>
                                        <select class="form-select" disabled>
                                            <option>America/Sao_Paulo (Brasil)</option>
                                        </select>
                                        <small class="text-muted">Configurado automaticamente</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Idioma</label>
                                        <select class="form-select" disabled>
                                            <option>Português (Brasil)</option>
                                        </select>
                                        <small class="text-muted">Configurado automaticamente</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Formato de Data</label>
                                        <select class="form-select" disabled>
                                            <option>DD/MM/AAAA</option>
                                        </select>
                                        <small class="text-muted">Configurado automaticamente</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Moeda</label>
                                        <select class="form-select" disabled>
                                            <option>Real Brasileiro (R$)</option>
                                        </select>
                                        <small class="text-muted">Configurado automaticamente</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações da Conta -->
                        <div class="config-section">
                            <div class="config-header">
                                <h4><i class="fas fa-info-circle"></i>Informações da Conta</h4>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Último Acesso</label>
                                        <input type="text" class="form-control" value="<?= $usuario_data['data_ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario_data['data_ultimo_acesso'])) : 'Nunca' ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status da Conta</label>
                                        <input type="text" class="form-control" value="<?= $usuario_data['is_active'] ? 'Ativa' : 'Inativa' ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tentativas de Login</label>
                                        <input type="text" class="form-control" value="<?= $usuario_data['tentativas_login'] ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bloqueado Até</label>
                                        <input type="text" class="form-control" value="<?= $usuario_data['bloqueado_ate'] ? date('d/m/Y H:i', strtotime($usuario_data['bloqueado_ate'])) : 'Não bloqueado' ?>" readonly>
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
    <script>
        // Verificar força da senha
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password_strength');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
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
