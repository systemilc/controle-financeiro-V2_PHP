<?php
require_once 'config/database.php';
require_once 'config/timezone.php';

class SugestaoCompra {
    private $conn;
    private $table_name = "sugestoes_compra";

    public $id;
    public $produto_id;
    public $grupo_id;
    public $quantidade_sugerida;
    public $data_ultima_compra;
    public $data_ultimo_consumo;
    public $dias_consumo;
    public $consumo_diario_medio;
    public $estoque_atual;
    public $status; // 'ativa', 'comprada', 'cancelada'
    public $prioridade; // 'baixa', 'media', 'alta', 'critica'
    public $observacoes;
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

    // Criar nova sugestão
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET produto_id=:produto_id, grupo_id=:grupo_id, 
                      quantidade_sugerida=:quantidade_sugerida,
                      data_ultima_compra=:data_ultima_compra,
                      data_ultimo_consumo=:data_ultimo_consumo,
                      dias_consumo=:dias_consumo,
                      consumo_diario_medio=:consumo_diario_medio,
                      estoque_atual=:estoque_atual,
                      status=:status,
                      prioridade=:prioridade,
                      observacoes=:observacoes";

        $stmt = $this->conn->prepare($query);

        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        $stmt->bindParam(":produto_id", $this->produto_id);
        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":quantidade_sugerida", $this->quantidade_sugerida);
        $stmt->bindParam(":data_ultima_compra", $this->data_ultima_compra);
        $stmt->bindParam(":data_ultimo_consumo", $this->data_ultimo_consumo);
        $stmt->bindParam(":dias_consumo", $this->dias_consumo);
        $stmt->bindParam(":consumo_diario_medio", $this->consumo_diario_medio);
        $stmt->bindParam(":estoque_atual", $this->estoque_atual);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":prioridade", $this->prioridade);
        $stmt->bindParam(":observacoes", $this->observacoes);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todas as sugestões
    public function getAll($grupo_id, $status = null) {
        $query = "SELECT sc.*, p.nome as produto_nome, p.codigo as produto_codigo,
                         f.nome as fornecedor_nome, f.cnpj as fornecedor_cnpj
                  FROM " . $this->table_name . " sc
                  LEFT JOIN produtos p ON sc.produto_id = p.id
                  LEFT JOIN fornecedores f ON p.id IN (
                      SELECT DISTINCT ic.produto_id 
                      FROM itens_compra ic 
                      LEFT JOIN compras c ON ic.compra_id = c.id 
                      WHERE c.fornecedor_id = f.id
                  )
                  WHERE sc.grupo_id = :grupo_id";

        if($status) {
            $query .= " AND sc.status = :status";
        }

        $query .= " ORDER BY sc.prioridade DESC, sc.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter sugestão por ID
    public function getById($id) {
        $query = "SELECT sc.*, p.nome as produto_nome, p.codigo as produto_codigo
                  FROM " . $this->table_name . " sc
                  LEFT JOIN produtos p ON sc.produto_id = p.id
                  WHERE sc.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualizar sugestão
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantidade_sugerida=:quantidade_sugerida,
                      status=:status,
                      prioridade=:prioridade,
                      observacoes=:observacoes
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        $stmt->bindParam(":quantidade_sugerida", $this->quantidade_sugerida);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":prioridade", $this->prioridade);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar sugestão
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Marcar produto como acabado e gerar sugestão
    public function marcarProdutoAcabado($produto_id, $grupo_id) {
        try {
            $this->conn->beginTransaction();

            // Obter dados do produto
            $produto = $this->getProdutoData($produto_id);
            if (!$produto) {
                throw new Exception("Produto não encontrado");
            }

            // Verificar se já existe uma sugestão ativa para este produto
            $query_existe = "SELECT id FROM " . $this->table_name . " 
                           WHERE produto_id = :produto_id AND grupo_id = :grupo_id AND status = 'ativa'";
            $stmt_existe = $this->conn->prepare($query_existe);
            $stmt_existe->bindParam(":produto_id", $produto_id);
            $stmt_existe->bindParam(":grupo_id", $grupo_id);
            $stmt_existe->execute();
            
            if ($stmt_existe->rowCount() > 0) {
                throw new Exception("Já existe uma sugestão ativa para este produto");
            }

            // Calcular consumo histórico
            $consumo_data = $this->calcularConsumoHistorico($produto_id, $grupo_id);
            
            // Se não conseguiu calcular, usar valores padrão
            if (!$consumo_data) {
                $consumo_data = [
                    'quantidade_sugerida' => 1,
                    'data_ultima_compra' => date('Y-m-d', strtotime('-30 days')),
                    'dias_consumo' => 30,
                    'consumo_diario_medio' => 0.033 // 1 unidade em 30 dias
                ];
            }

            // Gerar sugestão
            $this->produto_id = $produto_id;
            $this->grupo_id = $grupo_id;
            $this->quantidade_sugerida = $consumo_data['quantidade_sugerida'];
            $this->data_ultima_compra = $consumo_data['data_ultima_compra'];
            $this->data_ultimo_consumo = getCurrentDate();
            $this->dias_consumo = $consumo_data['dias_consumo'];
            $this->consumo_diario_medio = $consumo_data['consumo_diario_medio'];
            $this->estoque_atual = 0; // Produto acabou
            $this->status = 'ativa';
            $this->prioridade = $this->calcularPrioridade($consumo_data);
            $this->observacoes = "Produto marcado como acabado em " . getCurrentDateFormatted() . 
                                ". Consumo calculado: " . number_format($this->consumo_diario_medio, 3) . " unidades/dia";

            if ($this->create()) {
                // Atualizar status do produto
                $query_update_produto = "UPDATE produtos SET estoque_zerado = 1, data_estoque_zerado = NOW() WHERE id = :produto_id";
                $stmt_update = $this->conn->prepare($query_update_produto);
                $stmt_update->bindParam(":produto_id", $produto_id);
                $stmt_update->execute();

                $this->conn->commit();
                return true;
            } else {
                throw new Exception("Erro ao criar sugestão");
            }

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Calcular consumo histórico do produto
    private function calcularConsumoHistorico($produto_id, $grupo_id) {
        // Primeiro, buscar dados do produto
        $produto_data = $this->getProdutoData($produto_id);
        if (!$produto_data) {
            return null;
        }

        $produto_nome = $produto_data['nome'];
        
        // Buscar compras via itens_compra (sistema de compras)
        $query_compras = "SELECT 
                    ic.quantidade,
                    ic.preco_unitario,
                    c.data_compra,
                    DATEDIFF(CURDATE(), c.data_compra) as dias_desde_compra
                  FROM itens_compra ic
                  LEFT JOIN compras c ON ic.compra_id = c.id
                  WHERE ic.produto_id = :produto_id
                  AND c.grupo_id = :grupo_id
                  AND c.data_compra >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  ORDER BY c.data_compra DESC";

        $stmt = $this->conn->prepare($query_compras);
        $stmt->bindParam(":produto_id", $produto_id);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Se não há compras via itens_compra, buscar em transações de importação
        if (empty($compras)) {
            $query_transacoes = "SELECT 
                        t.valor as quantidade,
                        t.valor as preco_unitario,
                        t.data_transacao as data_compra,
                        DATEDIFF(CURDATE(), t.data_transacao) as dias_desde_compra
                      FROM transacoes t
                      WHERE t.tipo = 'despesa'
                      AND t.descricao LIKE :produto_nome
                      AND t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                      AND t.data_transacao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      ORDER BY t.data_transacao DESC";

            $stmt = $this->conn->prepare($query_transacoes);
            $produto_nome_like = '%' . $produto_nome . '%';
            $stmt->bindParam(":produto_nome", $produto_nome_like);
            $stmt->bindParam(":grupo_id", $grupo_id);
            $stmt->execute();
            
            $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Se ainda não há dados, usar dados do próprio produto ou valores padrão
        if (empty($compras)) {
            // Usar dados do produto como base
            $quantidade_total = $produto_data['quantidade'] > 0 ? $produto_data['quantidade'] : 1;
            $data_ultima_compra = $produto_data['data_ultima_compra'] ?: date('Y-m-d', strtotime('-30 days'));
            $dias_consumo = max(1, (strtotime('now') - strtotime($data_ultima_compra)) / (60 * 60 * 24));
            
            $consumo_diario_medio = $quantidade_total / $dias_consumo;
            $quantidade_sugerida = max(1, ceil($consumo_diario_medio * 30));

            return [
                'quantidade_sugerida' => $quantidade_sugerida,
                'data_ultima_compra' => $data_ultima_compra,
                'dias_consumo' => (int)$dias_consumo,
                'consumo_diario_medio' => $consumo_diario_medio
            ];
        }

        // Calcular consumo diário médio
        $total_quantidade = 0;
        $total_dias = 0;
        $data_ultima_compra = $compras[0]['data_compra'];

        foreach ($compras as $compra) {
            $total_quantidade += $compra['quantidade'];
            $total_dias += $compra['dias_desde_compra'];
        }

        $consumo_diario_medio = $total_dias > 0 ? $total_quantidade / $total_dias : 0;
        
        // Sugerir quantidade para 30 dias (mínimo 1)
        $quantidade_sugerida = max(1, ceil($consumo_diario_medio * 30));

        return [
            'quantidade_sugerida' => $quantidade_sugerida,
            'data_ultima_compra' => $data_ultima_compra,
            'dias_consumo' => $total_dias,
            'consumo_diario_medio' => $consumo_diario_medio
        ];
    }

    // Calcular prioridade da sugestão
    private function calcularPrioridade($consumo_data) {
        $consumo_diario = $consumo_data['consumo_diario_medio'];
        
        if ($consumo_diario >= 1) {
            return 'critica'; // Consumo alto
        } elseif ($consumo_diario >= 0.5) {
            return 'alta'; // Consumo médio-alto
        } elseif ($consumo_diario >= 0.1) {
            return 'media'; // Consumo médio
        } else {
            return 'baixa'; // Consumo baixo
        }
    }

    // Obter dados do produto
    private function getProdutoData($produto_id) {
        $query = "SELECT * FROM produtos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $produto_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Marcar sugestão como comprada
    public function marcarComoComprada() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'comprada', updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter estatísticas das sugestões
    public function getEstatisticas($grupo_id) {
        $query = "SELECT 
                    COUNT(*) as total_sugestoes,
                    COUNT(CASE WHEN status = 'ativa' THEN 1 END) as sugestoes_ativas,
                    COUNT(CASE WHEN status = 'comprada' THEN 1 END) as sugestoes_compradas,
                    COUNT(CASE WHEN prioridade = 'critica' THEN 1 END) as prioridade_critica,
                    COUNT(CASE WHEN prioridade = 'alta' THEN 1 END) as prioridade_alta,
                    AVG(quantidade_sugerida) as quantidade_media_sugerida
                  FROM " . $this->table_name . "
                  WHERE grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter sugestões por prioridade
    public function getByPrioridade($grupo_id, $prioridade) {
        $query = "SELECT sc.*, p.nome as produto_nome, p.codigo as produto_codigo
                  FROM " . $this->table_name . " sc
                  LEFT JOIN produtos p ON sc.produto_id = p.id
                  WHERE sc.grupo_id = :grupo_id AND sc.prioridade = :prioridade
                  ORDER BY sc.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":prioridade", $prioridade);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar sugestões por produto
    public function searchByProduto($grupo_id, $termo) {
        $query = "SELECT sc.*, p.nome as produto_nome, p.codigo as produto_codigo
                  FROM " . $this->table_name . " sc
                  LEFT JOIN produtos p ON sc.produto_id = p.id
                  WHERE sc.grupo_id = :grupo_id 
                  AND (p.nome LIKE :termo OR p.codigo LIKE :termo)
                  ORDER BY sc.prioridade DESC, sc.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $termo = '%' . $termo . '%';
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->bindParam(":termo", $termo);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Remover sugestões de produtos comprados
    public function removerSugestoesCompradas($produto_ids, $grupo_id) {
        if (empty($produto_ids)) {
            return true;
        }

        $placeholders = str_repeat('?,', count($produto_ids) - 1) . '?';
        
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'comprada', 
                      observacoes = CONCAT(IFNULL(observacoes, ''), ' - Removido automaticamente por compra em ', NOW())
                  WHERE produto_id IN ($placeholders) 
                  AND grupo_id = ? 
                  AND status = 'ativa'";

        $stmt = $this->conn->prepare($query);
        
        $params = array_merge($produto_ids, [$grupo_id]);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }

    // Verificar se produto tem sugestão ativa
    public function temSugestaoAtiva($produto_id, $grupo_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE produto_id = :produto_id 
                  AND grupo_id = :grupo_id 
                  AND status = 'ativa'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":produto_id", $produto_id);
        $stmt->bindParam(":grupo_id", $grupo_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    // Remover sugestão por produto e código
    public function removerSugestaoPorProduto($produto_nome, $produto_codigo, $grupo_id) {
        // Primeiro, buscar produtos que correspondem ao nome ou código
        $query_produtos = "SELECT id FROM produtos 
                          WHERE grupo_id = :grupo_id 
                          AND (nome LIKE :produto_nome OR codigo = :produto_codigo)";
        
        $stmt_produtos = $this->conn->prepare($query_produtos);
        $produto_nome_like = '%' . $produto_nome . '%';
        $stmt_produtos->bindParam(":grupo_id", $grupo_id);
        $stmt_produtos->bindParam(":produto_nome", $produto_nome_like);
        $stmt_produtos->bindParam(":produto_codigo", $produto_codigo);
        $stmt_produtos->execute();
        
        $produtos_ids = $stmt_produtos->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($produtos_ids)) {
            return false;
        }
        
        // Remover sugestões para esses produtos
        $placeholders = str_repeat('?,', count($produtos_ids) - 1) . '?';
        
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'comprada', 
                      observacoes = CONCAT(IFNULL(observacoes, ''), ' - Removido automaticamente por importação em ', NOW())
                  WHERE grupo_id = ? 
                  AND status = 'ativa'
                  AND produto_id IN ($placeholders)";

        $stmt = $this->conn->prepare($query);
        $params = array_merge([$grupo_id], $produtos_ids);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    }
}
?>
