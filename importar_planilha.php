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
            
            // Adicionar informações de debug se disponíveis
            if (isset($result['debug_info'])) {
                $message .= "<br><small>Arquivo: " . basename($result['debug_info']['filename']) . "</small>";
                error_log("Importar Planilha: Debug info: " . json_encode($result['debug_info']));
            }
            
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
            $vinculacoes_produtos = isset($_POST['vinculacoes_produtos']) ? json_decode($_POST['vinculacoes_produtos'], true) : [];
            
            // Debug das configurações recebidas
            error_log("Importar Planilha: Dados recebidos do formulário:");
            error_log("parcelamento_ativo: " . (isset($_POST['parcelamento_ativo']) ? 'SIM' : 'NÃO'));
            error_log("quantidade_parcelas: " . (isset($_POST['quantidade_parcelas']) ? $_POST['quantidade_parcelas'] : 'NÃO DEFINIDO'));
            error_log("tipo_parcelamento: " . (isset($_POST['tipo_parcelamento']) ? $_POST['tipo_parcelamento'] : 'NÃO DEFINIDO'));
            error_log("vinculacoes_produtos: " . json_encode($vinculacoes_produtos));
            error_log("vinculacoes_produtos count: " . count($vinculacoes_produtos));
            foreach ($vinculacoes_produtos as $chave => $valor) {
                error_log("Vinculação: $chave = $valor");
            }
            
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
            
            $result = $importacao->importarDados($dados_importacao, $configuracoes, $vinculacoes_produtos);
            
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
    
    <style>
    /* Estilos básicos para o modal - ZERO ANIMAÇÕES */
    #modalProdutos {
        font-family: Arial, sans-serif;
    }
    
    #modalProdutos .card {
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 15px;
        background: white;
    }
    
    #modalProdutos .card-header {
        background-color: #f0f0f0;
        border-bottom: 1px solid #ccc;
        padding: 15px;
        font-weight: bold;
    }
    
    #modalProdutos .card-body {
        padding: 15px;
    }
    
    #modalProdutos .alert {
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 3px;
        border: 1px solid;
    }
    
    #modalProdutos .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeaa7;
        color: #856404;
    }
    
    #modalProdutos .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    
    #modalProdutos .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
    
    #modalProdutos .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
    
    #modalProdutos .form-check {
        margin-bottom: 8px;
    }
    
    #modalProdutos .form-check-input {
        margin-right: 5px;
    }
    
    #modalProdutos .form-check-label {
        cursor: pointer;
    }
    </style>
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
                                
                                <button type="submit" class="btn btn-success" id="importBtn" disabled style="z-index: 1000; position: relative;">
                                    <i class="fas fa-upload"></i> Processar Planilha
                                </button>
                                
                                <button type="button" class="btn btn-info ms-2" onclick="testarBotao()" style="z-index: 1000; position: relative;">
                                    <i class="fas fa-test"></i> Testar Botão
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
                                            <button class="btn btn-sm btn-outline-primary ms-2" 
                                                    onclick="testarBotaoVerProdutos('<?php echo $compra['compra']['nota']; ?>')">
                                                <i class="fas fa-eye"></i> Ver Produtos
                                            </button>
                                            </td>
                                            <td><strong>R$ <?php echo number_format($compra['compra']['valor_total'], 2, ',', '.'); ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Modal completamente customizado - SEM BOOTSTRAP -->
                            <div id="modalProdutos" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000;">
                                <div id="modalContent" style="position: absolute; top: 50%; left: 50%; margin-left: -600px; margin-top: -300px; width: 1200px; height: 600px; background: white; border: 2px solid #333; border-radius: 10px; box-shadow: 0 0 30px rgba(0,0,0,0.8);">
                                    <div style="height: 60px; background: #333; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                                        <h3 style="margin: 0; font-size: 18px;">Produtos da Compra</h3>
                                        <button onclick="fecharModal()" style="background: #ff4444; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; font-weight: bold;">×</button>
                                    </div>
                                    <div id="conteudoProdutos" style="height: 480px; padding: 20px; overflow-y: auto; background: #f9f9f9;">
                                        <!-- Conteúdo será carregado via JavaScript -->
                                    </div>
                                    <div style="height: 60px; background: #333; color: white; padding: 15px 20px; border-radius: 0 0 8px 8px; display: flex; justify-content: space-between; align-items: center;">
                                        <button onclick="fecharModal()" style="background: #666; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;">Fechar</button>
                                        <button onclick="salvarVinculacoes()" style="background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;">
                                            Salvar Vinculações
                                        </button>
                                    </div>
                                </div>
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
    console.log('DOM carregado, iniciando script...');
    
    const fileInput = document.getElementById('spreadsheetFile');
    const importBtn = document.getElementById('importBtn');
    const parcelamentoCheckbox = document.getElementById('parcelamento_ativo');
    const parcelamentoConfig = document.getElementById('parcelamento_config');
    
    console.log('Elementos encontrados:', {
        fileInput: fileInput,
        importBtn: importBtn,
        parcelamentoCheckbox: parcelamentoCheckbox,
        parcelamentoConfig: parcelamentoConfig
    });
    
    // Habilitar/desabilitar botão de upload
    if (fileInput) {
        console.log('fileInput encontrado, adicionando event listener');
        
        fileInput.addEventListener('change', function(e) {
            console.log('Evento change disparado');
            console.log('Arquivos selecionados:', e.target.files.length);
            
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                console.log('Arquivo selecionado:', file.name, file.size, file.type);
                
                // Verificar tipo de arquivo
                const allowedTypes = ['text/csv', 'text/tab-separated-values', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                const allowedExtensions = ['csv', 'tsv', 'txt', 'xlsx', 'xls'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                console.log('Verificação de arquivo:', {
                    fileType: file.type,
                    fileExtension: fileExtension,
                    allowedTypes: allowedTypes,
                    allowedExtensions: allowedExtensions
                });
                
                if (allowedTypes.includes(file.type) || allowedExtensions.includes(fileExtension)) {
                    importBtn.disabled = false;
                    console.log('Arquivo válido, botão habilitado');
                    console.log('Botão disabled:', importBtn.disabled);
                } else {
                    alert('Por favor, selecione apenas arquivos CSV, TSV, TXT, XLSX ou XLS.');
                    e.target.value = '';
                    importBtn.disabled = true;
                    console.log('Arquivo inválido, botão desabilitado');
                }
            } else {
                importBtn.disabled = true;
                console.log('Nenhum arquivo selecionado, botão desabilitado');
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
    const configFormValidation = document.getElementById('configForm');
    if (configFormValidation) {
        configFormValidation.addEventListener('submit', function(e) {
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
    
    // Dados dos produtos para JavaScript (definidos globalmente)
    window.dadosProdutos = <?php echo isset($preview_data['dados']) ? json_encode($preview_data['dados']) : '[]'; ?>;
    const dadosProdutos = window.dadosProdutos;
    const vinculacoesProdutos = {};
    
    
    // Função para coletar vinculações antes do envio
    function coletarVinculacoes() {
        console.log('=== COLETANDO VINCULAÇÕES PARA ENVIO ===');
        
        // Usar vinculações salvas anteriormente no modal
        const vinculacoes = window.vinculacoesProdutos || {};
        
        console.log('Vinculações disponíveis:', vinculacoes);
        console.log('Quantidade de vinculações:', Object.keys(vinculacoes).length);
        
        // Adicionar campo hidden com as vinculações
        const configForm = document.getElementById('configForm');
        if (configForm) {
            console.log('Formulário encontrado:', configForm);
            
            let hiddenInput = document.getElementById('vinculacoes_produtos');
            if (!hiddenInput) {
                console.log('Criando campo hidden...');
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'vinculacoes_produtos';
                hiddenInput.id = 'vinculacoes_produtos';
                configForm.appendChild(hiddenInput);
                console.log('Campo hidden criado');
            } else {
                console.log('Campo hidden já existe');
            }
            
            const jsonVinculacoes = JSON.stringify(vinculacoes);
            hiddenInput.value = jsonVinculacoes;
            
            console.log('Valor do campo hidden:', hiddenInput.value);
            console.log('Campo hidden adicionado ao formulário:', configForm.contains(hiddenInput));
        } else {
            console.log('❌ Formulário não encontrado!');
        }
        
        console.log('=== VINCULAÇÕES COLETADAS PARA ENVIO ===');
        return vinculacoes;
    }
    
    // Interceptar envio do formulário para coletar vinculações
    const configForm = document.getElementById('configForm');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            console.log('Formulário sendo enviado...');
            console.log('Vinculações antes de coletar:', window.vinculacoesProdutos);
            coletarVinculacoes();
            
            // Verificar se as vinculações foram adicionadas ao formulário
            const hiddenInput = document.getElementById('vinculacoes_produtos');
            if (hiddenInput) {
                console.log('Campo hidden encontrado:', hiddenInput.value);
            } else {
                console.log('❌ Campo hidden NÃO encontrado!');
            }
        });
    }
    
    // Verificar se há dados de preview antes de executar funções relacionadas
    if (dadosProdutos && dadosProdutos.length > 0) {
        console.log('Dados de produtos carregados:', dadosProdutos.length, 'compras');
    } else {
        console.log('Nenhum dado de preview disponível');
    }
});

// ===== FUNÇÕES GLOBAIS =====

// Função de teste para debug do botão
function testarBotaoVerProdutos(notaCompra) {
    console.log('=== TESTE BOTÃO VER PRODUTOS ===');
    console.log('Nota recebida:', notaCompra);
    console.log('Função mostrarProdutos existe?', typeof mostrarProdutos);
    console.log('Função desabilitarImportar existe?', typeof desabilitarImportar);
    console.log('window.dadosProdutos:', window.dadosProdutos);
    
    try {
        if (typeof mostrarProdutos === 'function') {
            console.log('Chamando mostrarProdutos...');
            mostrarProdutos(notaCompra);
        } else {
            console.error('❌ Função mostrarProdutos não encontrada!');
            alert('Erro: Função mostrarProdutos não encontrada!');
        }
        
        if (typeof desabilitarImportar === 'function') {
            console.log('Chamando desabilitarImportar...');
            desabilitarImportar();
        } else {
            console.error('❌ Função desabilitarImportar não encontrada!');
        }
    } catch (error) {
        console.error('❌ Erro ao executar funções:', error);
        alert('Erro: ' + error.message);
    }
}

// Função para mostrar produtos de uma compra
function mostrarProdutos(notaCompra) {
    console.log('mostrarProdutos chamada para nota:', notaCompra);
    console.log('dadosProdutos disponíveis:', window.dadosProdutos);
    
    const dadosProdutos = window.dadosProdutos || <?php echo isset($preview_data['dados']) ? json_encode($preview_data['dados']) : '[]'; ?>;
    const compra = dadosProdutos.find(c => c.compra.nota === notaCompra);
    
    console.log('Compra encontrada:', compra);
    
    if (!compra) {
        console.log('❌ Compra não encontrada para nota:', notaCompra);
        return;
    }
    
    let html = '<div class="row">';
    
    compra.produtos.forEach((produto, index) => {
        const chaveProduto = notaCompra + '_' + index;
        
        html += '<div class="col-md-6 mb-4">';
        html += '<div class="card">';
        html += '<div class="card-header">';
        html += '<h6 class="mb-0">' + produto.nome + '</h6>';
        html += '<small class="text-muted">Código: ' + produto.codigo + '</small>';
        html += '</div>';
        html += '<div class="card-body">';
        
        // Informações do produto atual
        html += '<div class="mb-3">';
        html += '<strong>Produto da Planilha:</strong><br>';
        html += 'Quantidade: ' + produto.quantidade + '<br>';
        html += 'Preço Unitário: R$ ' + (parseFloat(produto.valor_unitario) || 0).toFixed(2).replace('.', ',') + '<br>';
        html += 'Valor Total: R$ ' + (parseFloat(produto.valor_total) || 0).toFixed(2).replace('.', ',');
        html += '</div>';
        
                // Sempre mostrar opções de vinculação
                html += '<div class="mb-3">';
                html += '<label class="form-label"><strong>Escolha uma opção:</strong></label>';
                
                // Opção: Criar novo produto
                html += '<div class="form-check mb-2">';
                html += '<input class="form-check-input" type="radio" name="produto_' + chaveProduto + '" value="novo" id="novo_' + chaveProduto + '" checked>';
                html += '<label class="form-check-label" for="novo_' + chaveProduto + '">';
                html += '<strong>Criar novo produto</strong> (recomendado se for realmente diferente)';
                html += '</label>';
                html += '</div>';
                
                // Se tem produtos similares, mostrar alerta
                if (produto.tem_similares && produto.produtos_existentes.length > 0) {
                    html += '<div class="alert alert-warning">';
                    html += '<i class="fas fa-exclamation-triangle"></i> ';
                    html += '<strong>Produtos similares encontrados automaticamente!</strong>';
                    html += '</div>';
                    
                    // Melhor preço histórico
                    if (produto.melhor_preco_historico) {
                        const melhorPreco = produto.melhor_preco_historico;
                        const precoAtual = parseFloat(produto.valor_unitario);
                        
                        // Verificar se melhorPreco.preco é um número válido
                        const precoHistorico = parseFloat(melhorPreco.preco);
                        
                        if (!isNaN(precoHistorico) && !isNaN(precoAtual)) {
                            const diferenca = precoAtual - precoHistorico;
                            const percentual = (diferenca / precoHistorico) * 100;
                            
                            html += '<div class="alert ' + (diferenca > 0 ? 'alert-danger' : 'alert-success') + '">';
                            html += '<strong>Comparação de Preços:</strong><br>';
                            html += 'Melhor preço histórico: R$ ' + precoHistorico.toFixed(2).replace('.', ',') + '<br>';
                            html += 'Preço atual: R$ ' + precoAtual.toFixed(2).replace('.', ',') + '<br>';
                            html += 'Diferença: ' + (diferenca > 0 ? '+' : '') + diferenca.toFixed(2).replace('.', ',') + 
                                   ' (' + (diferenca > 0 ? '+' : '') + percentual.toFixed(1) + '%)';
                            html += '</div>';
                        } else {
                            html += '<div class="alert alert-warning">';
                            html += '<strong>Comparação de Preços:</strong><br>';
                            html += 'Preço atual: R$ ' + precoAtual.toFixed(2).replace('.', ',') + '<br>';
                            html += 'Preço histórico não disponível para comparação';
                            html += '</div>';
                        }
                    }
                    
                    // Opções: Vincular a produtos similares
                    produto.produtos_existentes.forEach(produtoExistente => {
                        html += '<div class="form-check mb-2">';
                        html += '<input class="form-check-input" type="radio" name="produto_' + chaveProduto + '" value="' + produtoExistente.id + '" id="existente_' + chaveProduto + '_' + produtoExistente.id + '">';
                        html += '<label class="form-check-label" for="existente_' + chaveProduto + '_' + produtoExistente.id + '">';
                        html += '<strong>Vincular a produto similar:</strong> ' + produtoExistente.nome + '<br>';
                        html += '<small class="text-muted">';
                        html += 'Código: ' + produtoExistente.codigo + ' | ';
                        html += 'Preço mais barato: R$ ' + (parseFloat(produtoExistente.preco_mais_barato) || 0).toFixed(2).replace('.', ',') + ' | ';
                        html += 'Compras: ' + produtoExistente.total_compras + ' | ';
                        html += 'Fornecedores: ' + produtoExistente.total_fornecedores;
                        html += '</small>';
                        html += '</label>';
                        html += '</div>';
                    });
                }
                
                // Sempre mostrar todos os produtos para vinculação manual
                if (produto.todos_produtos_disponiveis && produto.todos_produtos_disponiveis.length > 0) {
                    html += '<div class="alert alert-info">';
                    html += '<i class="fas fa-search"></i> ';
                    html += '<strong>Ou vincular manualmente a qualquer produto existente:</strong>';
                    html += '</div>';
                    
                    // Criar um select para facilitar a busca
                    html += '<div class="mb-3">';
                    html += '<label class="form-label">Buscar produto para vincular:</label>';
                    html += '<select class="form-select" onchange="selecionarProdutoExistente(\'' + chaveProduto + '\', this.value)">';
                    html += '<option value="">Selecione um produto...</option>';
                    
                    produto.todos_produtos_disponiveis.forEach(produtoExistente => {
                        const precoBarato = produtoExistente.preco_mais_barato ? (parseFloat(produtoExistente.preco_mais_barato) || 0).toFixed(2).replace('.', ',') : 'N/A';
                        html += '<option value="' + produtoExistente.id + '">';
                        html += produtoExistente.nome + ' (Código: ' + produtoExistente.codigo + ' | Preço: R$ ' + precoBarato + ')';
                        html += '</option>';
                    });
                    
                    html += '</select>';
                    html += '</div>';
                    
                    // Opções: Vincular a produtos selecionados manualmente
                    produto.todos_produtos_disponiveis.forEach(produtoExistente => {
                        html += '<div class="form-check mb-2" id="opcao_manual_' + chaveProduto + '_' + produtoExistente.id + '" style="display: none;">';
                        html += '<input class="form-check-input" type="radio" name="produto_' + chaveProduto + '" value="' + produtoExistente.id + '" id="manual_' + chaveProduto + '_' + produtoExistente.id + '">';
                        html += '<label class="form-check-label" for="manual_' + chaveProduto + '_' + produtoExistente.id + '">';
                        html += '<strong>Vincular a:</strong> ' + produtoExistente.nome + '<br>';
                        html += '<small class="text-muted">';
                        html += 'Código: ' + produtoExistente.codigo + ' | ';
                        html += 'Preço mais barato: R$ ' + (parseFloat(produtoExistente.preco_mais_barato) || 0).toFixed(2).replace('.', ',') + ' | ';
                        html += 'Compras: ' + produtoExistente.total_compras + ' | ';
                        html += 'Fornecedores: ' + produtoExistente.total_fornecedores;
                        if (produtoExistente.fornecedores) {
                            html += '<br>Fornecedores: ' + produtoExistente.fornecedores;
                        }
                        html += '</small>';
                        html += '</label>';
                        html += '</div>';
                    });
                }
                
                html += '</div>';
        
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    
    html += '</div>';
    
    // Limpar conteúdo anterior
    const conteudoProdutos = document.getElementById('conteudoProdutos');
    conteudoProdutos.innerHTML = '';
    
    // Adicionar novo conteúdo
    conteudoProdutos.innerHTML = html;
    
    // Mostrar modal - CONTROLE TOTAL
    const modalElement = document.getElementById('modalProdutos');
    
    // Mostrar modal instantaneamente
    modalElement.style.display = 'block';
    
    // Prevenir scroll do body
    document.body.style.overflow = 'hidden';
    
    // Função para fechar modal
    window.fecharModal = function() {
        modalElement.style.display = 'none';
        document.body.style.overflow = 'auto';
    };
    
    // Evento para fechar clicando fora
    modalElement.onclick = function(e) {
        if (e.target === modalElement) {
            fecharModal();
        }
    };
}

// Função para selecionar produto existente via select
function selecionarProdutoExistente(chaveProduto, produtoId) {
    // Esconder todas as opções manuais
    const opcoesManuais = document.querySelectorAll('[id^="opcao_manual_' + chaveProduto + '_"]');
    opcoesManuais.forEach(opcao => {
        opcao.style.display = 'none';
    });
    
    // Mostrar a opção selecionada
    if (produtoId) {
        const opcaoSelecionada = document.getElementById('opcao_manual_' + chaveProduto + '_' + produtoId);
        if (opcaoSelecionada) {
            opcaoSelecionada.style.display = 'block';
            // Selecionar automaticamente o radio button
            const radioButton = document.getElementById('manual_' + chaveProduto + '_' + produtoId);
            if (radioButton) {
                radioButton.checked = true;
            }
        }
    }
}

// Função para desabilitar botão de importar
function desabilitarImportar() {
    const importBtn = document.getElementById('importBtn');
    if (importBtn) {
        importBtn.disabled = true;
        importBtn.style.opacity = '0.5';
        importBtn.title = 'Configure as vinculações de produtos primeiro';
        console.log('Botão de importar desabilitado');
    }
}

// Função para habilitar botão de importar
function habilitarImportar() {
    const importBtn = document.getElementById('importBtn');
    if (importBtn) {
        importBtn.disabled = false;
        importBtn.style.opacity = '1';
        importBtn.title = '';
        console.log('Botão de importar habilitado');
    }
}

// Função para salvar vinculações
function salvarVinculacoes() {
    console.log('=== SALVANDO VINCULAÇÕES ===');
    
    // Coletar todas as vinculações
    const vinculacoes = {};
    const radioButtons = document.querySelectorAll('input[type="radio"]:checked');
    
    console.log('Radio buttons encontrados:', radioButtons.length);
    
    radioButtons.forEach(radio => {
        const name = radio.name;
        const value = radio.value;
        
        console.log('Radio button:', name, '=', value);
        
        if (name.startsWith('produto_')) {
            // Remover o prefixo 'produto_' para criar a chave correta
            const chave = name.replace('produto_', '');
            vinculacoes[chave] = value;
            console.log('✅ Vinculação encontrada:', chave, '=', value);
        }
    });
    
    console.log('Total de vinculações coletadas:', Object.keys(vinculacoes).length);
    console.log('Vinculações:', vinculacoes);
    
    // Armazenar vinculações globalmente
    window.vinculacoesProdutos = vinculacoes;
    
    // Verificar se foi salvo corretamente
    console.log('window.vinculacoesProdutos após salvar:', window.vinculacoesProdutos);
    
    // Mostrar resumo das vinculações
    let resumo = 'Vinculações salvas:\n';
    Object.keys(vinculacoes).forEach(chave => {
        const valor = vinculacoes[chave];
        if (valor === 'novo') {
            resumo += `• ${chave}: Criar novo produto\n`;
        } else {
            resumo += `• ${chave}: Vincular ao produto ID ${valor}\n`;
        }
    });
    
    alert(resumo);
    
    // Fechar modal
    fecharModal();
    
    // Habilitar botão de importar
    habilitarImportar();
    
    console.log('=== VINCULAÇÕES SALVAS COM SUCESSO ===');
}

// Função de teste para debug (definida globalmente)
function testarBotao() {
    console.log('Função testarBotao chamada');
    
    const importBtn = document.getElementById('importBtn');
    const fileInput = document.getElementById('spreadsheetFile');
    
    console.log('Estado atual:', {
        botaoDisabled: importBtn.disabled,
        arquivoSelecionado: fileInput.files.length > 0,
        nomeArquivo: fileInput.files.length > 0 ? fileInput.files[0].name : 'nenhum'
    });
    
    // Forçar habilitação do botão para teste
    importBtn.disabled = false;
    console.log('Botão forçado para habilitado');
    
    alert('Botão testado! Verifique o console para mais detalhes.');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/mobile-menu.js"></script>
</body>
</html>