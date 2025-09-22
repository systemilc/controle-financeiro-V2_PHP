<?php
require_once 'config/database.php';
require_once 'config/timezone.php';

class Transacao {
    private $conn;
    private $table_name = "transacoes";

    public $id;
    public $usuario_id;
    public $conta_id;
    public $categoria_id;
    public $tipo_pagamento_id;
    public $descricao;
    public $valor;
    public $tipo;
    public $is_confirmed;
    public $data_transacao;
    public $data_vencimento;
    public $data_confirmacao;
    public $observacoes;
    public $is_transfer;
    public $conta_original_nome;
    public $multiplicador;
    public $grupo_id;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    // Criar nova transação
    public function create() {
        // Verificar se o usuario_id existe
        if (empty($this->usuario_id)) {
            throw new Exception("Usuario ID não definido para criar transação");
        }
        
        // Verificar se o usuário existe no banco
        $stmt = $this->conn->prepare("SELECT id, username, email, is_approved, is_active, role FROM usuarios WHERE id = ?");
        $stmt->bindParam(1, $this->usuario_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Debug: verificar se há usuários no banco
            $debug_stmt = $this->conn->query("SELECT COUNT(*) as total FROM usuarios");
            $debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
            throw new Exception("Usuário não encontrado para criar transação. ID: {$this->usuario_id}, Total de usuários no banco: {$debug_result['total']}");
        }
        
        if (!$user['is_approved']) {
            // Aprovar automaticamente o usuário
            $stmt = $this->conn->prepare("UPDATE usuarios SET is_approved = 1 WHERE id = ?");
            $stmt->bindParam(1, $this->usuario_id);
            $stmt->execute();
        }
        
        if (!$user['is_active']) {
            // Ativar automaticamente o usuário
            $stmt = $this->conn->prepare("UPDATE usuarios SET is_active = 1 WHERE id = ?");
            $stmt->bindParam(1, $this->usuario_id);
            $stmt->execute();
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET usuario_id=:usuario_id, conta_id=:conta_id, categoria_id=:categoria_id, 
                      tipo_pagamento_id=:tipo_pagamento_id, descricao=:descricao, valor=:valor, 
                      tipo=:tipo, is_confirmed=:is_confirmed, data_transacao=:data_transacao, 
                      data_vencimento=:data_vencimento, observacoes=:observacoes, 
                      is_transfer=:is_transfer, conta_original_nome=:conta_original_nome, 
                      multiplicador=:multiplicador";

        $stmt = $this->conn->prepare($query);

        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));
        $this->conta_original_nome = htmlspecialchars(strip_tags($this->conta_original_nome));
        
        // Sempre usar data atual (com timezone correto)
        $data_atual = getCurrentDate();

        // Tratar valores nulos para foreign keys
        $categoria_id = !empty($this->categoria_id) ? $this->categoria_id : null;
        $tipo_pagamento_id = !empty($this->tipo_pagamento_id) ? $this->tipo_pagamento_id : null;
        $data_vencimento = !empty($this->data_vencimento) ? $this->data_vencimento : null;
        $observacoes = !empty($this->observacoes) ? $this->observacoes : null;
        $conta_original_nome = !empty($this->conta_original_nome) ? $this->conta_original_nome : null;

        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":conta_id", $this->conta_id);
        $stmt->bindParam(":categoria_id", $categoria_id);
        $stmt->bindParam(":tipo_pagamento_id", $tipo_pagamento_id);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":is_confirmed", $this->is_confirmed);
        $stmt->bindParam(":data_transacao", $data_atual);
        $stmt->bindParam(":data_vencimento", $data_vencimento);
        $stmt->bindParam(":observacoes", $observacoes);
        $stmt->bindParam(":is_transfer", $this->is_transfer);
        $stmt->bindParam(":conta_original_nome", $conta_original_nome);
        $stmt->bindParam(":multiplicador", $this->multiplicador);

        if($stmt->execute()) {
            // Atualizar saldo da conta após criação
            $this->atualizarSaldoContaCriacao();
            return true;
        }
        return false;
    }

    // Ler transações
    public function read($filtro_tipo = null, $filtro_categoria = null, $data_inicio = null, $data_fim = null, $grupo_id = null) {
        $query = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN categorias c ON t.categoria_id = c.id";

        $conditions = [];
        $params = [];

        // Filtrar por grupo se especificado
        if($grupo_id) {
            $conditions[] = "t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";
            $params[':grupo_id'] = $grupo_id;
        }

        if($filtro_tipo) {
            $conditions[] = "t.tipo = :tipo";
            $params[':tipo'] = $filtro_tipo;
        }

        if($filtro_categoria) {
            $conditions[] = "t.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtro_categoria;
        }

        if($data_inicio) {
            $conditions[] = "t.data_transacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }

        if($data_fim) {
            $conditions[] = "t.data_transacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY t.data_transacao DESC, t.created_at DESC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    // Atualizar transação
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET descricao=:descricao, valor=:valor, tipo=:tipo, 
                      categoria_id=:categoria_id, data_transacao=:data_transacao, 
                      observacoes=:observacoes 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        // Tratar valores nulos para foreign keys
        $categoria_id = !empty($this->categoria_id) ? $this->categoria_id : null;
        $observacoes = !empty($this->observacoes) ? $this->observacoes : null;

        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":categoria_id", $categoria_id);
        $stmt->bindParam(":data_transacao", $this->data_transacao);
        $stmt->bindParam(":observacoes", $observacoes);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Deletar transação
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obter todas as transações
    public function getAll($order_by = 'data_transacao', $order_direction = 'DESC') {
        // Validar campos de ordenação
        $allowed_columns = [
            'data_transacao' => 't.data_transacao',
            'descricao' => 't.descricao',
            'valor' => 't.valor',
            'tipo' => 't.tipo',
            'conta_nome' => 'c.nome',
            'categoria_nome' => 'cat.nome',
            'is_confirmed' => 't.is_confirmed',
            'created_at' => 't.created_at'
        ];
        
        $order_column = $allowed_columns[$order_by] ?? 't.data_transacao';
        $order_direction = strtoupper($order_direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT 
                    t.*,
                    c.nome as conta_nome,
                    cat.nome as categoria_nome,
                    cat.cor as categoria_cor,
                    tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter transações pendentes (não confirmadas)
    public function getPendentes($order_by = 'data_transacao', $order_direction = 'DESC') {
        // Validar campos de ordenação
        $allowed_columns = [
            'data_transacao' => 't.data_transacao',
            'descricao' => 't.descricao',
            'valor' => 't.valor',
            'tipo' => 't.tipo',
            'conta_nome' => 'c.nome',
            'categoria_nome' => 'cat.nome',
            'created_at' => 't.created_at'
        ];
        
        $order_column = $allowed_columns[$order_by] ?? 't.data_transacao';
        $order_direction = strtoupper($order_direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT 
                    t.*,
                    c.nome as conta_nome,
                    cat.nome as categoria_nome,
                    cat.cor as categoria_cor,
                    tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 0
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar transações pendentes com paginação
    public function getPendentesWithPagination($order_by = 'data_transacao', $order_direction = 'DESC', $limit = 10, $offset = 0) {
        // Validar campos de ordenação
        $allowed_columns = [
            'data_transacao' => 't.data_transacao',
            'descricao' => 't.descricao',
            'valor' => 't.valor',
            'tipo' => 't.tipo',
            'conta_nome' => 'c.nome',
            'categoria_nome' => 'cat.nome',
            'created_at' => 't.created_at'
        ];
        
        $order_column = $allowed_columns[$order_by] ?? 't.data_transacao';
        $order_direction = strtoupper($order_direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT 
                    t.*,
                    c.nome as conta_nome,
                    cat.nome as categoria_nome,
                    cat.cor as categoria_cor,
                    tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 0
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total de transações pendentes
    public function getPendentesCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " t
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Buscar transações com paginação
    public function getAllWithPagination($order_by = 'data_transacao', $order_direction = 'DESC', $limit = 10, $offset = 0) {
        // Validar campos de ordenação
        $allowed_columns = [
            'data_transacao' => 't.data_transacao',
            'descricao' => 't.descricao',
            'valor' => 't.valor',
            'tipo' => 't.tipo',
            'conta_nome' => 'c.nome',
            'categoria_nome' => 'cat.nome',
            'is_confirmed' => 't.is_confirmed',
            'created_at' => 't.created_at'
        ];
        
        $order_column = $allowed_columns[$order_by] ?? 't.data_transacao';
        $order_direction = strtoupper($order_direction) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT 
                    t.*,
                    c.nome as conta_nome,
                    cat.nome as categoria_nome,
                    cat.cor as categoria_cor,
                    tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar total de transações
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " t
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obter transação por ID
    public function getById($id) {
        $query = "SELECT 
                    t.*,
                    c.nome as conta_nome,
                    cat.nome as categoria_nome,
                    cat.cor as categoria_cor,
                    tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.id = :id AND t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter resumo financeiro
    public function getResumo($data_inicio = null, $data_fim = null, $grupo_id = null) {
        $query = "SELECT 
                    SUM(CASE WHEN tipo = 'receita' AND is_confirmed = 1 THEN valor ELSE 0 END) as total_receitas,
                    SUM(CASE WHEN tipo = 'despesa' AND is_confirmed = 1 THEN valor ELSE 0 END) as total_despesas,
                    COUNT(CASE WHEN tipo = 'receita' AND is_confirmed = 1 THEN 1 END) as qtd_receitas,
                    COUNT(CASE WHEN tipo = 'despesa' AND is_confirmed = 1 THEN 1 END) as qtd_despesas
                  FROM " . $this->table_name . " t";

        $conditions = [];
        $params = [];

        // Filtrar por grupo se especificado
        if($grupo_id) {
            $conditions[] = "t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";
            $params[':grupo_id'] = $grupo_id;
        }

        if($data_inicio) {
            $conditions[] = "t.data_transacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }

        if($data_fim) {
            $conditions[] = "t.data_transacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }

        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular saldo e total de transações
        $result['saldo'] = $result['total_receitas'] - $result['total_despesas'];
        $result['total_transacoes'] = $result['qtd_receitas'] + $result['qtd_despesas'];
        
        return $result;
    }

    // Obter transações por categoria
    public function getByCategory($data_inicio = null, $data_fim = null) {
        $query = "SELECT 
                    c.nome,
                    c.tipo,
                    c.cor,
                    SUM(t.valor) as valor,
                    COUNT(t.id) as quantidade
                  FROM " . $this->table_name . " t
                  LEFT JOIN categorias c ON t.categoria_id = c.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)
                  AND t.is_confirmed = 1";

        $params = [':grupo_id' => $this->grupo_id];

        if($data_inicio) {
            $query .= " AND t.data_transacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }

        if($data_fim) {
            $query .= " AND t.data_transacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }

        $query .= " GROUP BY c.id, c.nome, c.tipo, c.cor
                    ORDER BY valor DESC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular percentual
        $total = array_sum(array_column($result, 'valor'));
        foreach($result as &$item) {
            $item['percentual'] = $total > 0 ? ($item['valor'] / $total) * 100 : 0;
        }

        return $result;
    }

    // Obter transações por conta
    public function getByAccount($data_inicio = null, $data_fim = null) {
        $query = "SELECT 
                    c.nome,
                    SUM(CASE WHEN t.tipo = 'receita' AND t.is_confirmed = 1 THEN t.valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN t.tipo = 'despesa' AND t.is_confirmed = 1 THEN t.valor ELSE 0 END) as despesas
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  WHERE t.usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";

        $params = [':grupo_id' => $this->grupo_id];

        if($data_inicio) {
            $query .= " AND t.data_transacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }

        if($data_fim) {
            $query .= " AND t.data_transacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }

        $query .= " GROUP BY c.id, c.nome
                    ORDER BY receitas DESC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular saldo
        foreach($result as &$item) {
            $item['saldo'] = $item['receitas'] - $item['despesas'];
        }

        return $result;
    }

    // Obter evolução mensal
    public function getMonthlyEvolution($data_inicio = null, $data_fim = null) {
        $query = "SELECT 
                    DATE_FORMAT(data_transacao, '%Y-%m') as mes,
                    DATE_FORMAT(data_transacao, '%m/%Y') as mes_formatado,
                    SUM(CASE WHEN tipo = 'receita' AND is_confirmed = 1 THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = 'despesa' AND is_confirmed = 1 THEN valor ELSE 0 END) as despesas
                  FROM " . $this->table_name . "
                  WHERE usuario_id IN (SELECT id FROM usuarios WHERE grupo_id = :grupo_id)";

        $params = [':grupo_id' => $this->grupo_id];

        if($data_inicio) {
            $query .= " AND data_transacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }

        if($data_fim) {
            $query .= " AND data_transacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }

        $query .= " GROUP BY DATE_FORMAT(data_transacao, '%Y-%m')
                    ORDER BY mes ASC";

        $stmt = $this->conn->prepare($query);

        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Confirmar transação
    public function confirm() {
        $query = "UPDATE " . $this->table_name . "
                  SET is_confirmed = 1, data_confirmacao = CURDATE(), data_transacao = CURDATE()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            // Atualizar saldo da conta após confirmação
            $this->atualizarSaldoConta();
            
            // Criar notificação de pagamento confirmado (apenas se grupo_id estiver definido)
            if(isset($this->grupo_id)) {
                $this->criarNotificacaoConfirmacao();
            }
            return true;
        }
        return false;
    }
    
    // Atualizar saldo da conta após confirmação
    private function atualizarSaldoConta() {
        // Obter dados da transação para atualizar a conta
        $query = "SELECT conta_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $transacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($transacao) {
            // Atualizar saldo da conta
            require_once 'classes/Conta.php';
            $conta = new Conta($this->conn);
            $conta->id = $transacao['conta_id'];
            $conta->updateSaldo();
        }
    }
    
    // Atualizar saldo da conta após criação
    private function atualizarSaldoContaCriacao() {
        if($this->conta_id) {
            // Atualizar saldo da conta
            require_once 'classes/Conta.php';
            $conta = new Conta($this->conn);
            $conta->id = $this->conta_id;
            $conta->updateSaldo();
        }
    }
    
    // Criar notificação de confirmação de pagamento
    private function criarNotificacaoConfirmacao() {
        require_once 'classes/Notificacao.php';
        
        $query = "SELECT t.*, c.nome as conta_nome, cat.nome as categoria_nome 
                  FROM " . $this->table_name . " t
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  WHERE t.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $transacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transacao) {
            $notificacao = new Notificacao($this->conn);
            $notificacao->grupo_id = $this->grupo_id;
            
            $titulo = "Pagamento Confirmado";
            $mensagem = "A transação '" . htmlspecialchars($transacao['descricao']) . "' foi confirmada (R$ " . number_format($transacao['valor'], 2, ',', '.') . ")";
            $dados_extras = [
                'transacao_id' => $this->id,
                'valor' => $transacao['valor'],
                'conta' => $transacao['conta_nome']
            ];
            
            $notificacao->create('pagamento_confirmado', $titulo, $mensagem, null, 'media', $dados_extras);
        }
    }

    // Criar parcelas
    public function createParcelas($quantidade_parcelas, $tipo_parcelamento) {
        $parcelas_criadas = 0;
        
        // Verificar se o usuario_id existe
        if (empty($this->usuario_id)) {
            throw new Exception("Usuario ID não definido para criar parcelas");
        }
        
        // Verificar se o usuário existe no banco
        $stmt = $this->conn->prepare("SELECT id, username, email, is_approved, is_active, role FROM usuarios WHERE id = ?");
        $stmt->bindParam(1, $this->usuario_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Debug: verificar se há usuários no banco
            $debug_stmt = $this->conn->query("SELECT COUNT(*) as total FROM usuarios");
            $debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
            throw new Exception("Usuário não encontrado para criar parcelas. ID: {$this->usuario_id}, Total de usuários no banco: {$debug_result['total']}");
        }
        
        if (!$user['is_approved']) {
            // Aprovar automaticamente o usuário
            $stmt = $this->conn->prepare("UPDATE usuarios SET is_approved = 1 WHERE id = ?");
            $stmt->bindParam(1, $this->usuario_id);
            $stmt->execute();
        }
        
        if (!$user['is_active']) {
            // Ativar automaticamente o usuário
            $stmt = $this->conn->prepare("UPDATE usuarios SET is_active = 1 WHERE id = ?");
            $stmt->bindParam(1, $this->usuario_id);
            $stmt->execute();
        }
        
        // Calcular valor por parcela
        if ($tipo_parcelamento == 'multiplicar') {
            $valor_parcela = $this->valor; // Mesmo valor em cada parcela
        } else { // dividir
            $valor_parcela = $this->valor / $quantidade_parcelas; // Valor dividido
        }
        
        // Data base para as parcelas - sempre usa data atual (com timezone correto)
        $data_base = getCurrentDate(); // Sempre data atual
        
        for ($i = 1; $i <= $quantidade_parcelas; $i++) {
            // Calcular data da parcela (1 mês de diferença)
            $data_parcela = date('Y-m-d', strtotime($data_base . " +" . ($i - 1) . " months"));
            
            // Descrição da parcela
            $descricao_parcela = $this->descricao;
            if ($tipo_parcelamento == 'dividir' && $quantidade_parcelas > 1) {
                // Divisor: mostra número da parcela
                $descricao_parcela .= " ({$i}/{$quantidade_parcelas})";
            }
            // Multiplicador: não mostra número de parcelas
            
            // Observações da parcela
            $observacoes_parcela = $this->observacoes;
            if ($quantidade_parcelas > 1) {
                if ($tipo_parcelamento == 'multiplicar') {
                    $observacoes_parcela .= "\nMultiplicação - Valor: R$ " . number_format($valor_parcela, 2, ',', '.');
                } else {
                    $observacoes_parcela .= "\nParcela {$i} de {$quantidade_parcelas} (Divisão - Valor: R$ " . number_format($valor_parcela, 2, ',', '.') . ")";
                }
            }
            
            // Criar transação da parcela
            $query = "INSERT INTO " . $this->table_name . " 
                      SET usuario_id=:usuario_id, conta_id=:conta_id, categoria_id=:categoria_id, 
                          tipo_pagamento_id=:tipo_pagamento_id, descricao=:descricao, valor=:valor, 
                          tipo=:tipo, is_confirmed=:is_confirmed, data_transacao=:data_transacao, 
                          data_vencimento=:data_vencimento, observacoes=:observacoes, 
                          is_transfer=:is_transfer, conta_original_nome=:conta_original_nome, 
                          multiplicador=:multiplicador";

            $stmt = $this->conn->prepare($query);

            $descricao_parcela = htmlspecialchars(strip_tags($descricao_parcela));
            $observacoes_parcela = htmlspecialchars(strip_tags($observacoes_parcela));
            $conta_original_nome = htmlspecialchars(strip_tags($this->conta_original_nome));

            // Tratar valores nulos para foreign keys
            $categoria_id = !empty($this->categoria_id) ? $this->categoria_id : null;
            $tipo_pagamento_id = !empty($this->tipo_pagamento_id) ? $this->tipo_pagamento_id : null;
            $observacoes_parcela = !empty($observacoes_parcela) ? $observacoes_parcela : null;
            $conta_original_nome = !empty($conta_original_nome) ? $conta_original_nome : null;

            $stmt->bindParam(":usuario_id", $this->usuario_id);
            $stmt->bindParam(":conta_id", $this->conta_id);
            $stmt->bindParam(":categoria_id", $categoria_id);
            $stmt->bindParam(":tipo_pagamento_id", $tipo_pagamento_id);
            $stmt->bindParam(":descricao", $descricao_parcela);
            $stmt->bindParam(":valor", $valor_parcela);
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":is_confirmed", $this->is_confirmed);
            $stmt->bindParam(":data_transacao", $data_parcela);
            $stmt->bindParam(":data_vencimento", $data_parcela);
            $stmt->bindParam(":observacoes", $observacoes_parcela);
            $stmt->bindParam(":is_transfer", $this->is_transfer);
            $stmt->bindParam(":conta_original_nome", $conta_original_nome);
            $stmt->bindParam(":multiplicador", $i);

            if($stmt->execute()) {
                $parcelas_criadas++;
            }
        }
        
        return $parcelas_criadas;
    }

    // ========== MÉTODOS PARA ADMINISTRADORES ==========
    
    // Obter todas as transações (admin)
    public function getAllForAdmin($order_column = 't.created_at', $order_direction = 'DESC') {
        $query = "SELECT t.*, u.username, u.username as usuario_nome, g.nome as grupo_nome,
                         c.nome as conta_nome, cat.nome as categoria_nome, cat.cor as categoria_cor,
                         tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN usuarios u ON t.usuario_id = u.id
                  LEFT JOIN grupos g ON u.grupo_id = g.id
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter transações pendentes (admin)
    public function getPendentesForAdmin($order_column = 't.created_at', $order_direction = 'DESC') {
        $query = "SELECT t.*, u.username, u.username as usuario_nome, g.nome as grupo_nome,
                         c.nome as conta_nome, cat.nome as categoria_nome, cat.cor as categoria_cor,
                         tp.nome as tipo_pagamento_nome
                  FROM " . $this->table_name . " t
                  LEFT JOIN usuarios u ON t.usuario_id = u.id
                  LEFT JOIN grupos g ON u.grupo_id = g.id
                  LEFT JOIN contas c ON t.conta_id = c.id
                  LEFT JOIN categorias cat ON t.categoria_id = cat.id
                  LEFT JOIN tipos_pagamento tp ON t.tipo_pagamento_id = tp.id
                  WHERE t.is_confirmed = 0
                  ORDER BY {$order_column} {$order_direction}, t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obter estatísticas gerais (admin)
    public function getEstatisticasGerais() {
        $query = "SELECT 
                    COUNT(*) as total_transacoes,
                    COUNT(CASE WHEN is_confirmed = 1 THEN 1 END) as transacoes_confirmadas,
                    COUNT(CASE WHEN is_confirmed = 0 THEN 1 END) as transacoes_pendentes,
                    SUM(CASE WHEN tipo = 'receita' AND is_confirmed = 1 THEN valor ELSE 0 END) as receitas_totais,
                    SUM(CASE WHEN tipo = 'despesa' AND is_confirmed = 1 THEN valor ELSE 0 END) as despesas_totais,
                    COUNT(DISTINCT usuario_id) as usuarios_ativos,
                    COUNT(DISTINCT conta_id) as contas_utilizadas
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obter estatísticas por grupo (admin)
    public function getEstatisticasPorGrupo() {
        $query = "SELECT 
                    g.nome as grupo_nome,
                    COUNT(t.id) as total_transacoes,
                    SUM(CASE WHEN t.tipo = 'receita' AND t.is_confirmed = 1 THEN t.valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN t.tipo = 'despesa' AND t.is_confirmed = 1 THEN t.valor ELSE 0 END) as despesas,
                    COUNT(DISTINCT t.usuario_id) as usuarios_ativos
                  FROM " . $this->table_name . " t
                  LEFT JOIN usuarios u ON t.usuario_id = u.id
                  LEFT JOIN grupos g ON u.grupo_id = g.id
                  GROUP BY g.id, g.nome
                  ORDER BY total_transacoes DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
