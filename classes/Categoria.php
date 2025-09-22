<?php
require_once 'config/database.php';

class Categoria {
    private $conn;
    private $table_name = "categorias";

    public $id;
    public $nome;
    public $tipo;
    public $cor;
    public $icone;
    public $grupo_id;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar nova categoria
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, nome=:nome, tipo=:tipo, cor=:cor, icone=:icone";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":cor", $this->cor);
        $stmt->bindParam(":icone", $this->icone);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todas as categorias
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id 
                  ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ler categorias
    public function read($filtro_tipo = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE grupo_id = :grupo_id";
        
        if($filtro_tipo) {
            $query .= " AND tipo = :tipo";
        }
        
        $query .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        
        if($filtro_tipo) {
            $stmt->bindParam(":tipo", $filtro_tipo);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar categoria
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, tipo=:tipo, cor=:cor, icone=:icone 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":cor", $this->cor);
        $stmt->bindParam(":icone", $this->icone);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar categoria
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se categoria pode ser deletada (não tem transações associadas)
    public function canDelete() {
        $query = "SELECT COUNT(*) as count FROM transacoes WHERE categoria_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }
}
?>
