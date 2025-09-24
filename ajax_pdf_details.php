<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/SimplePDFProcessor.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$current_user = $auth->getCurrentUser();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$pdf_processor = new SimplePDFProcessor();
$pdf_processor->grupo_id = $current_user['grupo_id'];

try {
    // Usar conexão direta do banco
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT * FROM pdf_processed WHERE id = :id AND grupo_id = :grupo_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->bindParam(':grupo_id', $current_user['grupo_id']);
    $stmt->execute();
    
    $danfe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$danfe) {
        echo json_encode(['success' => false, 'message' => 'DANFE não encontrada']);
        exit;
    }
    
    $dados = json_decode($danfe['dados_json'], true);
    
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-building"></i> Emitente</h6>
            <p><strong>Razão Social:</strong> ' . htmlspecialchars($danfe['razao_social']) . '</p>
            <p><strong>CNPJ:</strong> ' . htmlspecialchars($danfe['cnpj']) . '</p>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-info-circle"></i> Nota Fiscal</h6>
            <p><strong>Arquivo:</strong> ' . htmlspecialchars($danfe['filename']) . '</p>
            <p><strong>Chave de Acesso:</strong> ' . htmlspecialchars($danfe['chave_acesso']) . '</p>
            <p><strong>Data de Emissão:</strong> ' . htmlspecialchars($danfe['data_emissao']) . '</p>
            <p><strong>Valor Total:</strong> R$ ' . number_format($danfe['valor_total'], 2, ',', '.') . '</p>
            <p><strong>Status:</strong> <span class="badge bg-success">' . htmlspecialchars($danfe['status']) . '</span></p>
            <p><strong>Processado em:</strong> ' . date('d/m/Y H:i:s', strtotime($danfe['created_at'])) . '</p>
        </div>
    </div>';
    
    if (!empty($dados['itens'])) {
        $html .= '
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
                <tbody>';
        
        foreach ($dados['itens'] as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['codigo']) . '</td>
                        <td>' . htmlspecialchars($item['descricao']) . '</td>
                        <td>' . number_format($item['quantidade'], 2, ',', '.') . '</td>
                        <td>R$ ' . number_format($item['valor_unitario'], 2, ',', '.') . '</td>
                        <td>R$ ' . number_format($item['valor_total'], 2, ',', '.') . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar detalhes: ' . $e->getMessage()]);
}
?>
