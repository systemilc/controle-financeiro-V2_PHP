<?php
require_once 'config/database.php';

class ItemCompra {
    private $conn;
    private $table_name = "itens_compra";

    public $id;
    public $compra_id;
    public $produto_id;
    public $quantidade;
    public $preco_unitario;
    public $preco_total;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo item de compra
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET compra_id=:compra_id, produto_id=:produto_id, 
                      quantidade=:quantidade, preco_unitario=:preco_unitario, preco_total=:preco_total";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":compra_id", $this->compra_id);
        $stmt->bindParam(":produto_id", $this->produto_id);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":preco_unitario", $this->preco_unitario);
        $stmt->bindParam(":preco_total", $this->preco_total);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todos os itens de uma compra
    public function getByCompraId($compra_id) {
        $query = "SELECT ic.*, p.nome as produto_nome, p.codigo as produto_codigo
                  FROM " . $this->table_name . " ic
                  LEFT JOIN produtos p ON ic.produto_id = p.id
                  WHERE ic.compra_id = :compra_id
                  ORDER BY p.nome";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":compra_id", $compra_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter item por ID
    public function getById($id) {
        $query = "SELECT ic.*, p.nome as produto_nome, p.codigo as produto_codigo
                  FROM " . $this->table_name . " ic
                  LEFT JOIN produtos p ON ic.produto_id = p.id
                  WHERE ic.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualizar item de compra
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantidade=:quantidade,
                      preco_unitario=:preco_unitario,
                      valor_total=:valor_total
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":preco_unitario", $this->preco_unitario);
        $stmt->bindParam(":valor_total", $this->valor_total);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar item de compra
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter estatísticas dos itens
    public function getEstatisticas($grupo_id) {
        $query = "SELECT 
                    COUNT(*) as total_itens,
                    SUM(quantidade) as quantidade_total,
                    SUM(valor_total) as valor_total,
                    AVG(preco_unitario) as preco_medio
                  FROM " . $this->table_name . " ic
                  LEFT JOIN compras c ON ic.compra_id = c.id
                  WHERE c.grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
