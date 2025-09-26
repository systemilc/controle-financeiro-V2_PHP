<?php
require_once 'config/database.php';

class Produto {
    private $conn;
    private $table_name = "produtos";

    public $id;
    public $grupo_id;
    public $nome;
    public $codigo;
    public $quantidade;
    public $valor_total;
    public $preco_medio;
    public $data_ultima_compra;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar novo produto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, nome=:nome, codigo=:codigo, 
                      quantidade=:quantidade, valor_total=:valor_total, 
                      preco_medio=:preco_medio, data_ultima_compra=:data_ultima_compra";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":valor_total", $this->valor_total);
        $stmt->bindParam(":preco_medio", $this->preco_medio);
        $stmt->bindParam(":data_ultima_compra", $this->data_ultima_compra);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Ler produtos
    public function read($filtro_grupo = null, $filtro_nome = null, $filtro_codigo = null) {
        $query = "SELECT p.*, g.nome as grupo_nome FROM " . $this->table_name . " p 
                  LEFT JOIN grupos g ON p.grupo_id = g.id";

        $conditions = [];
        $params = [];

        if($filtro_grupo) {
            $conditions[] = "p.grupo_id = :grupo_id";
            $params[':grupo_id'] = $filtro_grupo;
        }

        if($filtro_nome) {
            $conditions[] = "p.nome LIKE :nome";
            $params[':nome'] = '%' . $filtro_nome . '%';
        }

        if($filtro_codigo) {
            $conditions[] = "p.codigo LIKE :codigo";
            $params[':codigo'] = '%' . $filtro_codigo . '%';
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY p.nome ASC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar produto
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, codigo=:codigo, quantidade=:quantidade, 
                      valor_total=:valor_total, preco_medio=:preco_medio, 
                      data_ultima_compra=:data_ultima_compra 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->codigo = htmlspecialchars(strip_tags($this->codigo));

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":quantidade", $this->quantidade);
        $stmt->bindParam(":valor_total", $this->valor_total);
        $stmt->bindParam(":preco_medio", $this->preco_medio);
        $stmt->bindParam(":data_ultima_compra", $this->data_ultima_compra);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar produto
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se produto pode ser deletado
    public function canDelete() {
        $query = "SELECT COUNT(*) as count FROM itens_compra WHERE produto_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Obter estatísticas do produto
    public function getStats() {
        // Estatísticas baseadas apenas em itens de compra (fonte principal)
        $query = "SELECT 
                    COUNT(ic.id) as total_compras,
                    COALESCE(SUM(ic.quantidade), 0) as quantidade_total_comprada,
                    COALESCE(SUM(ic.preco_total), 0) as valor_total_gasto,
                    COALESCE(AVG(ic.preco_unitario), 0) as preco_medio_historico,
                    MIN(c.data_compra) as primeira_compra,
                    MAX(c.data_compra) as ultima_compra,
                    COALESCE(MIN(ic.preco_unitario), 0) as preco_mais_barato,
                    COALESCE(MAX(ic.preco_unitario), 0) as preco_mais_caro
                  FROM " . $this->table_name . " p
                  LEFT JOIN itens_compra ic ON p.id = ic.produto_id
                  LEFT JOIN compras c ON ic.compra_id = c.id
                  WHERE p.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não há dados de itens de compra, buscar em transações de importação
        if (($stats['total_compras'] ?? 0) == 0) {
            // Buscar nome do produto para usar na busca
            $query_nome = "SELECT nome FROM " . $this->table_name . " WHERE id = ?";
            $stmt_nome = $this->conn->prepare($query_nome);
            $stmt_nome->bindParam(1, $this->id);
            $stmt_nome->execute();
            $produto_nome = $stmt_nome->fetch(PDO::FETCH_ASSOC);
            
            if ($produto_nome) {
                $query_transacoes = "SELECT 
                            COUNT(t.id) as total_compras,
                            COUNT(t.id) as quantidade_total_comprada,
                            COALESCE(SUM(t.valor), 0) as valor_total_gasto,
                            COALESCE(AVG(t.valor), 0) as preco_medio_historico,
                            MIN(t.data_transacao) as primeira_compra,
                            MAX(t.data_transacao) as ultima_compra,
                            COALESCE(MIN(t.valor), 0) as preco_mais_barato,
                            COALESCE(MAX(t.valor), 0) as preco_mais_caro
                          FROM transacoes t
                          WHERE t.tipo = 'despesa' 
                          AND t.descricao LIKE ?
                          AND t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = (SELECT grupo_id FROM produtos WHERE id = ?))";

                $stmt = $this->conn->prepare($query_transacoes);
                $nome_like = '%' . $produto_nome['nome'] . '%';
                $stmt->bindParam(1, $nome_like);
                $stmt->bindParam(2, $this->id);
                $stmt->execute();
                $stats_transacoes = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($stats_transacoes && ($stats_transacoes['total_compras'] ?? 0) > 0) {
                    $stats = $stats_transacoes;
                }
            }
        }
        
        // Garantir que todos os valores sejam numéricos válidos
        return [
            'total_compras' => (int)($stats['total_compras'] ?? 0),
            'quantidade_total_comprada' => (float)($stats['quantidade_total_comprada'] ?? 0),
            'valor_total_gasto' => (float)($stats['valor_total_gasto'] ?? 0),
            'preco_medio_historico' => (float)($stats['preco_medio_historico'] ?? 0),
            'primeira_compra' => $stats['primeira_compra'] ?? null,
            'ultima_compra' => $stats['ultima_compra'] ?? null,
            'preco_mais_barato' => (float)($stats['preco_mais_barato'] ?? 0),
            'preco_mais_caro' => (float)($stats['preco_mais_caro'] ?? 0)
        ];
    }

    // Obter histórico de compras
    public function getHistoricoCompras($limit = 10) {
        // Histórico baseado apenas em itens de compra (fonte principal)
        $query = "SELECT 
                    ic.quantidade,
                    ic.preco_unitario,
                    ic.preco_total,
                    c.data_compra,
                    c.numero_nota,
                    f.nome as fornecedor_nome,
                    'item_compra' as tipo_origem
                  FROM itens_compra ic
                  LEFT JOIN compras c ON ic.compra_id = c.id
                  LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
                  WHERE ic.produto_id = ?
                  ORDER BY c.data_compra DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar produto por nome ou código
    public function search($termo, $grupo_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id 
                  AND (nome LIKE :termo OR codigo LIKE :termo)
                  ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $termo = '%' . $termo . '%';
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":termo", $termo);
        $stmt->execute();
        
        return $stmt;
    }

    // Verificar se produto já existe
    public function exists($nome, $codigo, $grupo_id, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND nome = :nome AND codigo = :codigo";
        
        if($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":codigo", $codigo);
        
        if($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Atualizar estatísticas do produto
    public function updateStats() {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantidade = (
                      SELECT COALESCE(SUM(ic.quantidade), 0)
                      FROM itens_compra ic
                      WHERE ic.produto_id = ?
                  ),
                  valor_total = (
                      SELECT COALESCE(SUM(ic.preco_total), 0)
                      FROM itens_compra ic
                      WHERE ic.produto_id = ?
                  ),
                  preco_medio = (
                      SELECT COALESCE(AVG(ic.preco_unitario), 0)
                      FROM itens_compra ic
                      WHERE ic.produto_id = ?
                  ),
                  data_ultima_compra = (
                      SELECT MAX(c.data_compra)
                      FROM itens_compra ic
                      LEFT JOIN compras c ON ic.compra_id = c.id
                      WHERE ic.produto_id = ?
                  )
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->id);
        $stmt->bindParam(3, $this->id);
        $stmt->bindParam(4, $this->id);
        $stmt->bindParam(5, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter produtos com análise de preços
    public function getAnalisePrecos($grupo_id) {
        $query = "SELECT 
                    p.id,
                    p.nome,
                    p.codigo,
                    COUNT(ic.id) as total_compras,
                    SUM(ic.quantidade) as quantidade_total,
                    SUM(ic.preco_total) as valor_total_gasto,
                    AVG(ic.preco_unitario) as preco_medio,
                    MIN(ic.preco_unitario) as preco_mais_barato,
                    MAX(ic.preco_unitario) as preco_mais_caro,
                    MIN(c.data_compra) as primeira_compra,
                    MAX(c.data_compra) as ultima_compra
                  FROM " . $this->table_name . " p
                  LEFT JOIN itens_compra ic ON p.id = ic.produto_id
                  LEFT JOIN compras c ON ic.compra_id = c.id
                  WHERE p.grupo_id = ?
                  GROUP BY p.id
                  HAVING total_compras > 0
                  ORDER BY valor_total_gasto DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grupo_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Associar produtos
    public function associarProduto($produto_associado_id) {
        $query = "INSERT INTO associacoes_produtos 
                  (produto_id, produto_associado_id, grupo_id) 
                  VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $produto_associado_id);
        $stmt->bindParam(3, $this->grupo_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter produtos associados
    public function getProdutosAssociados() {
        $query = "SELECT p.* FROM associacoes_produtos ap
                  LEFT JOIN produtos p ON ap.produto_associado_id = p.id
                  WHERE ap.produto_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar produto por código
    public function getByCodigo($codigo) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND codigo = :codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":codigo", $codigo);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
