<?php
require_once 'config/database.php';

class UsuarioConvidado {
    private $conn;
    private $table_name = "usuarios_convidados";

    public $id;
    public $grupo_id;
    public $usuario_id;
    public $convite_id;
    public $data_aceite;
    public $is_ativo;
    public $permissoes;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Adicionar usuário convidado
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, usuario_id=:usuario_id, 
                      convite_id=:convite_id, permissoes=:permissoes";

        $stmt = $this->conn->prepare($query);

        $this->permissoes = json_encode($this->permissoes);

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":convite_id", $this->convite_id);
        $stmt->bindParam(":permissoes", $this->permissoes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Obter usuários convidados do grupo
    public function getByGrupo($grupo_id) {
        $query = "SELECT uc.*, u.username, u.email, u.created_at as usuario_criado_em,
                         c.email_convidado, c.data_envio as convite_enviado_em
                  FROM " . $this->table_name . " uc
                  LEFT JOIN usuarios u ON uc.usuario_id = u.id
                  LEFT JOIN convites c ON uc.convite_id = c.id
                  WHERE uc.grupo_id = :grupo_id AND uc.is_ativo = 1
                  ORDER BY uc.data_aceite DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar se usuário tem acesso ao grupo
    public function usuarioTemAcesso($usuario_id, $grupo_id) {
        $query = "SELECT uc.id FROM " . $this->table_name . " uc
                  WHERE uc.usuario_id = :usuario_id AND uc.grupo_id = :grupo_id 
                  AND uc.is_ativo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Obter grupos que o usuário tem acesso
    public function getGruposByUsuario($usuario_id) {
        $query = "SELECT uc.*, g.nome as grupo_nome, g.descricao as grupo_descricao
                  FROM " . $this->table_name . " uc
                  LEFT JOIN grupos g ON uc.grupo_id = g.id
                  WHERE uc.usuario_id = :usuario_id AND uc.is_ativo = 1
                  ORDER BY uc.data_aceite DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Remover acesso do usuário ao grupo
    public function removerAcesso($usuario_id, $grupo_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_ativo = 0
                  WHERE usuario_id = :usuario_id AND grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":grupo_id", $grupo_id);

        return $stmt->execute();
    }

    // Contar usuários convidados ativos do grupo
    public function countAtivosByGrupo($grupo_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND is_ativo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obter estatísticas de usuários convidados
    public function getEstatisticasByGrupo($grupo_id) {
        $query = "SELECT 
                    COUNT(*) as total_usuarios_convidados,
                    COUNT(CASE WHEN is_ativo = 1 THEN 1 END) as usuarios_ativos,
                    COUNT(CASE WHEN is_ativo = 0 THEN 1 END) as usuarios_inativos
                  FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verificar se usuário já foi convidado para o grupo
    public function usuarioJaConvidado($usuario_id, $grupo_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id AND grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Atualizar permissões do usuário
    public function atualizarPermissoes($usuario_id, $grupo_id, $permissoes) {
        $query = "UPDATE " . $this->table_name . " 
                  SET permissoes = :permissoes
                  WHERE usuario_id = :usuario_id AND grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $permissoes_json = json_encode($permissoes);
        $stmt->bindParam(":permissoes", $permissoes_json);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":grupo_id", $grupo_id);

        return $stmt->execute();
    }

    // Obter permissões do usuário no grupo
    public function getPermissoes($usuario_id, $grupo_id) {
        $query = "SELECT permissoes FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id AND grupo_id = :grupo_id 
                  AND is_ativo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? json_decode($result['permissoes'], true) : null;
    }
}
?>
