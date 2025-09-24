<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/SimplePDFProcessor.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();

// Instanciar classe SimplePDFProcessor
$pdf_processor = new SimplePDFProcessor();
$pdf_processor->grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';
$pdf_data = null;

// Debug detalhado
error_log("DEBUG: Método da requisição: " . $_SERVER['REQUEST_METHOD']);
error_log("DEBUG: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
error_log("DEBUG: POST data: " . print_r($_POST, true));
error_log("DEBUG: FILES data: " . print_r($_FILES, true));

// Processar requisições
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_pdf':
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/pdf/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
                    
                    if ($file_extension === 'pdf') {
                        $new_filename = 'danfe_' . time() . '_' . uniqid() . '.pdf';
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                            $result = $pdf_processor->processDANFEPDF($upload_path, $new_filename);
                            
                            if ($result['success']) {
                                $pdf_data = $result['data'];
                                $message = 'PDF processado com sucesso! Dados extraídos da DANFE.';
                                $message_type = 'success';
                            } else {
                                $message = $result['message'];
                                $message_type = 'danger';
                                // Remover arquivo se não foi processado
                                unlink($upload_path);
                            }
                        } else {
                            $message = 'Erro ao fazer upload do arquivo!';
                            $message_type = 'danger';
                        }
                    } else {
                        $message = 'Formato de arquivo não permitido! Use apenas arquivos PDF.';
                        $message_type = 'danger';
                    }
                } else {
                    // Debug mais detalhado
                    $debug_info = [];
                    if (!isset($_FILES['pdf_file'])) {
                        $debug_info[] = "FILES['pdf_file'] não está definido";
                    } else {
                        $debug_info[] = "FILES['pdf_file'] definido";
                        $debug_info[] = "Erro: " . $_FILES['pdf_file']['error'];
                        $debug_info[] = "Nome: " . $_FILES['pdf_file']['name'];
                    }
                    
                    $message = 'Nenhum arquivo foi enviado! Debug: ' . implode(', ', $debug_info);
                    $message_type = 'danger';
                }
                break;
                
            case 'create_compra':
                if ($pdf_data) {
                    $result = $pdf_processor->createCompraFromDANFE($pdf_data);
                    
                    if ($result['success']) {
                        // Salvar dados processados
                        $pdf_processor->saveProcessedData($pdf_data, $pdf_data['filename'] ?? 'uploaded.pdf');
                        
                        $message = 'Compra criada com sucesso! ID: ' . $result['compra_id'];
                        $message_type = 'success';
                        $pdf_data = null; // Limpar dados após processamento
                    } else {
                        $message = $result['message'];
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Nenhum dado de PDF disponível para processar!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Buscar DANFEs processadas
$processed_danfes = $pdf_processor->getProcessedDANFEs();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processador de PDF - DANFE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0056b3;
            background-color: #e3f2fd;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .pdf-preview {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .item-row {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        .item-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white min-vh-100 p-0">
                <div class="p-3">
                    <h5 class="text-center mb-4">
                        <i class="fas fa-file-pdf"></i> PDF Processor
                    </h5>
                    <nav class="nav flex-column">
                        <a class="nav-link text-white" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link text-white" href="transacoes.php">
                            <i class="fas fa-exchange-alt"></i> Transações
                        </a>
                        <a class="nav-link text-white" href="produtos.php">
                            <i class="fas fa-box"></i> Produtos
                        </a>
                        <a class="nav-link text-white" href="fornecedores.php">
                            <i class="fas fa-truck"></i> Fornecedores
                        </a>
                        <a class="nav-link text-white active" href="pdf_processor.php">
                            <i class="fas fa-file-pdf"></i> PDF DANFE
                        </a>
                        <a class="nav-link text-white" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-file-pdf text-danger"></i> Processador de PDF - DANFE</h2>
                        <span class="badge bg-primary">Usuário: <?php echo htmlspecialchars($current_user['nome']); ?></span>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Upload Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-upload"></i> Upload de PDF DANFE</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="pdfForm">
                                <input type="hidden" name="action" value="upload_pdf">
                                
                                <div class="upload-area" id="uploadArea">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5>Selecione seu PDF</h5>
                                    <p class="text-muted">Clique no botão abaixo para escolher o arquivo</p>
                                    <input type="file" name="pdf_file" id="pdfFile" accept=".pdf" class="form-control mb-3" required>
                                    <p class="text-muted small">Formatos aceitos: PDF (máximo 10MB)</p>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success" id="processBtn" disabled>
                                        <i class="fas fa-cogs"></i> Processar PDF
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PDF Data Preview -->
                    <?php if ($pdf_data): ?>
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-eye"></i> Dados Extraídos da DANFE</h5>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="create_compra">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-shopping-cart"></i> Criar Compra
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-building"></i> Emitente</h6>
                                        <p><strong>Razão Social:</strong> <?php echo htmlspecialchars($pdf_data['razao_social']); ?></p>
                                        <p><strong>CNPJ:</strong> <?php echo htmlspecialchars($pdf_data['cnpj']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-info-circle"></i> Nota Fiscal</h6>
                                        <p><strong>Chave de Acesso:</strong> <?php echo htmlspecialchars($pdf_data['chave_acesso']); ?></p>
                                        <p><strong>Data de Emissão:</strong> <?php echo htmlspecialchars($pdf_data['data_emissao']); ?></p>
                                        <p><strong>Valor Total:</strong> R$ <?php echo number_format($pdf_data['valor_total'], 2, ',', '.'); ?></p>
                                    </div>
                                </div>

                                <?php if (!empty($pdf_data['itens'])): ?>
                                    <hr>
                                    <h6><i class="fas fa-list"></i> Itens da Nota</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Descrição</th>
                                                    <th>Quantidade</th>
                                                    <th>Valor Unitário</th>
                                                    <th>Valor Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pdf_data['itens'] as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                                        <td><?php echo number_format($item['quantidade'], 2, ',', '.'); ?></td>
                                                        <td>R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                                                        <td>R$ <?php echo number_format($item['valor_total'], 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Processed DANFEs History -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Histórico de DANFEs Processadas</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($processed_danfes)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-file-pdf fa-3x mb-3"></i>
                                    <p>Nenhuma DANFE foi processada ainda.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Arquivo</th>
                                                <th>Emitente</th>
                                                <th>CNPJ</th>
                                                <th>Data</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($processed_danfes as $danfe): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                        <?php echo htmlspecialchars($danfe['filename']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($danfe['razao_social']); ?></td>
                                                    <td><?php echo htmlspecialchars($danfe['cnpj']); ?></td>
                                                    <td><?php echo htmlspecialchars($danfe['data_emissao']); ?></td>
                                                    <td>R$ <?php echo number_format($danfe['valor_total'], 2, ',', '.'); ?></td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo htmlspecialchars($danfe['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewDANFEDetails(<?php echo $danfe['id']; ?>)">
                                                            <i class="fas fa-eye"></i> Ver Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalhes da DANFE -->
    <div class="modal fade" id="danfeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-pdf"></i> Detalhes da DANFE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="danfeDetails">
                    <!-- Conteúdo carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funcionalidade simples de upload
        const fileInput = document.getElementById('pdfFile');
        const processBtn = document.getElementById('processBtn');

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                console.log('Arquivo selecionado:', file.name, file.size, file.type);
                
                // Verificar se é PDF
                if (file.type === 'application/pdf') {
                    processBtn.disabled = false;
                    console.log('PDF válido, botão habilitado');
                } else {
                    alert('Por favor, selecione apenas arquivos PDF.');
                    e.target.value = '';
                    processBtn.disabled = true;
                }
            } else {
                processBtn.disabled = true;
            }
        });

        function viewDANFEDetails(id) {
            fetch(`ajax_pdf_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('danfeDetails').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('danfeModal')).show();
                    } else {
                        alert('Erro ao carregar detalhes: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar detalhes da DANFE');
                });
        }
    </script>
</body>
</html>
