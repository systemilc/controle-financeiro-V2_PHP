<?php
require_once 'config/database.php';

class SpreadsheetProcessor {
    private $conn;
    private $grupo_id;
    
    public function __construct($db = null, $grupo_id = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
        
        $this->grupo_id = $grupo_id;
    }
    
    /**
     * Processa arquivo CSV/Excel com dados de compra
     */
    public function processSpreadsheet($file_path, $filename) {
        try {
            // Verificar se o arquivo existe
            if (!file_exists($file_path)) {
                throw new Exception("Arquivo não encontrado: " . $file_path);
            }
            
        // Determinar tipo de arquivo
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['csv', 'tsv', 'txt'])) {
            $data = $this->processCSV($file_path, $extension);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            $data = $this->processExcel($file_path);
        } else {
            throw new Exception("Formato de arquivo não suportado. Use CSV, TSV, TXT, XLSX ou XLS.");
        }
            
            if (empty($data)) {
                throw new Exception("Nenhum dado válido encontrado na planilha.");
            }
            
            // Processar dados e criar compra
            $result = $this->createCompraFromSpreadsheet($data);
            
            return [
                'success' => true,
                'data' => $data,
                'result' => $result,
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
     * Processa arquivo CSV/Excel apenas para extrair dados (sem salvar no banco)
     */
    public function processSpreadsheetDataOnly($file_path, $filename) {
        try {
            // Verificar se o arquivo existe
            if (!file_exists($file_path)) {
                throw new Exception("Arquivo não encontrado: " . $file_path);
            }
            
            // Determinar tipo de arquivo
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($extension, ['csv', 'tsv', 'txt'])) {
                $data = $this->processCSV($file_path, $extension);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $data = $this->processExcel($file_path);
            } else {
                throw new Exception("Formato de arquivo não suportado. Use CSV, TSV, TXT, XLSX ou XLS.");
            }
                
            if (empty($data)) {
                throw new Exception("Nenhum dado válido encontrado na planilha.");
            }
            
            return $data;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao processar planilha: " . $e->getMessage());
        }
    }
    
    /**
     * Processa arquivo CSV/TSV
     */
    private function processCSV($file_path, $extension = 'csv') {
        $data = [];
        $handle = fopen($file_path, 'r');
        
        if ($handle === false) {
            throw new Exception("Não foi possível abrir o arquivo.");
        }
        
        // Determinar delimitador baseado na extensão
        $delimiter = ($extension === 'tsv' || $extension === 'txt') ? "\t" : ",";
        
        // Ler cabeçalho
        $header = fgetcsv($handle, 1000, $delimiter);
        if (!$header) {
            fclose($handle);
            throw new Exception("Arquivo vazio ou inválido.");
        }
        
        // Mapear colunas
        $column_map = $this->mapColumns($header);
        
        // Ler dados
        $row_number = 1;
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            $row_number++;
            
            if (count($row) < count($header)) {
                continue; // Pular linhas incompletas
            }
            
            $item = $this->parseRow($row, $column_map, $row_number);
            if ($item) {
                $data[] = $item;
            }
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Processa arquivo Excel usando PhpSpreadsheet
     */
    private function processExcel($file_path) {
        try {
            // Verificar se a extensão ZIP está disponível
            if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
                // Fallback: converter para CSV temporariamente
                return $this->processExcelFallback($file_path);
            }
            
            // Incluir a biblioteca PhpSpreadsheet
            require_once 'vendor/autoload.php';
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $data = [];
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            if ($highestRow < 2) {
                throw new Exception("Planilha vazia ou sem dados.");
            }
            
            // Ler cabeçalho (primeira linha)
            $header = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $header[] = $worksheet->getCell($col . '1')->getValue();
            }
            
            // Mapear colunas
            $column_map = $this->mapColumns($header);
            
            // Ler dados (a partir da segunda linha)
            for ($row = 2; $row <= $highestRow; $row++) {
                $row_data = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $row_data[] = $worksheet->getCell($col . $row)->getValue();
                }
                
                $item = $this->parseRow($row_data, $column_map, $row);
                if ($item) {
                    $data[] = $item;
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao processar arquivo Excel: " . $e->getMessage());
        }
    }

    /**
     * Fallback para processar Excel quando ZIP não está disponível
     */
    private function processExcelFallback($file_path) {
        try {
            // Tentar usar uma abordagem alternativa
            // Por enquanto, retornar erro informativo
            throw new Exception("Extensão ZIP não disponível. Por favor, converta o arquivo Excel para CSV e tente novamente.");
            
        } catch (Exception $e) {
            throw new Exception("Erro ao processar arquivo Excel: " . $e->getMessage());
        }
    }
    
    /**
     * Mapeia colunas do cabeçalho
     */
    private function mapColumns($header) {
        $map = [];
        
        foreach ($header as $index => $column) {
            $column = trim(strtoupper($column));
            
            switch ($column) {
                case 'DATA':
                    $map['data'] = $index;
                    break;
                case 'NOTA':
                    $map['nota'] = $index;
                    break;
                case 'RAZÃO':
                case 'RAZAO':
                    $map['razao_social'] = $index;
                    break;
                case 'CNPJ':
                    $map['cnpj'] = $index;
                    break;
                case 'CODIGO PRODUTO':
                case 'CODIGO':
                    $map['codigo_produto'] = $index;
                    break;
                case 'PRODUTO':
                    $map['produto'] = $index;
                    break;
                case 'QUANTIDADE':
                    $map['quantidade'] = $index;
                    break;
                case 'VALOR UNITARIO':
                case 'VALOR UNITÁRIO':
                    $map['valor_unitario'] = $index;
                    break;
                case 'VALOR TOTAL':
                    $map['valor_total'] = $index;
                    break;
            }
        }
        
        return $map;
    }
    
    /**
     * Processa uma linha de dados
     */
    private function parseRow($row, $column_map, $row_number) {
        try {
            $item = [];
            
            // Data
            if (isset($column_map['data'])) {
                $data_str = trim($row[$column_map['data']]);
                $item['data'] = $this->parseDate($data_str);
            }
            
            // Nota
            if (isset($column_map['nota'])) {
                $item['nota'] = trim($row[$column_map['nota']]);
            }
            
            // Razão Social
            if (isset($column_map['razao_social'])) {
                $item['razao_social'] = trim($row[$column_map['razao_social']]);
            }
            
            // CNPJ
            if (isset($column_map['cnpj'])) {
                $item['cnpj'] = $this->cleanCNPJ(trim($row[$column_map['cnpj']]));
            }
            
            // Código do Produto
            if (isset($column_map['codigo_produto'])) {
                $item['codigo_produto'] = trim($row[$column_map['codigo_produto']]);
            }
            
            // Produto
            if (isset($column_map['produto'])) {
                $item['produto'] = trim($row[$column_map['produto']]);
            }
            
            // Quantidade
            if (isset($column_map['quantidade'])) {
                $item['quantidade'] = $this->parseNumber($row[$column_map['quantidade']]);
            }
            
            // Valor Unitário
            if (isset($column_map['valor_unitario'])) {
                $item['valor_unitario'] = $this->parseNumber($row[$column_map['valor_unitario']]);
            }
            
            // Valor Total
            if (isset($column_map['valor_total'])) {
                $item['valor_total'] = $this->parseNumber($row[$column_map['valor_total']]);
            }
            
            // Validar dados obrigatórios
            if (empty($item['produto']) || empty($item['quantidade']) || empty($item['valor_unitario'])) {
                return null; // Pular linha inválida
            }
            
            return $item;
            
        } catch (Exception $e) {
            error_log("Erro ao processar linha $row_number: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Converte data para formato do banco
     */
    private function parseDate($date_str) {
        // Tentar diferentes formatos
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_str);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Se não conseguir parsear, usar data atual
        return date('Y-m-d');
    }
    
    /**
     * Converte número para float
     */
    private function parseNumber($number_str) {
        // Remover espaços
        $number_str = trim($number_str);
        
        // Substituir vírgula por ponto
        $number_str = str_replace(',', '.', $number_str);
        
        // Remover caracteres não numéricos exceto ponto
        $number_str = preg_replace('/[^0-9.]/', '', $number_str);
        
        return floatval($number_str);
    }
    
    /**
     * Limpa CNPJ
     */
    private function cleanCNPJ($cnpj) {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }
    
    /**
     * Cria compra a partir dos dados da planilha
     */
    private function createCompraFromSpreadsheet($data) {
        try {
            $this->conn->beginTransaction();
            
            // Agrupar por fornecedor
            $fornecedores = [];
            foreach ($data as $item) {
                $key = $item['cnpj'] . '_' . $item['razao_social'];
                if (!isset($fornecedores[$key])) {
                    $fornecedores[$key] = [
                        'cnpj' => $item['cnpj'],
                        'razao_social' => $item['razao_social'],
                        'data' => $item['data'],
                        'nota' => $item['nota'],
                        'itens' => []
                    ];
                }
                $fornecedores[$key]['itens'][] = $item;
            }
            
            $compras_criadas = [];
            
            foreach ($fornecedores as $fornecedor_data) {
                // Criar/atualizar fornecedor
                $fornecedor_id = $this->createOrUpdateFornecedor($fornecedor_data);
                
                // Calcular valor total
                $valor_total = 0;
                foreach ($fornecedor_data['itens'] as $item) {
                    $valor_total += $item['valor_total'];
                }
                
                // Criar compra
                $compra_id = $this->createCompra($fornecedor_data, $fornecedor_id, $valor_total);
                
                // Criar itens da compra
                $this->createItensCompra($compra_id, $fornecedor_data['itens']);
                
                // Criar transação financeira
                $this->createTransacaoFromCompra($fornecedor_data, $compra_id, $valor_total);
                
                $compras_criadas[] = [
                    'compra_id' => $compra_id,
                    'fornecedor_id' => $fornecedor_id,
                    'razao_social' => $fornecedor_data['razao_social'],
                    'valor_total' => $valor_total,
                    'itens_count' => count($fornecedor_data['itens'])
                ];
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'compras_criadas' => $compras_criadas,
                'total_fornecedores' => count($fornecedores),
                'total_itens' => count($data)
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Cria ou atualiza fornecedor
     */
    private function createOrUpdateFornecedor($fornecedor_data) {
        require_once 'classes/Fornecedor.php';
        
        $fornecedor = new Fornecedor();
        $fornecedor->grupo_id = $this->grupo_id;
        
        // Verificar se fornecedor já existe
        $existing = $fornecedor->getByCNPJ($fornecedor_data['cnpj']);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Criar novo fornecedor
        $fornecedor->nome = $fornecedor_data['razao_social'];
        $fornecedor->cnpj = $fornecedor_data['cnpj'];
        $fornecedor->telefone = '';
        $fornecedor->email = '';
        $fornecedor->endereco = '';
        
        return $fornecedor->create();
    }
    
    /**
     * Cria compra
     */
    private function createCompra($fornecedor_data, $fornecedor_id, $valor_total) {
        $query = "INSERT INTO compras (fornecedor_id, data_compra, valor_total, grupo_id, numero_nota) 
                  VALUES (:fornecedor_id, :data_compra, :valor_total, :grupo_id, :numero_nota)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fornecedor_id', $fornecedor_id);
        $stmt->bindParam(':data_compra', $fornecedor_data['data']);
        $stmt->bindParam(':valor_total', $valor_total);
        $stmt->bindParam(':grupo_id', $this->grupo_id);
        $stmt->bindParam(':numero_nota', $fornecedor_data['nota']);
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
            $query = "INSERT INTO itens_compra (compra_id, produto_id, quantidade, preco_unitario, preco_total) 
                      VALUES (:compra_id, :produto_id, :quantidade, :preco_unitario, :preco_total)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':compra_id', $compra_id);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':quantidade', $item['quantidade']);
            $stmt->bindParam(':preco_unitario', $item['valor_unitario']);
            $stmt->bindParam(':preco_total', $item['valor_total']);
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
        $existing = $produto->getByCodigo($item['codigo_produto']);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Criar novo produto
        $produto->codigo = $item['codigo_produto'];
        $produto->nome = $item['produto'];
        $produto->preco = $item['valor_unitario'];
        $produto->categoria_id = 1; // Categoria padrão
        
        return $produto->create();
    }
    
    /**
     * Cria transação financeira
     */
    private function createTransacaoFromCompra($fornecedor_data, $compra_id, $valor_total) {
        $query = "INSERT INTO transacoes (usuario_id, conta_id, categoria_id, descricao, valor, tipo, is_confirmed, data_transacao, data_confirmacao) 
                  VALUES (:usuario_id, :conta_id, :categoria_id, :descricao, :valor, :tipo, 1, :data_transacao, :data_confirmacao)";
        
        $stmt = $this->conn->prepare($query);
        $usuario_id = $this->grupo_id; // Usar grupo_id como fallback
        $conta_id = 2; // Conta padrão (ITAU)
        $categoria_id = 8; // Categoria padrão (Alimentação)
        $descricao = "Compra Planilha - " . $fornecedor_data['razao_social'] . " (Nota: " . $fornecedor_data['nota'] . ")";
        $valor = $valor_total;
        $tipo = 'despesa';
        $data_transacao = $fornecedor_data['data'];
        $data_confirmacao = $fornecedor_data['data'];
        
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':conta_id', $conta_id);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':data_transacao', $data_transacao);
        $stmt->bindParam(':data_confirmacao', $data_confirmacao);
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }
}
?>
