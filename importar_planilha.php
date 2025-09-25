<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/ImportacaoFinanceira.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';
$preview_data = null;

// Instanciar classe de importação
$importacao = new ImportacaoFinanceira($grupo_id, $current_user['id'], $db);

// Processar upload e preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_spreadsheet') {
        try {
            if (!isset($_FILES['spreadsheet_file']) || $_FILES['spreadsheet_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Nenhum arquivo foi enviado ou ocorreu um erro no upload.");
            }
            
            $file = $_FILES['spreadsheet_file'];
            $filename = $file['name'];
            $file_tmp = $file['tmp_name'];
            
            // Verificar tipo de arquivo
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'tsv', 'txt', 'xlsx', 'xls'])) {
                throw new Exception("Formato de arquivo não suportado. Use CSV, TSV, TXT, XLSX ou XLS.");
            }
            
            // Verificar tamanho do arquivo (máximo 10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception("Arquivo muito grande. Máximo permitido: 10MB.");
            }
            
            // Criar diretório de uploads se não existir
            $upload_dir = 'uploads/spreadsheets/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $unique_filename = time() . '_' . $filename;
            $file_path = $upload_dir . $unique_filename;
            
            // Mover arquivo para diretório de uploads
            if (!move_uploaded_file($file_tmp, $file_path)) {
                throw new Exception("Erro ao salvar arquivo no servidor.");
            }
            
            // Processar planilha para preview
            $result = $importacao->processarPlanilhaParaPreview($file_path, $filename);
            
            if ($result['success']) {
                $preview_data = $result;
                $message = 'Planilha processada com sucesso! Revise os dados abaixo e configure a importação.';
                $message_type = 'success';
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            $message = "Erro ao processar planilha: " . $e->getMessage();
            $message_type = 'danger';
            
            // Limpar arquivo temporário se existir
            if (isset($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Processar importação final
    if ($_POST['action'] === 'confirmar_importacao') {
        try {
            $dados_importacao = json_decode($_POST['dados_importacao'], true);
            
            // Debug das configurações recebidas
            error_log("Importar Planilha: Dados recebidos do formulário:");
            error_log("parcelamento_ativo: " . (isset($_POST['parcelamento_ativo']) ? 'SIM' : 'NÃO'));
            error_log("quantidade_parcelas: " . (isset($_POST['quantidade_parcelas']) ? $_POST['quantidade_parcelas'] : 'NÃO DEFINIDO'));
            error_log("tipo_parcelamento: " . (isset($_POST['tipo_parcelamento']) ? $_POST['tipo_parcelamento'] : 'NÃO DEFINIDO'));
            
            $configuracoes = [
                'conta_id' => $_POST['conta_id'],
                'categoria_id' => $_POST['categoria_id'],
                'tipo_pagamento_id' => $_POST['tipo_pagamento_id'],
                'confirmar_automaticamente' => isset($_POST['confirmar_automaticamente']),
                'parcelamento' => [
                    'ativo' => isset($_POST['parcelamento_ativo']),
                    'quantidade' => isset($_POST['parcelamento_ativo']) ? (int)$_POST['quantidade_parcelas'] : 1,
                    'tipo' => isset($_POST['parcelamento_ativo']) ? $_POST['tipo_parcelamento'] : 'dividir'
                ]
            ];
            
            // Debug das configurações finais
            error_log("Importar Planilha: Configurações finais:");
            error_log("Parcelamento ativo: " . ($configuracoes['parcelamento']['ativo'] ? 'SIM' : 'NÃO'));
            error_log("Quantidade parcelas: " . $configuracoes['parcelamento']['quantidade']);
            error_log("Tipo parcelamento: " . $configuracoes['parcelamento']['tipo']);
            
            $result = $importacao->importarDados($dados_importacao, $configuracoes);
            
            if ($result['success']) {
                $message = 'Importação realizada com sucesso! ' . 
                          $result['resultados']['transacoes_criadas'] . ' transações criadas, ' .
                          $result['resultados']['fornecedores_criados'] . ' fornecedores criados, ' .
                          $result['resultados']['produtos_criados'] . ' produtos criados.';
                $message_type = 'success';
                $preview_data = null; // Limpar preview
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $message = 'Erro na importação: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Obter dados para formulários
$contas = $importacao->getContas();
$categorias = $importacao->getCategorias('despesa');
$tipos_pagamento = $importacao->getTiposPagamento();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Planilha - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/mobile-menu.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-12 col-md-9 col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Importar Planilha de Compra</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!$preview_data): ?>
            <!-- Formulário de Upload -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-excel"></i> Upload da Planilha
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="spreadsheetForm">
                                <input type="hidden" name="action" value="upload_spreadsheet">
                                
                                <div class="mb-3">
                                    <label for="spreadsheetFile" class="form-label">Selecione a Planilha</label>
                                    <input type="file" name="spreadsheet_file" id="spreadsheetFile" 
                                           class="form-control" accept=".csv,.tsv,.txt,.xlsx,.xls" required>
                                    <div class="form-text">
                                        Formatos aceitos: CSV, TSV, TXT, XLSX, XLS (máximo 10MB)
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success" id="importBtn" disabled>
                                    <i class="fas fa-upload"></i> Processar Planilha
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Formato da Planilha
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Sua planilha deve conter as seguintes colunas:</p>
                            <ul class="list-unstyled">
                                <li><strong>DATA</strong> - Data da compra</li>
                                <li><strong>NOTA</strong> - Número da nota fiscal</li>
                                <li><strong>RAZÃO</strong> - Razão social do fornecedor</li>
                                <li><strong>CNPJ</strong> - CNPJ do fornecedor</li>
                                <li><strong>CODIGO PRODUTO</strong> - Código do produto</li>
                                <li><strong>PRODUTO</strong> - Nome do produto</li>
                                <li><strong>QUANTIDADE</strong> - Quantidade comprada</li>
                                <li><strong>VALOR UNITARIO</strong> - Preço unitário</li>
                                <li><strong>VALOR TOTAL</strong> - Valor total do item</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Dica:</strong> Use <strong>TSV (Tab-Separated Values)</strong> para melhor compatibilidade. 
                                    As colunas devem ser separadas por <strong>tabulação</strong> ao invés de vírgula.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Preview e Configuração -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-eye"></i> Preview dos Dados
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h4><?php echo $preview_data['total_compras']; ?></h4>
                                            <small>Compras</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h4><?php echo $preview_data['total_produtos']; ?></h4>
                                            <small>Produtos</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h4>R$ <?php 
                                                $valor_total = 0;
                                                foreach ($preview_data['dados'] as $compra) {
                                                    $valor_total += $compra['compra']['valor_total'];
                                                }
                                                echo number_format($valor_total, 2, ',', '.');
                                            ?></h4>
                                            <small>Valor Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fornecedor</th>
                                            <th>Data</th>
                                            <th>Nota</th>
                                            <th>Produtos</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($preview_data['dados'] as $compra): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($compra['fornecedor']['razao_social']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($compra['fornecedor']['cnpj']); ?></small>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($compra['compra']['data'])); ?></td>
                                            <td><?php echo htmlspecialchars($compra['compra']['nota']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo count($compra['produtos']); ?> itens</span>
                                            </td>
                                            <td><strong>R$ <?php echo number_format($compra['compra']['valor_total'], 2, ',', '.'); ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog"></i> Configurações da Importação
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="configForm">
                                <input type="hidden" name="action" value="confirmar_importacao">
                                <input type="hidden" name="dados_importacao" id="dadosImportacao" value="<?php echo htmlspecialchars(json_encode($preview_data['dados'])); ?>">
                                
                                <div class="mb-3">
                                    <label for="conta_id" class="form-label">Conta *</label>
                                    <select class="form-select" name="conta_id" id="conta_id" required>
                                        <option value="">Selecione uma conta</option>
                                        <?php foreach ($contas as $conta): ?>
                                        <option value="<?php echo $conta['id']; ?>">
                                            <?php echo htmlspecialchars($conta['nome']); ?> 
                                            (R$ <?php echo number_format($conta['saldo'], 2, ',', '.'); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoria *</label>
                                    <select class="form-select" name="categoria_id" id="categoria_id" required>
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>">
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_pagamento_id" class="form-label">Tipo de Pagamento *</label>
                                    <select class="form-select" name="tipo_pagamento_id" id="tipo_pagamento_id" required>
                                        <option value="">Selecione um tipo</option>
                                        <?php foreach ($tipos_pagamento as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>">
                                            <?php echo htmlspecialchars($tipo['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="confirmar_automaticamente" id="confirmar_automaticamente">
                                        <label class="form-check-label" for="confirmar_automaticamente">
                                            Confirmar transações automaticamente
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="parcelamento_ativo" id="parcelamento_ativo">
                                        <label class="form-check-label" for="parcelamento_ativo">
                                            Parcelar transações
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="parcelamento_config" style="display: none;">
                                    <div class="mb-3">
                                        <label for="quantidade_parcelas" class="form-label">Quantidade de Parcelas *</label>
                                        <input type="number" class="form-control" name="quantidade_parcelas" id="quantidade_parcelas" min="2" max="12" value="2" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_parcelamento" class="form-label">Tipo de Parcelamento *</label>
                                        <select class="form-select" name="tipo_parcelamento" id="tipo_parcelamento" required>
                                            <option value="dividir">Dividir valor (valor igual em cada parcela)</option>
                                            <option value="multiplicar">Multiplicar valor (mesmo valor em cada parcela)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Confirmar Importação
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('spreadsheetFile');
    const importBtn = document.getElementById('importBtn');
    const parcelamentoCheckbox = document.getElementById('parcelamento_ativo');
    const parcelamentoConfig = document.getElementById('parcelamento_config');
    
    // Habilitar/desabilitar botão de upload
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                console.log('Arquivo selecionado:', file.name, file.size, file.type);
                
                // Verificar tipo de arquivo
                const allowedTypes = ['text/csv', 'text/tab-separated-values', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                const allowedExtensions = ['csv', 'tsv', 'txt', 'xlsx', 'xls'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (allowedTypes.includes(file.type) || allowedExtensions.includes(fileExtension)) {
                    importBtn.disabled = false;
                    console.log('Arquivo válido, botão habilitado');
                } else {
                    alert('Por favor, selecione apenas arquivos CSV, TSV, TXT, XLSX ou XLS.');
                    e.target.value = '';
                    importBtn.disabled = true;
                }
            } else {
                importBtn.disabled = true;
            }
        });
        
        // Adicionar loading ao formulário
        document.getElementById('spreadsheetForm').addEventListener('submit', function() {
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            importBtn.disabled = true;
        });
    }
    
    // Mostrar/ocultar configurações de parcelamento
    if (parcelamentoCheckbox) {
        parcelamentoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                parcelamentoConfig.style.display = 'block';
                // Tornar campos obrigatórios
                document.getElementById('quantidade_parcelas').required = true;
                document.getElementById('tipo_parcelamento').required = true;
            } else {
                parcelamentoConfig.style.display = 'none';
                // Remover obrigatoriedade
                document.getElementById('quantidade_parcelas').required = false;
                document.getElementById('tipo_parcelamento').required = false;
            }
        });
    }
    
    // Validação do formulário
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            const parcelamentoAtivo = document.getElementById('parcelamento_ativo').checked;
            
            if (parcelamentoAtivo) {
                const quantidadeParcelas = document.getElementById('quantidade_parcelas').value;
                const tipoParcelamento = document.getElementById('tipo_parcelamento').value;
                
                if (!quantidadeParcelas || quantidadeParcelas < 2) {
                    e.preventDefault();
                    alert('Por favor, informe uma quantidade de parcelas válida (mínimo 2).');
                    return false;
                }
                
                if (!tipoParcelamento) {
                    e.preventDefault();
                    alert('Por favor, selecione um tipo de parcelamento.');
                    return false;
                }
            }
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/mobile-menu.js"></script>
</body>
</html>