<?php
require_once 'config/database.php';

class Fornecedor {
    private $conn;
    private $table_name = "fornecedores";

    public $id;
    public $grupo_id;
    public $nome;
    public $cnpj;
    public $email;
    public $telefone;
    public $endereco;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo fornecedor
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, nome=:nome, cnpj=:cnpj, 
                      email=:email, telefone=:telefone, endereco=:endereco";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cnpj = htmlspecialchars(strip_tags($this->cnpj));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->endereco = htmlspecialchars(strip_tags($this->endereco));

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cnpj", $this->cnpj);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":endereco", $this->endereco);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Ler fornecedores
    public function read($filtro_grupo = null, $filtro_nome = null) {
        $query = "SELECT f.*, g.nome as grupo_nome FROM " . $this->table_name . " f 
                  LEFT JOIN grupos g ON f.grupo_id = g.id";

        $conditions = [];
        $params = [];

        if($filtro_grupo) {
            $conditions[] = "f.grupo_id = :grupo_id";
            $params[':grupo_id'] = $filtro_grupo;
        }

        if($filtro_nome) {
            $conditions[] = "f.nome LIKE :nome";
            $params[':nome'] = '%' . $filtro_nome . '%';
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY f.nome ASC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar fornecedor
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, cnpj=:cnpj, email=:email, 
                      telefone=:telefone, endereco=:endereco 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cnpj = htmlspecialchars(strip_tags($this->cnpj));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->endereco = htmlspecialchars(strip_tags($this->endereco));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cnpj", $this->cnpj);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":endereco", $this->endereco);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar fornecedor
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se fornecedor pode ser deletado
    public function canDelete() {
        $query = "SELECT COUNT(*) as count FROM compras WHERE fornecedor_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Obter estatísticas do fornecedor
    public function getStats() {
        $query = "SELECT 
                    COUNT(c.id) as total_compras,
                    SUM(c.valor_total) as valor_total_compras,
                    AVG(c.valor_total) as valor_medio_compras,
                    MIN(c.data_compra) as primeira_compra,
                    MAX(c.data_compra) as ultima_compra
                  FROM " . $this->table_name . " f
                  LEFT JOIN compras c ON f.id = c.fornecedor_id
                  WHERE f.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter histórico de compras
    public function getCompras($limit = 10) {
        $query = "SELECT c.*, cat.nome as categoria_nome, tp.nome as tipo_pagamento_nome
                  FROM compras c
                  LEFT JOIN categorias cat ON c.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON c.tipo_pagamento_id = tp.id
                  WHERE c.fornecedor_id = ?
                  ORDER BY c.data_compra DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar fornecedor por nome ou CNPJ
    public function search($termo, $grupo_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id 
                  AND (nome LIKE :termo OR cnpj LIKE :termo)
                  ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $termo = '%' . $termo . '%';
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":termo", $termo);
        $stmt->execute();
        
        return $stmt;
    }

    // Verificar se fornecedor já existe
    public function exists($nome, $grupo_id, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND nome = :nome";
        
        if($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":nome", $nome);
        
        if($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Formatar CNPJ
    public function formatCNPJ($cnpj) {
        if(empty($cnpj)) return '';
        
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if(strlen($cnpj) == 14) {
            return substr($cnpj, 0, 2) . '.' . 
                   substr($cnpj, 2, 3) . '.' . 
                   substr($cnpj, 5, 3) . '/' . 
                   substr($cnpj, 8, 4) . '-' . 
                   substr($cnpj, 12, 2);
        }
        
        return $cnpj;
    }

    // Formatar telefone
    public function formatTelefone($telefone) {
        if(empty($telefone)) return '';
        
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        
        if(strlen($telefone) == 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 5) . '-' . 
                   substr($telefone, 7, 4);
        } elseif(strlen($telefone) == 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 4) . '-' . 
                   substr($telefone, 6, 4);
        }
        
        return $telefone;
    }

    // Buscar fornecedor por CNPJ
    public function getByCNPJ($cnpj) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND cnpj = :cnpj";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":cnpj", $cnpj);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
