<?php
require_once 'config/database.php';

class Grupo {
    private $conn;
    private $table_name = "grupos";

    public $id;
    public $nome;
    public $descricao;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo grupo
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, descricao=:descricao";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":descricao", $this->descricao);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Ler grupos
    public function read() {
        $query = "SELECT g.*, 
                         COUNT(u.id) as total_usuarios,
                         COUNT(c.id) as total_contas
                  FROM " . $this->table_name . " g 
                  LEFT JOIN usuarios u ON g.id = u.grupo_id
                  LEFT JOIN contas c ON g.id = c.grupo_id
                  GROUP BY g.id
                  ORDER BY g.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obter todos os grupos
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Atualizar grupo
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, descricao=:descricao
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar grupo
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se grupo pode ser deletado
    public function canDelete() {
        $query = "SELECT COUNT(*) as count FROM usuarios WHERE grupo_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Obter estatísticas do grupo
    public function getStats() {
        $query = "SELECT 
                    COUNT(DISTINCT u.id) as total_usuarios,
                    COUNT(DISTINCT c.id) as total_contas,
                    COUNT(DISTINCT cat.id) as total_categorias,
                    COUNT(DISTINCT tp.id) as total_tipos_pagamento,
                    COUNT(DISTINCT f.id) as total_fornecedores,
                    COUNT(DISTINCT p.id) as total_produtos,
                    COUNT(DISTINCT t.id) as total_transacoes
                  FROM " . $this->table_name . " g
                  LEFT JOIN usuarios u ON g.id = u.grupo_id
                  LEFT JOIN contas c ON g.id = c.grupo_id
                  LEFT JOIN categorias cat ON g.id = cat.grupo_id
                  LEFT JOIN tipos_pagamento tp ON g.id = tp.grupo_id
                  LEFT JOIN fornecedores f ON g.id = f.grupo_id
                  LEFT JOIN produtos p ON g.id = p.grupo_id
                  LEFT JOIN transacoes t ON g.id = (SELECT grupo_id FROM usuarios WHERE id = t.usuario_id)
                  WHERE g.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter estatísticas gerais dos grupos
    public function getEstatisticas() {
        $query = "SELECT 
                    COUNT(DISTINCT g.id) as total_grupos,
                    COUNT(DISTINCT u.id) as total_usuarios,
                    COUNT(DISTINCT CASE WHEN u.is_active = 1 THEN u.id END) as usuarios_ativos
                  FROM " . $this->table_name . " g
                  LEFT JOIN usuarios u ON g.id = u.grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter todos os grupos com informações detalhadas
    public function getAllWithDetails() {
        $query = "SELECT g.*, 
                         COUNT(DISTINCT u.id) as total_usuarios,
                         COUNT(DISTINCT t.id) as total_transacoes,
                         COUNT(DISTINCT c.id) as total_contas
                  FROM " . $this->table_name . " g
                  LEFT JOIN usuarios u ON g.id = u.grupo_id
                  LEFT JOIN transacoes t ON g.id = (SELECT grupo_id FROM usuarios WHERE id = t.usuario_id)
                  LEFT JOIN contas c ON g.id = c.grupo_id
                  GROUP BY g.id
                  ORDER BY g.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>