<?php
require_once 'config/database.php';
require_once 'classes/Usuario.php';

class Auth {
    private $conn;
    private $usuario;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
        $this->usuario = new Usuario($this->conn);
    }

    // Iniciar sessão
    public function startSession() {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Login
    public function login($username, $password) {
        $this->startSession();
        
        // Primeiro, verificar se o usuário existe
        $this->usuario->username = $username;
        if (!$this->usuario->usernameExists()) {
            return ['success' => false, 'reason' => 'user_not_found'];
        }
        
        // Buscar dados do usuário
        $query = "SELECT u.*, g.nome as grupo_nome FROM usuarios u 
                  LEFT JOIN grupos g ON u.grupo_id = g.id 
                  WHERE u.username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar se está aprovado
            if ($user['is_approved'] != 1) {
                return ['success' => false, 'reason' => 'not_approved'];
            }
            
            // Verificar se está ativo
            if ($user['is_active'] != 1) {
                return ['success' => false, 'reason' => 'inactive'];
            }
            
            // Verificar se não está bloqueado
            if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
                return ['success' => false, 'reason' => 'blocked', 'blocked_until' => $user['bloqueado_ate']];
            }
            
            // Verificar senha
            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['grupo_id'] = $user['grupo_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['grupo_nome'] = $user['grupo_nome'] ?? 'Grupo Principal';
                $_SESSION['is_approved'] = $user['is_approved'];
                
                return ['success' => true];
            } else {
                return ['success' => false, 'reason' => 'wrong_password'];
            }
        }
        
        return ['success' => false, 'reason' => 'user_not_found'];
    }

    // Logout
    public function logout() {
        $this->startSession();
        session_destroy();
        return true;
    }

    // Verificar se está logado
    public function isLoggedIn() {
        $this->startSession();
        
        if (!isset($_SESSION['user_id']) || $_SESSION['is_approved'] != 1) {
            return false;
        }
        
        // Verificar se o usuário ainda está ativo e não foi bloqueado
        $this->usuario->id = $_SESSION['user_id'];
        $user_data = $this->usuario->readById();
        
        if (!$user_data) {
            $this->logout();
            return false;
        }
        
        // Verificar se está ativo
        if ($user_data['is_active'] != 1) {
            $this->logout();
            return false;
        }
        
        // Verificar se não está bloqueado
        if ($user_data['bloqueado_ate'] && strtotime($user_data['bloqueado_ate']) > time()) {
            $this->logout();
            return false;
        }
        
        return true;
    }

    // Verificar se é admin
    public function isAdmin() {
        $this->startSession();
        return $this->isLoggedIn() && $_SESSION['role'] == 'admin';
    }

    // Verificar se é user
    public function isUser() {
        $this->startSession();
        return $this->isLoggedIn() && $_SESSION['role'] == 'user';
    }

    // Verificar se é collaborator
    public function isCollaborator() {
        $this->startSession();
        return $this->isLoggedIn() && $_SESSION['role'] == 'collaborator';
    }

    // Obter usuário atual
    public function getCurrentUser() {
        $this->startSession();
        
        if(!$this->isLoggedIn()) {
            return null;
        }
        
        // Buscar dados atualizados do usuário
        $this->usuario->id = $_SESSION['user_id'];
        $user_data = $this->usuario->readById();
        
        if (!$user_data) {
            $this->logout();
            return null;
        }
        
        return [
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'grupo_id' => $user_data['grupo_id'],
            'role' => $user_data['role'],
            'grupo_nome' => $user_data['grupo_nome'] ?? 'Grupo Principal',
            'is_approved' => $user_data['is_approved'],
            'is_active' => $user_data['is_active'],
            'bloqueado_ate' => $user_data['bloqueado_ate']
        ];
    }

    // Obter ID do usuário atual
    public function getCurrentUserId() {
        $this->startSession();
        return $_SESSION['user_id'] ?? null;
    }

    // Obter ID do grupo atual
    public function getCurrentGroupId() {
        $this->startSession();
        return $_SESSION['grupo_id'] ?? null;
    }

    // Obter role do usuário atual
    public function getUserRole() {
        $this->startSession();
        return $_SESSION['role'] ?? null;
    }

    // Verificar permissão para acessar grupo
    public function canAccessGroup($grupo_id) {
        $this->startSession();
        
        if($this->isAdmin()) {
            return true; // Admin pode acessar qualquer grupo
        }
        
        return $_SESSION['grupo_id'] == $grupo_id;
    }

    // Middleware para proteger páginas
    public function requireLogin($redirect_to = 'login.php') {
        if(!$this->isLoggedIn()) {
            header('Location: ' . $redirect_to);
            exit;
        }
    }

    // Middleware para proteger páginas de admin
    public function requireAdmin($redirect_to = 'index.php') {
        $this->requireLogin();
        
        if(!$this->isAdmin()) {
            header('Location: ' . $redirect_to);
            exit;
        }
    }

    // Middleware para proteger páginas de user ou admin
    public function requireUserOrAdmin($redirect_to = 'index.php') {
        $this->requireLogin();
        
        if(!$this->isUser() && !$this->isAdmin()) {
            header('Location: ' . $redirect_to);
            exit;
        }
    }

    // Validar força da senha
    public function validatePassword($password) {
        $errors = [];
        
        if(strlen($password) < 6) {
            $errors[] = "A senha deve ter pelo menos 6 caracteres";
        }
        
        if(!preg_match('/[A-Z]/', $password)) {
            $errors[] = "A senha deve conter pelo menos uma letra maiúscula";
        }
        
        if(!preg_match('/[a-z]/', $password)) {
            $errors[] = "A senha deve conter pelo menos uma letra minúscula";
        }
        
        if(!preg_match('/[0-9]/', $password)) {
            $errors[] = "A senha deve conter pelo menos um número";
        }
        
        if(!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "A senha deve conter pelo menos um caractere especial";
        }
        
        return $errors;
    }

    // Gerar hash da senha
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // Verificar hash da senha
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>
