<?php
require_once 'config/database.php';

class Compra {
    private $conn;
    private $table_name = "compras";

    public $id;
    public $fornecedor_id;
    public $grupo_id;
    public $data_compra;
    public $numero_nota;
    public $valor_total;
    public $conta_id;
    public $tipo_pagamento_id;
    public $categoria_id;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar nova compra
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET fornecedor_id=:fornecedor_id, grupo_id=:grupo_id, 
                      data_compra=:data_compra, numero_nota=:numero_nota, 
                      valor_total=:valor_total, conta_id=:conta_id, 
                      tipo_pagamento_id=:tipo_pagamento_id, categoria_id=:categoria_id";

        $stmt = $this->conn->prepare($query);

        $this->numero_nota = htmlspecialchars(strip_tags($this->numero_nota));

        $stmt->bindParam(":fornecedor_id", $this->fornecedor_id);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":data_compra", $this->data_compra);
        $stmt->bindParam(":numero_nota", $this->numero_nota);
        $stmt->bindParam(":valor_total", $this->valor_total);
        $stmt->bindParam(":conta_id", $this->conta_id);
        $stmt->bindParam(":tipo_pagamento_id", $this->tipo_pagamento_id);
        $stmt->bindParam(":categoria_id", $this->categoria_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todas as compras
    public function getAll($grupo_id, $status = null) {
        $query = "SELECT c.*, f.nome as fornecedor_nome, f.cnpj as fornecedor_cnpj
                  FROM " . $this->table_name . " c
                  LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
                  WHERE c.grupo_id = :grupo_id";

        if($status) {
            $query .= " AND c.status = :status";
        }

        $query .= " ORDER BY c.data_compra DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter compra por ID
    public function getById($id) {
        $query = "SELECT c.*, f.nome as fornecedor_nome, f.cnpj as fornecedor_cnpj
                  FROM " . $this->table_name . " c
                  LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
                  WHERE c.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualizar compra
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET fornecedor_id=:fornecedor_id,
                      data_compra=:data_compra,
                      observacoes=:observacoes,
                      status=:status
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        $stmt->bindParam(":fornecedor_id", $this->fornecedor_id);
        $stmt->bindParam(":data_compra", $this->data_compra);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar compra
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter estatísticas das compras
    public function getEstatisticas($grupo_id) {
        $query = "SELECT 
                    COUNT(*) as total_compras,
                    COUNT(CASE WHEN status = 'confirmada' THEN 1 END) as compras_confirmadas,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as compras_pendentes,
                    COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as compras_canceladas
                  FROM " . $this->table_name . "
                  WHERE grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
