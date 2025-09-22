<?php
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Usuario.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$usuario = new Usuario($db);

$message = '';
$message_type = '';

// Verificar se já está logado
if($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Processar cadastro
if($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $usuario->username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $usuario->whatsapp = $_POST['whatsapp'] ?? '';
    $usuario->instagram = $_POST['instagram'] ?? '';
    $usuario->email = $_POST['email'] ?? '';
    $usuario->consent_lgpd = isset($_POST['consent_lgpd']) ? 1 : 0;
    $usuario->grupo_id = 1; // Grupo padrão
    $usuario->role = 'user';
    $usuario->is_approved = 0; // Precisa ser aprovado pelo admin

    // Validações
    $errors = [];

    if($usuario->usernameExists()) {
        $errors[] = "Nome de usuário já existe";
    }

    if($password !== $confirm_password) {
        $errors[] = "As senhas não coincidem";
    }

    $password_errors = $auth->validatePassword($password);
    if(!empty($password_errors)) {
        $errors = array_merge($errors, $password_errors);
    }

    if(!$usuario->consent_lgpd) {
        $errors[] = "Você deve concordar com o termo de consentimento LGPD";
    }

    if(empty($errors)) {
        $usuario->password = $auth->hashPassword($password);
        
        if($usuario->create()) {
            // Redirecionar para login com mensagem de sucesso
            header('Location: login.php?msg=cadastro_sucesso');
            exit;
        } else {
            $message = 'Erro ao realizar cadastro. Tente novamente.';
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .register-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .register-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        .login-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link:hover {
            color: #20c997;
            text-decoration: underline;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card register-card border-0">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h2 class="mb-0">Criar Nova Conta</h2>
                        <p class="mb-0 opacity-75">Junte-se ao nosso sistema</p>
                    </div>
                    <div class="register-body">
                        <?php if($message): ?>
                            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-2"></i>Nome de Usuário
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           required placeholder="Digite seu usuário" 
                                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           required placeholder="seu@email.com"
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="whatsapp" class="form-label">
                                        <i class="fas fa-phone me-2"></i>WhatsApp
                                    </label>
                                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" 
                                           placeholder="(XX) XXXXX-XXXX"
                                           value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">
                                        <i class="fab fa-instagram me-2"></i>Instagram
                                    </label>
                                    <input type="text" class="form-control" id="instagram" name="instagram" 
                                           placeholder="@seuinstagram"
                                           value="<?= htmlspecialchars($_POST['instagram'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required placeholder="Digite sua senha">
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                                <small class="form-text text-muted">
                                    Mínimo 6 caracteres, com maiúscula, minúscula, número e caractere especial.
                                </small>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirmar Senha
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required placeholder="Confirme sua senha">
                            </div>

                            <div class="form-check mb-4">
                                <input type="checkbox" class="form-check-input" id="consent_lgpd" name="consent_lgpd" required>
                                <label class="form-check-label" for="consent_lgpd">
                                    Concordo com o <a href="#" data-bs-toggle="modal" data-bs-target="#lgpdModal">Termo de Consentimento LGPD</a> 
                                    e aceito ser contatado via redes sociais para fins de marketing.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-success btn-register w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Cadastrar
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Já tem uma conta? 
                                <a href="login.php" class="login-link">Faça login aqui</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal LGPD -->
    <div class="modal fade" id="lgpdModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>Termo de Consentimento LGPD
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Ao realizar o cadastro, você concorda que seus dados (Nome de Usuário, Email, WhatsApp e Instagram) sejam armazenados para fins de acesso ao sistema e contato para marketing.</p>
                    <p>Garantimos o sigilo e a segurança das suas informações conforme a Lei Geral de Proteção de Dados (LGPD) - Lei nº 13.709/2018.</p>
                    <p>Você pode solicitar a remoção ou correção dos seus dados a qualquer momento.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação de força da senha
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            let strength = 0;
            if(password.length >= 6) strength++;
            if(/[A-Z]/.test(password)) strength++;
            if(/[a-z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength mt-2';
            if(strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if(strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Validação de confirmação de senha
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if(password !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
