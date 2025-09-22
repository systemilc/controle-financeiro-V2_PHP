<?php
require_once 'config/database.php';
require_once 'config/timezone.php';

class Conta {
    private $conn;
    private $table_name = "contas";

    public $id;
    public $nome;
    public $grupo_id;
    public $saldo;
    public $icone;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar nova conta
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, grupo_id=:grupo_id, saldo=:saldo, icone=:icone";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":saldo", $this->saldo);
        $stmt->bindParam(":icone", $this->icone);

        if($stmt->execute()) {
            $conta_id = $this->conn->lastInsertId();
            
            // Se há saldo inicial, criar transação de saldo inicial
            if($this->saldo > 0) {
                $this->createSaldoInicial($conta_id);
            }
            
            return true;
        }
        return false;
    }

    // Criar transação de saldo inicial
    private function createSaldoInicial($conta_id) {
        // Usar o usuário logado (se disponível) ou buscar o primeiro usuário admin
        $usuario_id = null;
        
        // Se há um usuário logado na sessão, usar ele
        if(session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $usuario_id = $_SESSION['user_id'];
        } else {
            // Buscar primeiro usuário admin disponível
            $query_usuario = "SELECT id FROM usuarios WHERE role = 'admin' LIMIT 1";
            $stmt_usuario = $this->conn->prepare($query_usuario);
            $stmt_usuario->execute();
            $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
            
            if($usuario) {
                $usuario_id = $usuario['id'];
            } else {
                // Se não há admin, buscar qualquer usuário
                $query_usuario = "SELECT id FROM usuarios LIMIT 1";
                $stmt_usuario = $this->conn->prepare($query_usuario);
                $stmt_usuario->execute();
                $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
                
                if($usuario) {
                    $usuario_id = $usuario['id'];
                }
            }
        }
        
        if($usuario_id) {
            // Buscar categoria "Saldo Inicial" ou criar uma padrão (sem grupo)
            $query_categoria = "SELECT id FROM categorias WHERE nome = 'Saldo Inicial' LIMIT 1";
            $stmt_categoria = $this->conn->prepare($query_categoria);
            $stmt_categoria->execute();
            $categoria = $stmt_categoria->fetch(PDO::FETCH_ASSOC);
            
            if(!$categoria) {
                // Criar categoria "Saldo Inicial" se não existir (sem grupo)
                $query_create_categoria = "INSERT INTO categorias (nome, tipo, cor) 
                                          VALUES ('Saldo Inicial', 'receita', '#28a745')";
                $stmt_create_categoria = $this->conn->prepare($query_create_categoria);
                $stmt_create_categoria->execute();
                $categoria_id = $this->conn->lastInsertId();
            } else {
                $categoria_id = $categoria['id'];
            }
            
            // Criar transação de saldo inicial
            $query_transacao = "INSERT INTO transacoes 
                                (usuario_id, conta_id, categoria_id, descricao, valor, tipo, 
                                 is_confirmed, data_transacao, observacoes) 
                                VALUES (:usuario_id, :conta_id, :categoria_id, :descricao, :valor, 
                                        'receita', 1, :data_transacao, :observacoes)";
            
            $stmt_transacao = $this->conn->prepare($query_transacao);
            $descricao = "Saldo inicial da conta " . $this->nome;
            $observacoes = "Transação criada automaticamente ao cadastrar a conta com saldo inicial.";
            
            $data_atual = getCurrentDate();
            $stmt_transacao->bindParam(':usuario_id', $usuario_id);
            $stmt_transacao->bindParam(':conta_id', $conta_id);
            $stmt_transacao->bindParam(':categoria_id', $categoria_id);
            $stmt_transacao->bindParam(':descricao', $descricao);
            $stmt_transacao->bindParam(':valor', $this->saldo);
            $stmt_transacao->bindParam(':data_transacao', $data_atual);
            $stmt_transacao->bindParam(':observacoes', $observacoes);
            
            $stmt_transacao->execute();
        }
    }

    // Obter todas as contas
    public function getAll() {
        $query = "SELECT c.*, g.nome as grupo_nome FROM " . $this->table_name . " c 
                  LEFT JOIN grupos g ON c.grupo_id = g.id
                  WHERE c.grupo_id = :grupo_id
                  ORDER BY c.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ler contas
    public function read($filtro_grupo = null) {
        $query = "SELECT c.*, g.nome as grupo_nome FROM " . $this->table_name . " c 
                  LEFT JOIN grupos g ON c.grupo_id = g.id";

        if($filtro_grupo) {
            $query .= " WHERE c.grupo_id = :grupo_id";
        }

        $query .= " ORDER BY c.nome ASC";

        $stmt = $this->conn->prepare($query);
        
        if($filtro_grupo) {
            $stmt->bindParam(":grupo_id", $filtro_grupo);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar conta
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, saldo=:saldo, icone=:icone 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->icone = htmlspecialchars(strip_tags($this->icone));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":saldo", $this->saldo);
        $stmt->bindParam(":icone", $this->icone);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar conta
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se conta pode ser deletada
    public function canDelete() {
        // Verificar se é a última conta do grupo
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE grupo_id = (SELECT grupo_id FROM " . $this->table_name . " WHERE id = ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row['count'] <= 1) {
            return false; // Não pode deletar a última conta
        }

        // Verificar se tem transações associadas
        $query = "SELECT COUNT(*) as count FROM transacoes WHERE conta_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Atualizar saldo da conta
    public function updateSaldo() {
        $query = "UPDATE " . $this->table_name . " 
                  SET saldo = (
                      SELECT COALESCE(SUM(
                          CASE 
                              WHEN tipo = 'receita' THEN valor 
                              WHEN tipo = 'despesa' THEN -valor 
                              ELSE 0 
                          END
                      ), 0)
                      FROM transacoes 
                      WHERE conta_id = ? AND is_confirmed = 1
                  )
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter saldo atual da conta
    public function getSaldo() {
        $query = "SELECT saldo FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['saldo'] ?? 0;
    }

    // Transferir saldo entre contas
    public function transferir($conta_destino_id, $valor, $usuario_id) {
        try {
            $this->conn->beginTransaction();

            // Verificar se conta origem tem saldo suficiente
            $saldo_atual = $this->getSaldo();
            if($saldo_atual < $valor) {
                throw new Exception("Saldo insuficiente na conta origem");
            }

            // Obter nome da conta destino
            $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $conta_destino_id);
            $stmt->execute();
            $conta_destino = $stmt->fetch(PDO::FETCH_ASSOC);

            // Criar transação de saída na conta origem
            $query = "INSERT INTO transacoes 
                      (usuario_id, conta_id, descricao, valor, tipo, is_confirmed, 
                       data_transacao, data_confirmacao, is_transfer, conta_original_nome) 
                      VALUES (?, ?, ?, ?, 'despesa', 1, CURDATE(), CURDATE(), 1, ?)";
            $stmt = $this->conn->prepare($query);
            $descricao_saida = "Transferência para " . $conta_destino['nome'];
            $stmt->bindParam(1, $usuario_id);
            $stmt->bindParam(2, $this->id);
            $stmt->bindParam(3, $descricao_saida);
            $stmt->bindParam(4, $valor);
            $stmt->bindParam(5, $conta_destino['nome']);
            $stmt->execute();

            // Criar transação de entrada na conta destino
            $query = "INSERT INTO transacoes 
                      (usuario_id, conta_id, descricao, valor, tipo, is_confirmed, 
                       data_transacao, data_confirmacao, is_transfer, conta_original_nome) 
                      VALUES (?, ?, ?, ?, 'receita', 1, CURDATE(), CURDATE(), 1, ?)";
            $stmt = $this->conn->prepare($query);
            $descricao_entrada = "Transferência de " . $this->nome;
            $stmt->bindParam(1, $usuario_id);
            $stmt->bindParam(2, $conta_destino_id);
            $stmt->bindParam(3, $descricao_entrada);
            $stmt->bindParam(4, $valor);
            $stmt->bindParam(5, $this->nome);
            $stmt->execute();

            // Atualizar saldos das contas
            $this->updateSaldo();
            $conta_destino_obj = new Conta($this->conn);
            $conta_destino_obj->id = $conta_destino_id;
            $conta_destino_obj->updateSaldo();

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>
