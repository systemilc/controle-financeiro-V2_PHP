<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Convite.php';
require_once 'classes/Usuario.php';

$auth = new Auth();
$convite = new Convite();
$usuario = new Usuario();

$message = '';
$message_type = '';
$convite_data = null;

// Verificar se há token na URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $convite_data = $convite->getByToken($token);
    
    if (!$convite_data) {
        $message = 'Convite inválido ou expirado.';
        $message_type = 'danger';
    }
} else {
    $message = 'Token de convite não fornecido.';
    $message_type = 'danger';
}

// Processar aceitação do convite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $convite_data) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validações
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = 'Todos os campos são obrigatórios.';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'As senhas não coincidem.';
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = 'A senha deve ter pelo menos 6 caracteres.';
        $message_type = 'danger';
    } else {
        // Verificar se username já existe
        if ($usuario->usernameExists($username)) {
            $message = 'Nome de usuário já existe.';
            $message_type = 'danger';
        } else {
            // Criar usuário
            $usuario->username = $username;
            $usuario->password = password_hash($password, PASSWORD_DEFAULT);
            $usuario->grupo_id = $convite_data['grupo_id'];
            $usuario->role = 'collaborator';
            $usuario->is_approved = 1; // Aprovado automaticamente
            $usuario->is_active = 1;
            $usuario->email = $convite_data['email'];
            $usuario->consent_lgpd = 1; // Consentimento automático
            
            if ($usuario->create()) {
                // Verificar se o usuário foi criado com ID válido
                if(empty($usuario->id)) {
                    $message = 'Erro: Usuário criado mas sem ID válido.';
                    $message_type = 'danger';
                } else {
                    // Aceitar convite
                    $convite->id = $convite_data['id'];
                    $convite->grupo_id = $convite_data['grupo_id']; // Definir grupo_id
                    
                    try {
                        if ($convite->aceitar($usuario->id)) {
                            $message = 'Convite aceito com sucesso! Você já pode fazer login.';
                            $message_type = 'success';
                            $convite_data = null; // Limpar dados do convite
                        } else {
                            $message = 'Erro ao aceitar convite.';
                            $message_type = 'danger';
                        }
                    } catch (Exception $e) {
                        $message = 'Erro ao aceitar convite: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            } else {
                $message = 'Erro ao criar usuário.';
                $message_type = 'danger';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceitar Convite - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3>Aceitar Convite</h3>
                        </div>
                        
                        <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($convite_data): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Convite de:</h6>
                            <p class="mb-1"><strong>Grupo:</strong> <?= htmlspecialchars($convite_data['grupo_nome']) ?></p>
                            <p class="mb-1"><strong>Convidado por:</strong> <?= htmlspecialchars($convite_data['convidado_por_nome']) ?></p>
                            <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($convite_data['email']) ?></p>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" name="username" required 
                                       placeholder="Escolha um nome de usuário">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" name="password" required 
                                       placeholder="Mínimo 6 caracteres">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" name="confirm_password" required 
                                       placeholder="Digite a senha novamente">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Aceitar Convite
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Ir para Login
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
