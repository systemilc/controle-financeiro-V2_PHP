<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/EmailManager.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();

// Verificar se é admin
if($auth->getUserRole() !== 'admin') {
    header('Location: index.php');
    exit;
}

$emailManager = new EmailManager();
$message = '';
$message_type = '';

// Processar formulário
if($_POST) {
    if(isset($_POST['action']) && $_POST['action'] == 'salvar_config') {
        // Atualizar configurações de email
        $config_file = 'config/email.php';
        $config = require $config_file;
        
        $config['smtp']['host'] = $_POST['smtp_host'];
        $config['smtp']['port'] = (int)$_POST['smtp_port'];
        $config['smtp']['encryption'] = $_POST['smtp_encryption'];
        $config['smtp']['username'] = $_POST['smtp_username'];
        $config['smtp']['password'] = $_POST['smtp_password'];
        $config['smtp']['from_email'] = $_POST['from_email'];
        $config['smtp']['from_name'] = $_POST['from_name'];
        $config['convite']['url_base'] = $_POST['url_base'];
        
        // Salvar configurações
        $config_content = "<?php\n/**\n * Configurações de Email\n * \n * Configure as informações do servidor SMTP para envio de emails\n */\n\nreturn " . var_export($config, true) . ";\n?>";
        
        if(file_put_contents($config_file, $config_content)) {
            $message = 'Configurações de email salvas com sucesso!';
            $message_type = 'success';
        } else {
            $message = 'Erro ao salvar configurações de email.';
            $message_type = 'danger';
        }
    }
    
    if(isset($_POST['action']) && $_POST['action'] == 'testar_smtp') {
        // Testar conexão SMTP
        $test_result = $emailManager->testarConexaoSMTP($_POST['smtp_host'], $_POST['smtp_port'], $_POST['smtp_encryption'], $_POST['smtp_username'], $_POST['smtp_password']);
        
        if($test_result['success']) {
            $message = '✅ Conexão SMTP bem-sucedida! ' . $test_result['message'];
            $message_type = 'success';
        } else {
            $message = '❌ Falha na conexão SMTP: ' . $test_result['message'];
            $message_type = 'danger';
        }
    }
}

// Obter configurações atuais
$config = require 'config/email.php';
$status = $emailManager->getStatusConfiguracao();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Email - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
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
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                Configurações de Email
                            </h2>
                        </div>
                    </div>

                    <?php if($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Status da Configuração -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Status da Configuração
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['smtp_host'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            Servidor SMTP
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['smtp_username'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            Usuário SMTP
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['smtp_password'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            Senha SMTP
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['from_email'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            Email Remetente
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['from_name'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            Nome Remetente
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-<?php echo $status['completo'] ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                            <strong>Configuração Completa</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulário de Configuração -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cog me-2"></i>
                                Configurações SMTP
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="salvar_config">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                   value="<?php echo htmlspecialchars($config['smtp']['host']); ?>" 
                                                   placeholder="smtp.gmail.com">
                                            <div class="form-text">Ex: smtp.gmail.com, smtp.outlook.com</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="smtp_port" class="form-label">Porta</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                   value="<?php echo $config['smtp']['port']; ?>" 
                                                   placeholder="587">
                                            <div class="form-text">587 (TLS), 465 (SSL), 25 (sem criptografia)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="smtp_encryption" class="form-label">Criptografia</label>
                                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                <option value="tls" <?php echo $config['smtp']['encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo $config['smtp']['encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="" <?php echo empty($config['smtp']['encryption']) ? 'selected' : ''; ?>>Nenhuma</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_username" class="form-label">Usuário SMTP</label>
                                            <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                                   value="<?php echo htmlspecialchars($config['smtp']['username']); ?>" 
                                                   placeholder="seu@email.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtp_password" class="form-label">Senha SMTP</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                   value="<?php echo htmlspecialchars($config['smtp']['password']); ?>" 
                                                   placeholder="Sua senha ou senha de aplicativo">
                                            <div class="form-text">Para Gmail, use senha de aplicativo</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="from_email" class="form-label">Email Remetente</label>
                                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                                   value="<?php echo htmlspecialchars($config['smtp']['from_email']); ?>" 
                                                   placeholder="noreply@seudominio.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="from_name" class="form-label">Nome Remetente</label>
                                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                                   value="<?php echo htmlspecialchars($config['smtp']['from_name']); ?>" 
                                                   placeholder="Controle Financeiro">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="url_base" class="form-label">URL Base do Sistema</label>
                                    <input type="url" class="form-control" id="url_base" name="url_base" 
                                           value="<?php echo htmlspecialchars($config['convite']['url_base']); ?>" 
                                           placeholder="https://seudominio.com/controle_financeiro">
                                    <div class="form-text">URL completa onde o sistema está instalado (para links de convite)</div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-warning" onclick="testarConexao()">
                                        <i class="fas fa-plug me-2"></i>Testar Conexão SMTP
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Salvar Configurações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Formulário de Teste SMTP (oculto) -->
                    <form id="testForm" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="testar_smtp">
                        <input type="hidden" name="smtp_host" id="test_smtp_host">
                        <input type="hidden" name="smtp_port" id="test_smtp_port">
                        <input type="hidden" name="smtp_encryption" id="test_smtp_encryption">
                        <input type="hidden" name="smtp_username" id="test_smtp_username">
                        <input type="hidden" name="smtp_password" id="test_smtp_password">
                    </form>

                    <!-- Instruções -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>
                                Instruções de Configuração
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Gmail:</h6>
                            <ul>
                                <li>Servidor: smtp.gmail.com</li>
                                <li>Porta: 587 (TLS) ou 465 (SSL)</li>
                                <li>Usuário: seu email do Gmail</li>
                                <li>Senha: <strong>Senha de aplicativo</strong> (não sua senha normal)</li>
                            </ul>

                            <h6>Outlook/Hotmail:</h6>
                            <ul>
                                <li>Servidor: smtp-mail.outlook.com</li>
                                <li>Porta: 587 (TLS)</li>
                                <li>Usuário: seu email do Outlook</li>
                                <li>Senha: sua senha normal</li>
                            </ul>

                            <h6>Yahoo:</h6>
                            <ul>
                                <li>Servidor: smtp.mail.yahoo.com</li>
                                <li>Porta: 587 (TLS) ou 465 (SSL)</li>
                                <li>Usuário: seu email do Yahoo</li>
                                <li>Senha: <strong>Senha de aplicativo</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function testarConexao() {
            // Coletar dados do formulário
            const smtp_host = document.getElementById('smtp_host').value;
            const smtp_port = document.getElementById('smtp_port').value;
            const smtp_encryption = document.getElementById('smtp_encryption').value;
            const smtp_username = document.getElementById('smtp_username').value;
            const smtp_password = document.getElementById('smtp_password').value;
            
            // Validar campos obrigatórios
            if (!smtp_host || !smtp_port || !smtp_username || !smtp_password) {
                alert('Por favor, preencha todos os campos obrigatórios antes de testar a conexão.');
                return;
            }
            
            // Preencher formulário de teste
            document.getElementById('test_smtp_host').value = smtp_host;
            document.getElementById('test_smtp_port').value = smtp_port;
            document.getElementById('test_smtp_encryption').value = smtp_encryption;
            document.getElementById('test_smtp_username').value = smtp_username;
            document.getElementById('test_smtp_password').value = smtp_password;
            
            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testando...';
            btn.disabled = true;
            
            // Enviar formulário de teste
            document.getElementById('testForm').submit();
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
