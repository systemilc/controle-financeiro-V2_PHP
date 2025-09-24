<?php
require_once 'config/database.php';

class PDFProcessor {
    private $conn;
    private $table_name = "pdf_processed";
    
    public $id;
    public $filename;
    public $chave_acesso;
    public $cnpj;
    public $razao_social;
    public $data_emissao;
    public $valor_total;
    public $status;
    public $dados_json;
    public $grupo_id;
    public $created_at;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    /**
     * Extrai texto de um arquivo PDF
     */
    public function extractTextFromPDF($file_path) {
        try {
            // Verificar se o arquivo existe
            if (!file_exists($file_path)) {
                throw new Exception("Arquivo PDF não encontrado: " . $file_path);
            }

            // Tentar usar pdftotext se disponível
            if (function_exists('shell_exec') && $this->isCommandAvailable('pdftotext')) {
                $temp_file = tempnam(sys_get_temp_dir(), 'pdf_text_');
                $command = "pdftotext -layout \"$file_path\" \"$temp_file\" 2>&1";
                $output = shell_exec($command);
                
                if (file_exists($temp_file)) {
                    $text = file_get_contents($temp_file);
                    unlink($temp_file);
                    return $text;
                }
            }

            // Fallback: tentar extrair usando bibliotecas PHP
            return $this->extractTextWithPHP($file_path);

        } catch (Exception $e) {
            throw new Exception("Erro ao extrair texto do PDF: " . $e->getMessage());
        }
    }

    /**
     * Extrai texto usando bibliotecas PHP (fallback)
     */
    private function extractTextWithPHP($file_path) {
        // Tentar com Smalot\PdfParser se disponível
        if (class_exists('Smalot\PdfParser\Parser')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
            return $pdf->getText();
        }

        // Tentar com TCPDF se disponível
        if (class_exists('TCPDF')) {
            // Implementação básica com TCPDF
            $pdf = new TCPDF();
            $pdf->setSourceFile($file_path);
            $text = '';
            for ($i = 1; $i <= $pdf->numPages; $i++) {
                $page = $pdf->importPage($i);
                $text .= $page->getText();
            }
            return $text;
        }

        // Se nenhuma biblioteca estiver disponível, retornar erro
        throw new Exception("Nenhuma biblioteca de processamento de PDF disponível. Instale pdftotext, Smalot\\PdfParser ou TCPDF.");
    }

    /**
     * Verifica se um comando está disponível no sistema
     */
    private function isCommandAvailable($command) {
        $return = shell_exec("which $command 2>/dev/null");
        return !empty($return);
    }

    /**
     * Processa um PDF de DANFE e extrai os dados
     */
    public function processDANFEPDF($file_path, $filename) {
        try {
            // Extrair texto do PDF
            $text = $this->extractTextFromPDF($file_path);
            
            if (empty($text)) {
                throw new Exception("Não foi possível extrair texto do PDF");
            }

            // Extrair dados da DANFE
            $data = $this->parseDANFEText($text);
            
            if (!$data) {
                throw new Exception("Não foi possível extrair dados válidos da DANFE");
            }

            // Verificar se já foi processada
            if (!empty($data['chave_acesso']) && $this->isAlreadyProcessed($data['chave_acesso'])) {
                return [
                    'success' => false,
                    'message' => 'Esta DANFE já foi processada anteriormente'
                ];
            }

            return [
                'success' => true,
                'data' => $data,
                'filename' => $filename
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Extrai dados da DANFE a partir do texto extraído
     */
    private function parseDANFEText($text) {
        $data = [
            'chave_acesso' => '',
            'cnpj' => '',
            'razao_social' => '',
            'data_emissao' => '',
            'valor_total' => 0,
            'itens' => []
        ];

        // Extrair chave de acesso (44 dígitos)
        if (preg_match('/(\d{44})/', $text, $matches)) {
            $data['chave_acesso'] = $matches[1];
        }

        // Extrair CNPJ
        if (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $text, $matches)) {
            $data['cnpj'] = $matches[1];
        }

        // Extrair razão social (procurar por texto após "EMITENTE" ou similar)
        $lines = explode("\n", $text);
        $found_emitente = false;
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/EMITENTE|RAZÃO SOCIAL|NOME/i', $line)) {
                $found_emitente = true;
                continue;
            }
            
            if ($found_emitente && strlen($line) > 5 && !preg_match('/^\d/', $line) && 
                !preg_match('/CNPJ|DATA|VALOR|TOTAL|ITEM|CÓDIGO/i', $line)) {
                $data['razao_social'] = $line;
                break;
            }
        }

        // Extrair data de emissão
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $text, $matches)) {
            $data['data_emissao'] = $matches[1];
        }

        // Extrair valor total
        if (preg_match('/VALOR TOTAL[:\s]*R\$\s*([\d,\.]+)/i', $text, $matches)) {
            $data['valor_total'] = $this->parseCurrency($matches[1]);
        }

        // Extrair itens da nota
        $this->extractItensFromText($text, $data);

        return $data;
    }

    /**
     * Extrai itens da nota a partir do texto
     */
    private function extractItensFromText($text, &$data) {
        $lines = explode("\n", $text);
        $in_items_section = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detectar início da seção de itens
            if (preg_match('/ITEM|CÓDIGO|DESCRIÇÃO|QUANT|VALOR/i', $line)) {
                $in_items_section = true;
                continue;
            }
            
            if ($in_items_section) {
                // Tentar extrair item (código, descrição, quantidade, valor)
                if (preg_match('/^(\d+)\s+(.+?)\s+(\d+(?:,\d+)?)\s+R\$\s*([\d,\.]+)/', $line, $matches)) {
                    $item = [
                        'codigo' => $matches[1],
                        'descricao' => trim($matches[2]),
                        'quantidade' => $this->parseNumber($matches[3]),
                        'valor_unitario' => $this->parseCurrency($matches[4]),
                        'valor_total' => $this->parseCurrency($matches[4]) * $this->parseNumber($matches[3])
                    ];
                    
                    if (!empty($item['descricao'])) {
                        $data['itens'][] = $item;
                    }
                }
            }
        }
    }

    /**
     * Converte string de moeda para float
     */
    private function parseCurrency($value) {
        $value = str_replace(['R$', ' '], '', $value);
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }

    /**
     * Converte string de número para float
     */
    private function parseNumber($value) {
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }

    /**
     * Verifica se uma DANFE já foi processada
     */
    public function isAlreadyProcessed($chave_acesso) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE chave_acesso = :chave_acesso";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':chave_acesso', $chave_acesso);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Salva os dados processados da DANFE
     */
    public function saveProcessedData($data, $filename) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (filename, chave_acesso, cnpj, razao_social, data_emissao, valor_total, dados_json, grupo_id, status) 
                  VALUES (:filename, :chave_acesso, :cnpj, :razao_social, :data_emissao, :valor_total, :dados_json, :grupo_id, 'processado')";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':chave_acesso', $data['chave_acesso']);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':razao_social', $data['razao_social']);
        $stmt->bindParam(':data_emissao', $data['data_emissao']);
        $stmt->bindParam(':valor_total', $data['valor_total']);
        $stmt->bindParam(':dados_json', json_encode($data));
        $stmt->bindParam(':grupo_id', $this->grupo_id);

        return $stmt->execute();
    }

    /**
     * Lista DANFEs processadas
     */
    public function getProcessedDANFEs() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE grupo_id = :grupo_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria compra no sistema baseada nos dados da DANFE
     */
    public function createCompraFromDANFE($data) {
        try {
            $this->conn->beginTransaction();

            // 1. Criar/atualizar fornecedor
            $fornecedor_id = $this->createOrUpdateFornecedor($data);
            
            // 2. Criar compra
            $compra_id = $this->createCompra($data, $fornecedor_id);
            
            // 3. Criar itens da compra
            $this->createItensCompra($compra_id, $data['itens']);
            
            // 4. Criar transação financeira
            $this->createTransacaoFromCompra($data, $compra_id);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'compra_id' => $compra_id,
                'fornecedor_id' => $fornecedor_id
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cria ou atualiza fornecedor
     */
    private function createOrUpdateFornecedor($data) {
        require_once 'classes/Fornecedor.php';
        
        $fornecedor = new Fornecedor();
        $fornecedor->grupo_id = $this->grupo_id;
        
        // Verificar se fornecedor já existe
        $existing = $fornecedor->getByCNPJ($data['cnpj']);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Criar novo fornecedor
        $fornecedor->nome = $data['razao_social'];
        $fornecedor->cnpj = $data['cnpj'];
        $fornecedor->telefone = '';
        $fornecedor->email = '';
        $fornecedor->endereco = '';
        
        return $fornecedor->create();
    }

    /**
     * Cria compra
     */
    private function createCompra($data, $fornecedor_id) {
        $query = "INSERT INTO compras (fornecedor_id, data_compra, valor_total, grupo_id, status) 
                  VALUES (:fornecedor_id, :data_compra, :valor_total, :grupo_id, 'confirmada')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fornecedor_id', $fornecedor_id);
        $stmt->bindParam(':data_compra', $data['data_emissao']);
        $stmt->bindParam(':valor_total', $data['valor_total']);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }

    /**
     * Cria itens da compra
     */
    private function createItensCompra($compra_id, $itens) {
        foreach ($itens as $item) {
            // Criar produto se não existir
            $produto_id = $this->createOrUpdateProduto($item);
            
            // Criar item da compra
            $query = "INSERT INTO compra_itens (compra_id, produto_id, quantidade, valor_unitario, valor_total) 
                      VALUES (:compra_id, :produto_id, :quantidade, :valor_unitario, :valor_total)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':compra_id', $compra_id);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':quantidade', $item['quantidade']);
            $stmt->bindParam(':valor_unitario', $item['valor_unitario']);
            $stmt->bindParam(':valor_total', $item['valor_total']);
            $stmt->execute();
        }
    }

    /**
     * Cria ou atualiza produto
     */
    private function createOrUpdateProduto($item) {
        require_once 'classes/Produto.php';
        
        $produto = new Produto();
        $produto->grupo_id = $this->grupo_id;
        
        // Verificar se produto já existe
        $existing = $produto->getByCodigo($item['codigo']);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Criar novo produto
        $produto->codigo = $item['codigo'];
        $produto->nome = $item['descricao'];
        $produto->preco = $item['valor_unitario'];
        $produto->categoria_id = 1; // Categoria padrão
        
        return $produto->create();
    }

    /**
     * Cria transação financeira
     */
    private function createTransacaoFromCompra($data, $compra_id) {
        require_once 'classes/Transacao.php';
        
        $transacao = new Transacao();
        $transacao->grupo_id = $this->grupo_id;
        $transacao->tipo = 'despesa';
        $transacao->categoria_id = 1; // Categoria padrão
        $transacao->conta_id = 1; // Conta padrão
        $transacao->valor = $data['valor_total'];
        $transacao->descricao = "Compra DANFE - " . $data['razao_social'];
        $transacao->data_transacao = $data['data_emissao'];
        $transacao->status = 'confirmada';
        $transacao->referencia_id = $compra_id;
        $transacao->referencia_tipo = 'compra';
        
        return $transacao->create();
    }
}
?>
