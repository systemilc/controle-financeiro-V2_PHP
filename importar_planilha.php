<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/SpreadsheetProcessor.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_spreadsheet') {
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
        
        // Processar planilha
        $processor = new SpreadsheetProcessor($db, $grupo_id);
        $result = $processor->processSpreadsheet($file_path, $filename);
        
        if ($result['success']) {
            $message = "Planilha processada com sucesso! ";
            $message .= "Foram criadas " . count($result['result']['compras_criadas']) . " compras ";
            $message .= "com " . $result['result']['total_itens'] . " itens de " . $result['result']['total_fornecedores'] . " fornecedores.";
            $message_type = 'success';
            
            // Limpar arquivo temporário
            unlink($file_path);
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

include 'includes/navbar.php';
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
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Importar Planilha de Compra</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
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
                                    <i class="fas fa-upload"></i> Importar Planilha
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
                            
                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Importante:</strong> A planilha será agrupada por fornecedor (CNPJ + Razão Social) 
                                    e cada grupo criará uma compra separada no sistema.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-question-circle"></i> Como Funciona
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>1. Preparação da Planilha</h6>
                                    <ul>
                                        <li>Organize os dados nas colunas corretas</li>
                                        <li>Use formato de data DD/MM/AAAA</li>
                                        <li>Use vírgula como separador decimal</li>
                                        <li><strong>Use TABULAÇÃO entre colunas</strong></li>
                                        <li>Salve como TSV ou TXT para melhor compatibilidade</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>2. Processamento</h6>
                                    <ul>
                                        <li>Dados são agrupados por fornecedor</li>
                                        <li>Fornecedores são criados automaticamente</li>
                                        <li>Produtos são cadastrados no sistema</li>
                                        <li>Transações financeiras são criadas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('spreadsheetFile');
    const importBtn = document.getElementById('importBtn');
    
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
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
