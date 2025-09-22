<?php
require_once 'config/database.php';

class TipoPagamento {
    private $conn;
    private $table_name = "tipos_pagamento";

    public $id;
    public $grupo_id;
    public $nome;
    public $is_income;
    public $is_expense;
    public $is_asset;
    public $is_active;
    public $icone;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo tipo de pagamento
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, nome=:nome, is_income=:is_income, 
                      is_expense=:is_expense, is_asset=:is_asset, is_active=:is_active, icone=:icone";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":is_income", $this->is_income);
        $stmt->bindParam(":is_expense", $this->is_expense);
        $stmt->bindParam(":is_asset", $this->is_asset);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":icone", $this->icone);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todos os tipos de pagamento
    public function getAll() {
        $query = "SELECT tp.*, g.nome as grupo_nome FROM " . $this->table_name . " tp 
                  LEFT JOIN grupos g ON tp.grupo_id = g.id
                  WHERE tp.grupo_id = :grupo_id
                  ORDER BY tp.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ler tipos de pagamento
    public function read($filtro_grupo = null, $filtro_ativo = null) {
        $query = "SELECT tp.*, g.nome as grupo_nome FROM " . $this->table_name . " tp 
                  LEFT JOIN grupos g ON tp.grupo_id = g.id";

        $conditions = [];
        $params = [];

        if($filtro_grupo) {
            $conditions[] = "tp.grupo_id = :grupo_id";
            $params[':grupo_id'] = $filtro_grupo;
        }

        if($filtro_ativo !== null) {
            $conditions[] = "tp.is_active = :is_active";
            $params[':is_active'] = $filtro_ativo;
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY tp.nome ASC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar tipo de pagamento
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, is_income=:is_income, is_expense=:is_expense, 
                      is_asset=:is_asset, is_active=:is_active, icone=:icone 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":is_income", $this->is_income);
        $stmt->bindParam(":is_expense", $this->is_expense);
        $stmt->bindParam(":is_asset", $this->is_asset);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":icone", $this->icone);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar tipo de pagamento
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se tipo pode ser deletado
    public function canDelete() {
        $query = "SELECT COUNT(*) as count FROM transacoes WHERE tipo_pagamento_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Obter tipos ativos para transações
    public function getActiveForTransactions($grupo_id, $tipo_transacao = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND is_active = 1";

        if($tipo_transacao == 'receita') {
            $query .= " AND is_income = 1";
        } elseif($tipo_transacao == 'despesa') {
            $query .= " AND is_expense = 1";
        }

        $query .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Ativar/Desativar tipo
    public function toggleStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = NOT is_active 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter estatísticas de uso
    public function getUsageStats() {
        $query = "SELECT 
                    COUNT(t.id) as total_transacoes,
                    SUM(CASE WHEN t.tipo = 'receita' THEN t.valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN t.tipo = 'despesa' THEN t.valor ELSE 0 END) as total_despesas
                  FROM " . $this->table_name . " tp
                  LEFT JOIN transacoes t ON tp.id = t.tipo_pagamento_id
                  WHERE tp.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
