<?php
require_once 'config/database.php';
require_once 'config/timezone.php';

class Notificacao {
    private $conn;
    private $table_name = "notificacoes";
    public $grupo_id;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar notificação
    public function create($tipo, $titulo, $mensagem, $usuario_id = null, $prioridade = 'media', $dados_extras = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET grupo_id=:grupo_id, usuario_id=:usuario_id, tipo=:tipo, 
                      titulo=:titulo, mensagem=:mensagem, prioridade=:prioridade, 
                      dados_extras=:dados_extras";

        $stmt = $this->conn->prepare($query);

        $titulo = strip_tags($titulo);
        $mensagem = strip_tags($mensagem);
        $dados_extras_json = $dados_extras ? json_encode($dados_extras) : null;

        $stmt->bindParam(":grupo_id", $this->grupo_id);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":mensagem", $mensagem);
        $stmt->bindParam(":prioridade", $prioridade);
        $stmt->bindParam(":dados_extras", $dados_extras_json);

        return $stmt->execute();
    }

    // Obter notificações não lidas
    public function getNaoLidas($usuario_id = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id 
                  AND is_lida = 0";
        
        if ($usuario_id) {
            $query .= " AND (usuario_id = :usuario_id OR usuario_id IS NULL)";
        } else {
            $query .= " AND usuario_id IS NULL";
        }
        
        $query .= " ORDER BY prioridade DESC, data_notificacao DESC LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        
        if ($usuario_id) {
            $stmt->bindParam(':usuario_id', $usuario_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter todas as notificações
    public function getAll($usuario_id = null, $limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id";
        
        if ($usuario_id) {
            $query .= " AND (usuario_id = :usuario_id OR usuario_id IS NULL)";
        } else {
            $query .= " AND usuario_id IS NULL";
        }
        
        $query .= " ORDER BY data_notificacao DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        if ($usuario_id) {
            $stmt->bindParam(':usuario_id', $usuario_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar notificações com filtros e paginação
    public function getAllWithFilters($filtro_tipo = '', $filtro_prioridade = '', $filtro_status = '', $limit = 10, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id";
        
        $params = [':grupo_id' => $this->grupo_id];
        
        if ($filtro_tipo) {
            $query .= " AND tipo = :tipo";
            $params[':tipo'] = $filtro_tipo;
        }
        
        if ($filtro_prioridade) {
            $query .= " AND prioridade = :prioridade";
            $params[':prioridade'] = $filtro_prioridade;
        }
        
        if ($filtro_status === 'lidas') {
            $query .= " AND is_lida = 1";
        } elseif ($filtro_status === 'nao_lidas') {
            $query .= " AND is_lida = 0";
        }
        
        $query .= " ORDER BY data_notificacao DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total de notificações com filtros
    public function getTotalCount($filtro_tipo = '', $filtro_prioridade = '', $filtro_status = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id";
        
        $params = [':grupo_id' => $this->grupo_id];
        
        if ($filtro_tipo) {
            $query .= " AND tipo = :tipo";
            $params[':tipo'] = $filtro_tipo;
        }
        
        if ($filtro_prioridade) {
            $query .= " AND prioridade = :prioridade";
            $params[':prioridade'] = $filtro_prioridade;
        }
        
        if ($filtro_status === 'lidas') {
            $query .= " AND is_lida = 1";
        } elseif ($filtro_status === 'nao_lidas') {
            $query .= " AND is_lida = 0";
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Marcar como lida
    public function marcarComoLida($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_lida = 1, data_leitura = :data_leitura 
                  WHERE id = :id AND grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $data_leitura = getCurrentDateTime();
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':data_leitura', $data_leitura);

        return $stmt->execute();
    }

    // Marcar todas como lidas
    public function marcarTodasComoLidas($usuario_id = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_lida = 1, data_leitura = :data_leitura 
                  WHERE grupo_id = :grupo_id AND is_lida = 0";
        
        if ($usuario_id) {
            $query .= " AND (usuario_id = :usuario_id OR usuario_id IS NULL)";
        } else {
            $query .= " AND usuario_id IS NULL";
        }

        $stmt = $this->conn->prepare($query);
        $data_leitura = getCurrentDateTime();
        
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':data_leitura', $data_leitura);
        
        if ($usuario_id) {
            $stmt->bindParam(':usuario_id', $usuario_id);
        }

        return $stmt->execute();
    }

    // Contar notificações não lidas
    public function contarNaoLidas($usuario_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE grupo_id = :grupo_id AND is_lida = 0";
        
        if ($usuario_id) {
            $query .= " AND (usuario_id = :usuario_id OR usuario_id IS NULL)";
        } else {
            $query .= " AND usuario_id IS NULL";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        
        if ($usuario_id) {
            $stmt->bindParam(':usuario_id', $usuario_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Verificar vencimentos próximos (7 dias)
    public function verificarVencimentosProximos() {
        $query = "SELECT t.*, c.nome as conta_nome, cat.nome as categoria_nome 
                  FROM transacoes t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 0
                  AND t.data_vencimento IS NOT NULL
                  AND t.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  ORDER BY t.data_vencimento ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar vencimentos atrasados
    public function verificarVencimentosAtrasados() {
        $query = "SELECT t.*, c.nome as conta_nome, cat.nome as categoria_nome 
                  FROM transacoes t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 0
                  AND t.data_vencimento IS NOT NULL
                  AND t.data_vencimento < CURDATE()
                  ORDER BY t.data_vencimento ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar saldos baixos
    public function verificarSaldosBaixos($limite = 100) {
        $query = "SELECT c.* FROM contas c
                  WHERE c.grupo_id = :grupo_id
                  AND c.saldo < :limite
                  AND c.saldo >= 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':limite', $limite);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Gerar notificações automáticas
    public function gerarNotificacoesAutomaticas() {
        $notificacoes_criadas = 0;

        // Verificar vencimentos próximos
        $vencimentos_proximos = $this->verificarVencimentosProximos();
        foreach ($vencimentos_proximos as $vencimento) {
            $dias_restantes = (strtotime($vencimento['data_vencimento']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            $dias_restantes = ceil($dias_restantes);
            
            $titulo = "Vencimento Próximo";
            $mensagem = "A transação '" . htmlspecialchars($vencimento['descricao']) . "' vence em {$dias_restantes} dia(s) (R$ " . number_format($vencimento['valor'], 2, ',', '.') . ")";
            $dados_extras = [
                'transacao_id' => $vencimento['id'],
                'dias_restantes' => $dias_restantes,
                'valor' => $vencimento['valor']
            ];
            
            if ($this->create('vencimento_proximo', $titulo, $mensagem, null, 'media', $dados_extras)) {
                $notificacoes_criadas++;
            }
        }

        // Verificar vencimentos atrasados
        $vencimentos_atrasados = $this->verificarVencimentosAtrasados();
        foreach ($vencimentos_atrasados as $vencimento) {
            $dias_atraso = (strtotime(date('Y-m-d')) - strtotime($vencimento['data_vencimento'])) / (60 * 60 * 24);
            $dias_atraso = ceil($dias_atraso);
            
            $titulo = "Vencimento Atrasado";
            $mensagem = "A transação '" . htmlspecialchars($vencimento['descricao']) . "' está atrasada há {$dias_atraso} dia(s) (R$ " . number_format($vencimento['valor'], 2, ',', '.') . ")";
            $dados_extras = [
                'transacao_id' => $vencimento['id'],
                'dias_atraso' => $dias_atraso,
                'valor' => $vencimento['valor']
            ];
            
            if ($this->create('vencimento_atrasado', $titulo, $mensagem, null, 'alta', $dados_extras)) {
                $notificacoes_criadas++;
            }
        }

        // Verificar saldos baixos
        $saldos_baixos = $this->verificarSaldosBaixos();
        foreach ($saldos_baixos as $conta) {
            $titulo = "Saldo Baixo";
            $mensagem = "A conta '" . htmlspecialchars($conta['nome']) . "' está com saldo baixo: R$ " . number_format($conta['saldo'], 2, ',', '.');
            $dados_extras = [
                'conta_id' => $conta['id'],
                'saldo' => $conta['saldo']
            ];
            
            if ($this->create('saldo_baixo', $titulo, $mensagem, null, 'media', $dados_extras)) {
                $notificacoes_criadas++;
            }
        }

        return $notificacoes_criadas;
    }

    // Excluir notificação
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND grupo_id = :grupo_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':grupo_id', $this->grupo_id);

        return $stmt->execute();
    }
}
?>
