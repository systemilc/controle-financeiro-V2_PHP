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
                $usuario->endereco = $_POST['endereco'];
                $usuario->cidade = $_POST['cidade'];
                $usuario->estado = $_POST['estado'];
                $usuario->cep = $_POST['cep'];
                
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
                
            case 'update_avatar':
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/avatars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            $usuario->id = $_SESSION['user_id'];
                            $usuario->avatar = $new_filename;
                            
                            if ($usuario->updateAvatar()) {
                                $message = 'Avatar atualizado com sucesso!';
                                $message_type = 'success';
                            } else {
                                $message = 'Erro ao salvar avatar no banco de dados!';
                                $message_type = 'danger';
                            }
                        } else {
                            $message = 'Erro ao fazer upload do arquivo!';
                            $message_type = 'danger';
                        }
                    } else {
                        $message = 'Formato de arquivo não permitido! Use JPG, PNG ou GIF.';
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Nenhum arquivo selecionado!';
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
    <title>Meu Perfil - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        .profile-info {
            text-align: center;
        }
        .profile-name {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section-header {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .section-header h4 {
            color: #495057;
            margin: 0;
        }
        .section-header i {
            color: #667eea;
            margin-right: 10px;
        }
        .avatar-upload {
            position: relative;
            display: inline-block;
        }
        .avatar-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .avatar-upload:hover .avatar-overlay {
            opacity: 1;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
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
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <div class="avatar-upload">
                                        <img src="<?= $usuario_data['avatar'] ? 'uploads/avatars/' . $usuario_data['avatar'] : 'https://via.placeholder.com/120x120/667eea/ffffff?text=' . substr($usuario_data['nome'], 0, 1) ?>" 
                                             alt="Avatar" class="profile-avatar" id="avatar-preview">
                                        <div class="avatar-overlay">
                                            <i class="fas fa-camera text-white fa-2x"></i>
                                        </div>
                                        <input type="file" name="avatar" id="avatar-input" accept="image/*">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="profile-info">
                                        <h1 class="profile-name"><?= htmlspecialchars($usuario_data['nome']) ?></h1>
                                        <p class="profile-role">
                                            <i class="fas fa-user-tag me-2"></i>
                                            <?= ucfirst($usuario_data['funcao']) ?> • 
                                            <i class="fas fa-users me-1"></i>
                                            <?= htmlspecialchars($usuario_data['grupo_nome']) ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="fas fa-envelope me-2"></i>
                                            <?= htmlspecialchars($usuario_data['email']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
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
                        <div class="profile-section">
                            <div class="section-header">
                                <h4><i class="fas fa-chart-bar"></i>Estatísticas da Conta</h4>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $usuario_data['data_ultimo_acesso'] ? '1' : '0' ?></div>
                                    <div class="stat-label">Último Acesso</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $usuario_data['tentativas_login'] ?></div>
                                    <div class="stat-label">Tentativas de Login</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $usuario_data['is_active'] ? '1' : '0' ?></div>
                                    <div class="stat-label">Status da Conta</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $usuario_data['bloqueado_ate'] ? '1' : '0' ?></div>
                                    <div class="stat-label">Conta Bloqueada</div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações Pessoais -->
                        <div class="profile-section">
                            <div class="section-header">
                                <h4><i class="fas fa-user"></i>Informações Pessoais</h4>
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
                                            <label class="form-label">CEP</label>
                                            <input type="text" class="form-control" name="cep" value="<?= htmlspecialchars($usuario_data['cep']) ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Endereço</label>
                                            <input type="text" class="form-control" name="endereco" value="<?= htmlspecialchars($usuario_data['endereco']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Cidade</label>
                                            <input type="text" class="form-control" name="cidade" value="<?= htmlspecialchars($usuario_data['cidade']) ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" name="estado">
                                                <option value="">Selecione...</option>
                                                <option value="AC" <?= $usuario_data['estado'] === 'AC' ? 'selected' : '' ?>>Acre</option>
                                                <option value="AL" <?= $usuario_data['estado'] === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                                <option value="AP" <?= $usuario_data['estado'] === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                                <option value="AM" <?= $usuario_data['estado'] === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                                <option value="BA" <?= $usuario_data['estado'] === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                                <option value="CE" <?= $usuario_data['estado'] === 'CE' ? 'selected' : '' ?>>Ceará</option>
                                                <option value="DF" <?= $usuario_data['estado'] === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                                <option value="ES" <?= $usuario_data['estado'] === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                                <option value="GO" <?= $usuario_data['estado'] === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                                <option value="MA" <?= $usuario_data['estado'] === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                                <option value="MT" <?= $usuario_data['estado'] === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                                <option value="MS" <?= $usuario_data['estado'] === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                                <option value="MG" <?= $usuario_data['estado'] === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                                <option value="PA" <?= $usuario_data['estado'] === 'PA' ? 'selected' : '' ?>>Pará</option>
                                                <option value="PB" <?= $usuario_data['estado'] === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                                <option value="PR" <?= $usuario_data['estado'] === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                                <option value="PE" <?= $usuario_data['estado'] === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                                <option value="PI" <?= $usuario_data['estado'] === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                                <option value="RJ" <?= $usuario_data['estado'] === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                                <option value="RN" <?= $usuario_data['estado'] === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                                <option value="RS" <?= $usuario_data['estado'] === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                                <option value="RO" <?= $usuario_data['estado'] === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                                <option value="RR" <?= $usuario_data['estado'] === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                                <option value="SC" <?= $usuario_data['estado'] === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                                <option value="SP" <?= $usuario_data['estado'] === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                                <option value="SE" <?= $usuario_data['estado'] === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                                <option value="TO" <?= $usuario_data['estado'] === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                            </select>
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
                        <div class="profile-section">
                            <div class="section-header">
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

                        <!-- Upload de Avatar -->
                        <div class="profile-section">
                            <div class="section-header">
                                <h4><i class="fas fa-camera"></i>Foto do Perfil</h4>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data" class="form-section">
                                <input type="hidden" name="action" value="update_avatar">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Selecionar Nova Foto</label>
                                            <input type="file" class="form-control" name="avatar" accept="image/*" id="avatar-file">
                                            <small class="text-muted">Formatos permitidos: JPG, PNG, GIF (máximo 2MB)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Preview</label>
                                            <div class="text-center">
                                                <img src="<?= $usuario_data['avatar'] ? 'uploads/avatars/' . $usuario_data['avatar'] : 'https://via.placeholder.com/100x100/667eea/ffffff?text=' . substr($usuario_data['nome'], 0, 1) ?>" 
                                                     alt="Preview" class="img-thumbnail" id="avatar-preview-file" style="width: 100px; height: 100px; object-fit: cover;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload me-2"></i>Atualizar Foto
                                </button>
                            </form>
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

        // Preview do avatar
        document.getElementById('avatar-file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview-file').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Upload automático do avatar no header
        document.getElementById('avatar-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('action', 'update_avatar');
                
                fetch('perfil.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
            }
        });
    </script>
</body>
</html>
