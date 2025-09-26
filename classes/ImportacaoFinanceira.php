<?php
require_once 'config/database.php';
require_once 'config/timezone.php';
require_once 'classes/Transacao.php';
require_once 'classes/Fornecedor.php';
require_once 'classes/Produto.php';
require_once 'classes/SpreadsheetProcessor.php';

class ImportacaoFinanceira {
    private $conn;
    private $grupo_id;
    private $usuario_id;
    private $spreadsheet_processor;

    public function __construct($grupo_id, $usuario_id, $db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
        
        $this->grupo_id = $grupo_id;
        $this->usuario_id = $usuario_id;
        $this->spreadsheet_processor = new SpreadsheetProcessor($this->conn);
    }

    /**
     * Processa planilha e retorna dados para preview
     */
    public function processarPlanilhaParaPreview($file_path, $filename) {
        try {
            // Processar planilha sem salvar no banco
            $dados = $this->spreadsheet_processor->processSpreadsheetDataOnly($file_path, $filename);
            
            if (empty($dados)) {
                throw new Exception("Nenhum dado válido encontrado na planilha.");
            }

            // Agrupar dados por fornecedor/compra
            $compras_agrupadas = $this->agruparDadosPorCompra($dados);
            
            // Verificar produtos existentes e adicionar informações de comparação
            $compras_agrupadas = $this->verificarProdutosExistentes($compras_agrupadas);
            
            return [
                'success' => true,
                'dados' => $compras_agrupadas,
                'total_compras' => count($compras_agrupadas),
                'total_produtos' => count($dados)
            ];
            
        } catch (Exception $e) {
            error_log("ImportacaoFinanceira: Erro no processamento da planilha: " . $e->getMessage());
            error_log("ImportacaoFinanceira: Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug_info' => [
                    'file_path' => $file_path,
                    'filename' => $filename,
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]
            ];
        }
    }

    /**
     * Verifica produtos existentes e adiciona informações de comparação
     */
    private function verificarProdutosExistentes($compras_agrupadas) {
        foreach ($compras_agrupadas as &$compra) {
            foreach ($compra['produtos'] as &$produto) {
                // Buscar produtos similares por nome ou código
                $produtos_similares = $this->buscarProdutosSimilares($produto['nome'], $produto['codigo']);
                
                // Buscar TODOS os produtos existentes para permitir vinculação manual
                $todos_produtos = $this->buscarTodosProdutos();
                
                if (!empty($produtos_similares)) {
                    $produto['produtos_existentes'] = $produtos_similares;
                    $produto['tem_similares'] = true;
                    
                    // Encontrar o melhor preço histórico
                    $melhor_preco = $this->encontrarMelhorPrecoHistorico($produtos_similares);
                    $produto['melhor_preco_historico'] = $melhor_preco;
                } else {
                    $produto['produtos_existentes'] = [];
                    $produto['tem_similares'] = false;
                    $produto['melhor_preco_historico'] = null;
                }
                
                // Sempre adicionar todos os produtos para vinculação manual
                $produto['todos_produtos_disponiveis'] = $todos_produtos;
            }
        }
        
        return $compras_agrupadas;
    }

    /**
     * Busca produtos similares por nome ou código
     */
    private function buscarProdutosSimilares($nome, $codigo) {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.id, p.nome, p.codigo,
                   MIN(ic.preco_unitario) as preco_mais_barato,
                   MAX(ic.preco_unitario) as preco_mais_caro,
                   AVG(ic.preco_unitario) as preco_medio,
                   COUNT(DISTINCT ic.compra_id) as total_compras,
                   COUNT(DISTINCT c.fornecedor_id) as total_fornecedores,
                   GROUP_CONCAT(DISTINCT f.nome SEPARATOR ', ') as fornecedores
            FROM produtos p
            LEFT JOIN itens_compra ic ON p.id = ic.produto_id
            LEFT JOIN compras c ON ic.compra_id = c.id
            LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
            WHERE p.grupo_id = ? 
            AND (LOWER(p.nome) LIKE LOWER(?) OR LOWER(p.codigo) LIKE LOWER(?))
            GROUP BY p.id, p.nome, p.codigo
            ORDER BY p.nome
        ");
        
        $nome_like = '%' . $nome . '%';
        $codigo_like = '%' . $codigo . '%';
        
        $stmt->execute([$this->grupo_id, $nome_like, $codigo_like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Encontra o melhor preço histórico entre os produtos similares
     */
    private function encontrarMelhorPrecoHistorico($produtos_existentes) {
        $melhor_preco = null;
        
        foreach ($produtos_existentes as $produto) {
            if ($produto['preco_mais_barato'] !== null) {
                if ($melhor_preco === null || $produto['preco_mais_barato'] < $melhor_preco['preco']) {
                    $melhor_preco = [
                        'preco' => $produto['preco_mais_barato'],
                        'produto_id' => $produto['id'],
                        'produto_nome' => $produto['nome'],
                        'total_compras' => $produto['total_compras'],
                        'total_fornecedores' => $produto['total_fornecedores']
                    ];
                }
            }
        }
        
        return $melhor_preco;
    }

    /**
     * Busca todos os produtos existentes para vinculação manual
     */
    private function buscarTodosProdutos() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.id, p.nome, p.codigo,
                   MIN(ic.preco_unitario) as preco_mais_barato,
                   MAX(ic.preco_unitario) as preco_mais_caro,
                   AVG(ic.preco_unitario) as preco_medio,
                   COUNT(DISTINCT ic.compra_id) as total_compras,
                   COUNT(DISTINCT c.fornecedor_id) as total_fornecedores,
                   GROUP_CONCAT(DISTINCT f.nome SEPARATOR ', ') as fornecedores
            FROM produtos p
            LEFT JOIN itens_compra ic ON p.id = ic.produto_id
            LEFT JOIN compras c ON ic.compra_id = c.id
            LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
            WHERE p.grupo_id = ?
            GROUP BY p.id, p.nome, p.codigo
            ORDER BY p.nome
        ");
        
        $stmt->execute([$this->grupo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agrupa dados da planilha por compra (fornecedor + data + nota)
     */
    private function agruparDadosPorCompra($dados) {
        $compras = [];
        
        foreach ($dados as $item) {
            $chave = $item['razao_social'] . '|' . $item['data'] . '|' . $item['nota'];
            
            if (!isset($compras[$chave])) {
                $compras[$chave] = [
                    'fornecedor' => [
                        'razao_social' => $item['razao_social'],
                        'cnpj' => $item['cnpj']
                    ],
                    'compra' => [
                        'data' => $item['data'],
                        'nota' => $item['nota'],
                        'valor_total' => 0
                    ],
                    'produtos' => []
                ];
            }
            
            // Adicionar produto
            $compras[$chave]['produtos'][] = [
                'codigo' => $item['codigo_produto'],
                'nome' => $item['produto'],
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
                'valor_total' => $item['valor_total']
            ];
            
            // Somar valor total
            $compras[$chave]['compra']['valor_total'] += $item['valor_total'];
        }
        
        return array_values($compras);
    }

    /**
     * Importa dados para o sistema financeiro
     */
    public function importarDados($dados_importacao, $configuracoes, $vinculacoes_produtos = []) {
        try {
            $this->conn->beginTransaction();
            
            $resultados = [
                'fornecedores_criados' => 0,
                'produtos_criados' => 0,
                'transacoes_criadas' => 0,
                'erros' => [],
                'debug_info' => []
            ];
            
            // Log de debug
            error_log("ImportacaoFinanceira: Iniciando importação com " . count($dados_importacao) . " compras");
            error_log("ImportacaoFinanceira: Configurações: " . json_encode($configuracoes));
            error_log("ImportacaoFinanceira: Vinculações recebidas: " . json_encode($vinculacoes_produtos));
            error_log("ImportacaoFinanceira: Total de vinculações: " . count($vinculacoes_produtos));
            
            foreach ($dados_importacao as $compra) {
                try {
                    // 1. Criar/obter fornecedor
                    $fornecedor_id = $this->criarOuObterFornecedor($compra['fornecedor']);
                    if ($fornecedor_id) {
                        $resultados['fornecedores_criados']++;
                    }
                    
                    // 2. Criar/vincular produtos e coletar IDs
                    error_log("ImportacaoFinanceira: Processando " . count($compra['produtos']) . " produtos para compra " . $compra['compra']['nota']);
                    $produtos_com_ids = [];
                    
                    foreach ($compra['produtos'] as $index => $produto_data) {
                        error_log("ImportacaoFinanceira: Produto $index - Nome: '{$produto_data['nome']}', Código: '{$produto_data['codigo']}', Fornecedor ID: $fornecedor_id");
                        
                        // Verificar se há vinculação definida para este produto
                        $chave_produto = $compra['compra']['nota'] . '_' . $index;
                        $produto_id = null;
                        
                        error_log("ImportacaoFinanceira: Verificando vinculação para chave: '$chave_produto'");
                        error_log("ImportacaoFinanceira: Vinculação existe? " . (isset($vinculacoes_produtos[$chave_produto]) ? 'SIM' : 'NÃO'));
                        if (isset($vinculacoes_produtos[$chave_produto])) {
                            error_log("ImportacaoFinanceira: Valor da vinculação: '{$vinculacoes_produtos[$chave_produto]}'");
                        }
                        
                        if (isset($vinculacoes_produtos[$chave_produto]) && $vinculacoes_produtos[$chave_produto] !== 'novo') {
                            // Usar produto existente vinculado
                            $produto_id = $vinculacoes_produtos[$chave_produto];
                            error_log("ImportacaoFinanceira: Usando produto existente vinculado ID: $produto_id");
                        } else {
                            // Criar novo produto
                            error_log("ImportacaoFinanceira: Criando novo produto (sem vinculação ou vinculação = 'novo')");
                            $produto_id = $this->criarOuObterProduto($produto_data, $fornecedor_id);
                            if ($produto_id) {
                                $resultados['produtos_criados']++;
                                error_log("ImportacaoFinanceira: Produto criado/obtido com ID: $produto_id");
                            } else {
                                error_log("ImportacaoFinanceira: ERRO - Falha ao criar/obter produto: '{$produto_data['nome']}'");
                            }
                        }
                        
                        // Adicionar produto com ID para criação dos itens
                        if ($produto_id) {
                            $produtos_com_ids[] = [
                                'produto_id' => $produto_id,
                                'quantidade' => $produto_data['quantidade'],
                                'valor_unitario' => $produto_data['valor_unitario'],
                                'valor_total' => $produto_data['valor_total']
                            ];
                        }
                    }
                    
                    // 3. Criar registro na tabela compras
                    $compra_id = $this->criarRegistroCompra($compra, $fornecedor_id, $configuracoes);
                    if ($compra_id) {
                        error_log("ImportacaoFinanceira: Compra criada com ID: " . $compra_id);
                        
                        // 4. Criar itens de compra
                        $itens_criados = $this->criarItensCompra($compra_id, $produtos_com_ids);
                        error_log("ImportacaoFinanceira: Itens de compra criados: " . $itens_criados);
                        
                        // 5. Criar transações (principal ou parcelas)
                        error_log("ImportacaoFinanceira: Verificando parcelamento - Ativo: " . ($configuracoes['parcelamento']['ativo'] ? 'SIM' : 'NÃO') . ", Quantidade: " . $configuracoes['parcelamento']['quantidade']);
                        
                        if ($configuracoes['parcelamento']['ativo'] && $configuracoes['parcelamento']['quantidade'] > 1) {
                            // Criar apenas parcelas (sem transação principal)
                            error_log("ImportacaoFinanceira: Criando parcelas - Quantidade: {$configuracoes['parcelamento']['quantidade']}, Tipo: {$configuracoes['parcelamento']['tipo']}");
                            $parcelas_criadas = $this->criarParcelasDiretamente($compra, $configuracoes);
                            error_log("ImportacaoFinanceira: Parcelas criadas: $parcelas_criadas");
                            $resultados['transacoes_criadas'] += $parcelas_criadas;
                        } else {
                            // Criar transação principal (sem parcelamento)
                            error_log("ImportacaoFinanceira: Criando transação para compra " . $compra['compra']['nota']);
                            $transacao_id = $this->criarTransacaoPrincipal($compra, $configuracoes);
                            if ($transacao_id) {
                                $resultados['transacoes_criadas']++;
                                error_log("ImportacaoFinanceira: Transação criada com ID: " . $transacao_id);
                            } else {
                                error_log("ImportacaoFinanceira: ERRO - Falha ao criar transação para compra " . $compra['compra']['nota']);
                            }
                        }
                    } else {
                        error_log("ImportacaoFinanceira: ERRO - Falha ao criar registro de compra " . $compra['compra']['nota']);
                    }
                    
                } catch (Exception $e) {
                    $resultados['erros'][] = "Erro na compra {$compra['compra']['nota']}: " . $e->getMessage();
                }
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'resultados' => $resultados
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cria ou obtém fornecedor
     */
    private function criarOuObterFornecedor($fornecedor_data) {
        // Verificar se fornecedor já existe
        $stmt = $this->conn->prepare("
            SELECT id FROM fornecedores 
            WHERE cnpj = ? AND grupo_id = ?
        ");
        $stmt->execute([$fornecedor_data['cnpj'], $this->grupo_id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fornecedor) {
            return $fornecedor['id'];
        }
        
        // Criar novo fornecedor
        $stmt = $this->conn->prepare("
            INSERT INTO fornecedores (nome, cnpj, grupo_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $fornecedor_data['razao_social'],
            $fornecedor_data['cnpj'],
            $this->grupo_id
        ]);
        
        return $this->conn->lastInsertId();
    }

    /**
     * Cria ou obtém produto
     */
    private function criarOuObterProduto($produto_data, $fornecedor_id) {
        error_log("ImportacaoFinanceira: Verificando produto - Nome: '{$produto_data['nome']}', Código: '{$produto_data['codigo']}', Grupo: {$this->grupo_id}");
        
        // Verificar se produto já existe
        $stmt = $this->conn->prepare("
            SELECT id FROM produtos 
            WHERE codigo = ? AND grupo_id = ?
        ");
        $stmt->execute([$produto_data['codigo'], $this->grupo_id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto) {
            error_log("ImportacaoFinanceira: Produto já existe com ID: {$produto['id']}");
            return $produto['id'];
        }
        
        // Criar novo produto
        error_log("ImportacaoFinanceira: Criando novo produto - Nome: '{$produto_data['nome']}', Código: '{$produto_data['codigo']}', Fornecedor: $fornecedor_id, Grupo: {$this->grupo_id}");
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO produtos (nome, codigo, grupo_id) 
                VALUES (?, ?, ?)
            ");
            $result = $stmt->execute([
                $produto_data['nome'],
                $produto_data['codigo'],
                $this->grupo_id
            ]);
            
            if ($result) {
                $produto_id = $this->conn->lastInsertId();
                error_log("ImportacaoFinanceira: Produto criado com sucesso. ID: $produto_id");
                return $produto_id;
            } else {
                error_log("ImportacaoFinanceira: ERRO - Falha ao executar INSERT do produto");
                return false;
            }
        } catch (Exception $e) {
            error_log("ImportacaoFinanceira: Exceção ao criar produto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria registro na tabela compras
     */
    private function criarRegistroCompra($compra, $fornecedor_id, $configuracoes) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO compras (
                    grupo_id, fornecedor_id, numero_nota, valor_total, 
                    data_compra, conta_id, tipo_pagamento_id, categoria_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $this->grupo_id,
                $fornecedor_id,
                $compra['compra']['nota'],
                $compra['compra']['valor_total'],
                $compra['compra']['data'],
                $configuracoes['conta_id'],
                $configuracoes['tipo_pagamento_id'],
                $configuracoes['categoria_id']
            ]);
            
            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                error_log("ImportacaoFinanceira: ERRO - Falha ao criar registro de compra");
                return false;
            }
        } catch (Exception $e) {
            error_log("ImportacaoFinanceira: Exceção ao criar compra: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria itens de compra
     */
    private function criarItensCompra($compra_id, $produtos_com_ids) {
        $itens_criados = 0;
        
        foreach ($produtos_com_ids as $produto_data) {
            try {
                // Criar item de compra diretamente com o ID do produto
                $stmt = $this->conn->prepare("
                    INSERT INTO itens_compra (
                        compra_id, produto_id, quantidade, 
                        preco_unitario, preco_total
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $compra_id,
                    $produto_data['produto_id'],
                    $produto_data['quantidade'],
                    $produto_data['valor_unitario'],
                    $produto_data['valor_total']
                ]);
                
                if ($result) {
                    $itens_criados++;
                    error_log("ImportacaoFinanceira: Item de compra criado - Produto ID: {$produto_data['produto_id']}, Quantidade: {$produto_data['quantidade']}, Preço: {$produto_data['valor_unitario']}");
                } else {
                    error_log("ImportacaoFinanceira: ERRO - Falha ao criar item de compra para produto ID: {$produto_data['produto_id']}");
                }
            } catch (Exception $e) {
                error_log("ImportacaoFinanceira: Exceção ao criar item de compra: " . $e->getMessage());
            }
        }
        
        return $itens_criados;
    }

    /**
     * Cria transação principal
     */
    private function criarTransacaoPrincipal($compra, $configuracoes) {
        $transacao = new Transacao($this->conn);
        $transacao->usuario_id = $this->usuario_id;
        $transacao->conta_id = $configuracoes['conta_id'];
        $transacao->categoria_id = $configuracoes['categoria_id'];
        $transacao->tipo_pagamento_id = $configuracoes['tipo_pagamento_id'];
        $transacao->descricao = "Compra - " . $compra['fornecedor']['razao_social'] . " (NF: " . $compra['compra']['nota'] . ")";
        $transacao->valor = $compra['compra']['valor_total'];
        $transacao->tipo = 'despesa';
        $transacao->is_confirmed = $configuracoes['confirmar_automaticamente'] ? 1 : 0;
        $transacao->data_transacao = $compra['compra']['data'];
        $transacao->data_confirmacao = $configuracoes['confirmar_automaticamente'] ? $compra['compra']['data'] : null;
        $transacao->observacoes = "Importado de planilha - " . count($compra['produtos']) . " produtos";
        $transacao->is_transfer = 0;
        $transacao->conta_original_nome = null;
        $transacao->multiplicador = 1;
        // grupo_id não é armazenado na tabela transacoes, apenas usuario_id
        
        // Log de debug
        error_log("ImportacaoFinanceira: Dados da transação - Usuario: {$transacao->usuario_id}, Conta: {$transacao->conta_id}, Categoria: {$transacao->categoria_id}, Valor: {$transacao->valor}, Data: {$transacao->data_transacao}, Grupo: {$this->grupo_id}");
        
        try {
            if ($transacao->create()) {
                // Obter o ID da transação criada usando uma consulta
                $stmt = $this->conn->prepare("SELECT id FROM transacoes WHERE usuario_id = ? AND descricao = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$transacao->usuario_id, $transacao->descricao]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $transacao_id = $result['id'];
                    error_log("ImportacaoFinanceira: Transação criada com sucesso. ID: " . $transacao_id);
                    return $transacao_id;
                } else {
                    error_log("ImportacaoFinanceira: ERRO - Não foi possível obter ID da transação criada");
                    return false;
                }
            } else {
                error_log("ImportacaoFinanceira: Falha ao executar create() da transação");
                return false;
            }
        } catch (Exception $e) {
            error_log("ImportacaoFinanceira: Exceção ao criar transação: " . $e->getMessage());
            error_log("ImportacaoFinanceira: Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Cria parcelas diretamente (sem transação principal)
     */
    private function criarParcelasDiretamente($compra, $configuracoes) {
        error_log("ImportacaoFinanceira: Criando parcelas diretamente para compra " . $compra['compra']['nota']);
        
        $parcelas_criadas = 0;
        $quantidade_parcelas = $configuracoes['parcelamento']['quantidade'];
        $tipo_parcelamento = $configuracoes['parcelamento']['tipo'];
        $valor_total = $compra['compra']['valor_total'];
        
        // Calcular valor por parcela
        if ($tipo_parcelamento == 'multiplicar') {
            $valor_parcela = $valor_total; // Mesmo valor em cada parcela
        } else { // dividir
            $valor_parcela = $valor_total / $quantidade_parcelas; // Valor dividido
        }
        
        // Data base para as parcelas
        $data_base = $compra['compra']['data'];
        
        for ($i = 1; $i <= $quantidade_parcelas; $i++) {
            // Calcular data da parcela (1 mês de diferença)
            $data_parcela = date('Y-m-d', strtotime($data_base . " +" . ($i - 1) . " months"));
            
            // Descrição da parcela
            $descricao_parcela = "Compra - " . $compra['fornecedor']['razao_social'] . " (NF: " . $compra['compra']['nota'] . ")";
            if ($tipo_parcelamento == 'dividir' && $quantidade_parcelas > 1) {
                // Divisor: mostra número da parcela
                $descricao_parcela .= " ({$i}/{$quantidade_parcelas})";
            }
            
            // Observações da parcela
            $observacoes_parcela = "Importado de planilha - " . count($compra['produtos']) . " produtos";
            if ($quantidade_parcelas > 1) {
                if ($tipo_parcelamento == 'multiplicar') {
                    $observacoes_parcela .= "\nMultiplicação - Valor: R$ " . number_format($valor_parcela, 2, ',', '.');
                } else {
                    $observacoes_parcela .= "\nParcela {$i} de {$quantidade_parcelas} (Divisão - Valor: R$ " . number_format($valor_parcela, 2, ',', '.') . ")";
                }
            }
            
            // Criar transação da parcela
            $transacao = new Transacao($this->conn);
            $transacao->usuario_id = $this->usuario_id;
            $transacao->conta_id = $configuracoes['conta_id'];
            $transacao->categoria_id = $configuracoes['categoria_id'];
            $transacao->tipo_pagamento_id = $configuracoes['tipo_pagamento_id'];
            $transacao->descricao = $descricao_parcela;
            $transacao->valor = $valor_parcela;
            $transacao->tipo = 'despesa';
            $transacao->is_confirmed = $configuracoes['confirmar_automaticamente'] ? 1 : 0;
            $transacao->data_transacao = $data_parcela;
            $transacao->data_confirmacao = $configuracoes['confirmar_automaticamente'] ? $data_parcela : null;
            $transacao->observacoes = $observacoes_parcela;
            $transacao->is_transfer = 0;
            $transacao->conta_original_nome = null;
            $transacao->multiplicador = $i;
            
            if ($transacao->create()) {
                $parcelas_criadas++;
                error_log("ImportacaoFinanceira: Parcela {$i} criada com sucesso");
            } else {
                error_log("ImportacaoFinanceira: ERRO ao criar parcela {$i}");
            }
        }
        
        return $parcelas_criadas;
    }

    /**
     * Cria parcelas da transação
     */
    private function criarParcelas($transacao_id, $config_parcelamento) {
        error_log("ImportacaoFinanceira: Iniciando criação de parcelas para transação ID: $transacao_id");
        
        // Buscar dados da transação principal
        $stmt = $this->conn->prepare("SELECT * FROM transacoes WHERE id = ?");
        $stmt->execute([$transacao_id]);
        $transacao_principal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transacao_principal) {
            error_log("ImportacaoFinanceira: ERRO - Transação principal não encontrada com ID: $transacao_id");
            return 0;
        }
        
        error_log("ImportacaoFinanceira: Transação principal encontrada - Valor: {$transacao_principal['valor']}, Descrição: {$transacao_principal['descricao']}");
        
        // Criar parcelas
        $transacao = new Transacao($this->conn);
        $transacao->usuario_id = $transacao_principal['usuario_id'];
        $transacao->conta_id = $transacao_principal['conta_id'];
        $transacao->categoria_id = $transacao_principal['categoria_id'];
        $transacao->tipo_pagamento_id = $transacao_principal['tipo_pagamento_id'];
        $transacao->descricao = $transacao_principal['descricao'];
        $transacao->valor = $transacao_principal['valor'];
        $transacao->tipo = $transacao_principal['tipo'];
        $transacao->is_confirmed = $transacao_principal['is_confirmed'];
        $transacao->data_transacao = $transacao_principal['data_transacao'];
        $transacao->observacoes = $transacao_principal['observacoes'];
        $transacao->is_transfer = $transacao_principal['is_transfer'];
        $transacao->conta_original_nome = $transacao_principal['conta_original_nome'];
        $transacao->grupo_id = $this->grupo_id;
        
        return $transacao->createParcelas(
            $config_parcelamento['quantidade'],
            $config_parcelamento['tipo']
        );
    }

    /**
     * Obtém contas disponíveis para o grupo
     */
    public function getContas() {
        $stmt = $this->conn->prepare("
            SELECT id, nome, saldo, icone 
            FROM contas 
            WHERE grupo_id = ? 
            ORDER BY nome ASC
        ");
        $stmt->execute([$this->grupo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém categorias disponíveis para o grupo
     */
    public function getCategorias($tipo = 'despesa') {
        $stmt = $this->conn->prepare("
            SELECT id, nome, cor, icone 
            FROM categorias 
            WHERE grupo_id = ? AND tipo = ?
            ORDER BY nome ASC
        ");
        $stmt->execute([$this->grupo_id, $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém tipos de pagamento disponíveis
     */
    public function getTiposPagamento() {
        $stmt = $this->conn->prepare("
            SELECT id, nome, icone 
            FROM tipos_pagamento 
            WHERE grupo_id = ? 
            ORDER BY nome ASC
        ");
        $stmt->execute([$this->grupo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
