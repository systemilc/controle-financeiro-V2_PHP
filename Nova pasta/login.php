<?php
require_once 'config/database.php';
require_once 'classes/Auth.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

$message = '';
$message_type = '';
$login_result = ['reason' => ''];

// Verificar mensagem de sucesso do cadastro
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'cadastro_sucesso':
            $message = 'Cadastro realizado com sucesso! Aguarde aprovação do administrador para fazer login.';
            $message_type = 'success';
            break;
    }
}

// Processar login
if($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $login_result = $auth->login($username, $password);
    
    if($login_result['success']) {
        header('Location: index.php');
        exit;
    } else {
        // Definir mensagens específicas baseadas no motivo da falha
        switch($login_result['reason']) {
            case 'user_not_found':
                $message = 'Usuário "' . htmlspecialchars($username) . '" não encontrado. Verifique o nome de usuário e tente novamente.';
                $message_type = 'danger';
                break;
            case 'wrong_password':
                $message = 'Senha incorreta para o usuário "' . htmlspecialchars($username) . '". Tente novamente.';
                $message_type = 'danger';
                break;
            case 'not_approved':
                $message = 'Olá ' . htmlspecialchars($username) . '! Sua conta ainda não foi aprovada pelo administrador.';
                $message_type = 'warning';
                break;
            case 'inactive':
                $message = 'Olá ' . htmlspecialchars($username) . '! Sua conta foi desativada.';
                $message_type = 'danger';
                break;
            case 'blocked':
                $blocked_until = isset($login_result['blocked_until']) ? $login_result['blocked_until'] : null;
                if ($blocked_until) {
                    $message = 'Olá ' . htmlspecialchars($username) . '! Sua conta foi bloqueada até ' . date('d/m/Y H:i', strtotime($blocked_until)) . '.';
                } else {
                    $message = 'Olá ' . htmlspecialchars($username) . '! Sua conta foi bloqueada.';
                }
                $message_type = 'danger';
                break;
            default:
                $message = 'Erro ao fazer login para o usuário "' . htmlspecialchars($username) . '". Tente novamente.';
                $message_type = 'danger';
        }
    }
} else {
    // Verificar se já está logado (apenas se não há POST)
    if($auth->isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/brands.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }
        .register-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card border-0">
                    <div class="login-header">
                        <i class="fas fa-wallet fa-3x mb-3"></i>
                        <h2 class="mb-0">Controle Financeiro</h2>
                        <p class="mb-0 opacity-75">Acesso Protegido</p>
                    </div>
                    <div class="login-body">
                        <?php if($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $message ?>
                                
                                <?php if(in_array($login_result['reason'] ?? '', ['not_approved', 'inactive', 'blocked'])): ?>
                                    <hr>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong>Precisa de ajuda?</strong><br>
                                            <small>Entre em contato com o administrador</small>
                                        </div>
                                        <?php 
                                        $whatsapp_message = "Olá! Sou o usuário '" . htmlspecialchars($username) . "' e preciso de ajuda com login no sistema financeiro pessoal.";
                                        $whatsapp_url = "https://wa.me/5573991040220?text=" . urlencode($whatsapp_message);
                                        ?>
                                        <a href="<?= $whatsapp_url ?>" 
                                           target="_blank" 
                                           class="btn btn-success btn-sm">
                                            <i class="fab fa-whatsapp me-1"></i>
                                            WhatsApp
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Usuário
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       required placeholder="Digite seu usuário">
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required placeholder="Digite sua senha">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Não tem uma conta? 
                                <a href="register.php" class="register-link">Crie uma aqui</a>
                            </p>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Usuário padrão: <strong>admin</strong> | Senha: <strong>123456</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
