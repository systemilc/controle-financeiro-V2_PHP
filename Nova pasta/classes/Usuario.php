<?php
require_once 'config/database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $grupo_id;
    public $role;
    public $is_approved;
    public $whatsapp;
    public $instagram;
    public $email;
    public $consent_lgpd;
    public $is_active;
    public $data_ultimo_acesso;
    public $tentativas_login;
    public $bloqueado_ate;
    public $avatar;
    public $endereco;
    public $cidade;
    public $estado;
    public $cep;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo usuário
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, password=:password, grupo_id=:grupo_id, 
                      role=:role, is_approved=:is_approved, is_active=:is_active,
                      whatsapp=:whatsapp, instagram=:instagram, 
                      email=:email, consent_lgpd=:consent_lgpd";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->whatsapp = htmlspecialchars(strip_tags($this->whatsapp));
        $this->instagram = htmlspecialchars(strip_tags($this->instagram));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_approved", $this->is_approved);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":whatsapp", $this->whatsapp);
        $stmt->bindParam(":instagram", $this->instagram);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":consent_lgpd", $this->consent_lgpd);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Ler usuários
    public function read($filtro_grupo = null, $filtro_role = null, $filtro_approved = null) {
        $query = "SELECT u.*, g.nome as grupo_nome FROM " . $this->table_name . " u 
                  LEFT JOIN grupos g ON u.grupo_id = g.id";

        $conditions = [];
        $params = [];

        if($filtro_grupo) {
            $conditions[] = "u.grupo_id = :grupo_id";
            $params[':grupo_id'] = $filtro_grupo;
        }

        if($filtro_role) {
            $conditions[] = "u.role = :role";
            $params[':role'] = $filtro_role;
        }

        if($filtro_approved !== null) {
            $conditions[] = "u.is_approved = :is_approved";
            $params[':is_approved'] = $filtro_approved;
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar usuário
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, grupo_id=:grupo_id, role=:role, 
                      is_approved=:is_approved, whatsapp=:whatsapp, 
                      instagram=:instagram, email=:email, consent_lgpd=:consent_lgpd 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->whatsapp = htmlspecialchars(strip_tags($this->whatsapp));
        $this->instagram = htmlspecialchars(strip_tags($this->instagram));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_approved", $this->is_approved);
        $stmt->bindParam(":whatsapp", $this->whatsapp);
        $stmt->bindParam(":instagram", $this->instagram);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":consent_lgpd", $this->consent_lgpd);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Atualizar senha
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password=:password WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar usuário
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login
    public function login($username, $password) {
        $query = "SELECT u.*, g.nome as grupo_nome FROM " . $this->table_name . " u 
                  LEFT JOIN grupos g ON u.grupo_id = g.id 
                  WHERE u.username = :username AND u.is_approved = 1 AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password'])) {
                // Verificar se o usuário está bloqueado
                if($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
                    return false; // Usuário bloqueado
                }
                return $user;
            }
        }
        return false;
    }

    // Verificar se username existe
    public function usernameExists($username = null) {
        $username = $username ?: $this->username;
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Aprovar usuário
    public function approve() {
        $query = "UPDATE " . $this->table_name . " SET is_approved = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Rejeitar usuário
    public function reject() {
        $query = "UPDATE " . $this->table_name . " SET is_approved = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todos os usuários (admin)
    public function getAll() {
        $query = "SELECT u.*, g.nome as grupo_nome FROM " . $this->table_name . " u
                  LEFT JOIN grupos g ON u.grupo_id = g.id
                  ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter usuários por grupo
    public function getByGrupo($grupo_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Aprovar usuário
    public function aprovar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_approved = 1 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Desaprovar usuário
    public function desaprovar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_approved = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Ativar usuário
    public function ativar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 1, bloqueado_ate = NULL, tentativas_login = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Desativar usuário
    public function desativar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Bloquear usuário
    public function bloquear($minutos = 30) {
        $bloqueado_ate = date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"));
        
        $query = "UPDATE " . $this->table_name . " 
                  SET bloqueado_ate = :bloqueado_ate 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":bloqueado_ate", $bloqueado_ate);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Desbloquear usuário
    public function desbloquear() {
        $query = "UPDATE " . $this->table_name . " 
                  SET bloqueado_ate = NULL, tentativas_login = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Incrementar tentativas de login
    public function incrementarTentativasLogin() {
        $query = "UPDATE " . $this->table_name . " 
                  SET tentativas_login = tentativas_login + 1 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Atualizar último acesso
    public function atualizarUltimoAcesso() {
        $query = "UPDATE " . $this->table_name . " 
                  SET data_ultimo_acesso = NOW(), tentativas_login = 0 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se usuário está bloqueado
    public function isBloqueado() {
        $query = "SELECT bloqueado_ate FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row['bloqueado_ate'] && strtotime($row['bloqueado_ate']) > time()) {
                return true;
            }
        }
        return false;
    }

    // Obter estatísticas de usuários
    public function getEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as usuarios_ativos,
                    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as usuarios_aprovados,
                    SUM(CASE WHEN bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW() THEN 1 ELSE 0 END) as usuarios_bloqueados,
                    SUM(CASE WHEN data_ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as usuarios_ativos_30dias
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter usuários pendentes de aprovação
    public function getPendentesAprovacao() {
        $query = "SELECT u.*, g.nome as grupo_nome FROM " . $this->table_name . " u
                  LEFT JOIN grupos g ON u.grupo_id = g.id
                  WHERE u.is_approved = 0 AND u.is_active = 1
                  ORDER BY u.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Atualizar avatar
    public function updateAvatar() {
        $query = "UPDATE " . $this->table_name . " SET avatar=:avatar WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar senha
    public function verifyPassword($password) {
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return password_verify($password, $user['password']);
        }
        return false;
    }

    // Buscar usuário por ID (retorna array)
    public function readById() {
        $query = "SELECT u.*, g.nome as grupo_nome FROM " . $this->table_name . " u 
                  LEFT JOIN grupos g ON u.grupo_id = g.id 
                  WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
