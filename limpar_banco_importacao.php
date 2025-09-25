<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$message = '';
$message_type = '';

// Processar limpeza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'limpar') {
    try {
        $db->beginTransaction();
        
        // 1. Remover transações de compra importadas (que começam com "Compra -")
        $stmt = $db->prepare("DELETE FROM transacoes WHERE grupo_id = ? AND descricao LIKE 'Compra -%'");
        $stmt->execute([$grupo_id]);
        $transacoes_removidas = $stmt->rowCount();
        
        // 2. Remover fornecedores criados pela importação (opcional - comentado para preservar dados)
        // $stmt = $db->prepare("DELETE FROM fornecedores WHERE grupo_id = ? AND nome LIKE '%'");
        // $stmt->execute([$grupo_id]);
        // $fornecedores_removidos = $stmt->rowCount();
        
        // 3. Remover produtos criados pela importação (opcional - comentado para preservar dados)
        // $stmt = $db->prepare("DELETE FROM produtos WHERE grupo_id = ?");
        // $stmt->execute([$grupo_id]);
        // $produtos_removidos = $stmt->rowCount();
        
        // 4. Remover compras da tabela compras (se existirem)
        $stmt = $db->prepare("DELETE FROM compras WHERE grupo_id = ?");
        $stmt->execute([$grupo_id]);
        $compras_removidas = $stmt->rowCount();
        
        // 5. Remover itens de compra
        $stmt = $db->prepare("DELETE FROM itens_compra WHERE compra_id NOT IN (SELECT id FROM compras)");
        $stmt->execute();
        $itens_removidos = $stmt->rowCount();
        
        $db->commit();
        
        $message = "Banco limpo com sucesso!<br>" .
                  "Transações removidas: $transacoes_removidas<br>" .
                  "Compras removidas: $compras_removidas<br>" .
                  "Itens de compra removidos: $itens_removidos";
        $message_type = 'success';
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "Erro ao limpar banco: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Verificar dados atuais
$stmt_transacoes = $db->prepare("SELECT COUNT(*) as total FROM transacoes WHERE grupo_id = ? AND descricao LIKE 'Compra -%'");
$stmt_transacoes->execute([$grupo_id]);
$total_transacoes = $stmt_transacoes->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_compras = $db->prepare("SELECT COUNT(*) as total FROM compras WHERE grupo_id = ?");
$stmt_compras->execute([$grupo_id]);
$total_compras = $stmt_compras->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_fornecedores = $db->prepare("SELECT COUNT(*) as total FROM fornecedores WHERE grupo_id = ?");
$stmt_fornecedores->execute([$grupo_id]);
$total_fornecedores = $stmt_fornecedores->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_produtos = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE grupo_id = ?");
$stmt_produtos->execute([$grupo_id]);
$total_produtos = $stmt_produtos->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar Banco - Controle Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            background-color: #fff5f5;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Limpeza do Banco de Dados
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="stats-card">
                            <h5><i class="fas fa-chart-bar me-2"></i>Dados Atuais no Banco</h5>
                            <div class="row text-center">
                                <div class="col-3">
                                    <h3><?php echo $total_transacoes; ?></h3>
                                    <small>Transações de Compra</small>
                                </div>
                                <div class="col-3">
                                    <h3><?php echo $total_compras; ?></h3>
                                    <small>Compras</small>
                                </div>
                                <div class="col-3">
                                    <h3><?php echo $total_fornecedores; ?></h3>
                                    <small>Fornecedores</small>
                                </div>
                                <div class="col-3">
                                    <h3><?php echo $total_produtos; ?></h3>
                                    <small>Produtos</small>
                                </div>
                            </div>
                        </div>

                        <div class="danger-zone">
                            <h5 class="text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Zona de Perigo
                            </h5>
                            <p class="text-muted">
                                Esta operação irá remover <strong>permanentemente</strong> os seguintes dados:
                            </p>
                            <ul class="text-muted">
                                <li>Todas as transações de compra importadas (que começam com "Compra -")</li>
                                <li>Todas as compras registradas manualmente</li>
                                <li>Todos os itens de compra relacionados</li>
                                <li><strong>NÃO</strong> remove fornecedores e produtos (para preservar dados)</li>
                            </ul>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Atenção:</strong> Esta ação não pode ser desfeita! 
                                Certifique-se de que você realmente deseja limpar os dados antes de continuar.
                            </div>

                            <form method="POST" onsubmit="return confirmarLimpeza()">
                                <input type="hidden" name="action" value="limpar">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class="fas fa-trash me-2"></i>
                                        Limpar Banco de Dados
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="mt-4">
                            <h6>Links Úteis:</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="importar_planilha.php" class="btn btn-primary">
                                    <i class="fas fa-file-excel me-1"></i>Importar Planilha
                                </a>
                                <a href="compras.php" class="btn btn-secondary">
                                    <i class="fas fa-shopping-cart me-1"></i>Página de Compras
                                </a>
                                <a href="transacoes.php" class="btn btn-info">
                                    <i class="fas fa-list me-1"></i>Transações
                                </a>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-home me-1"></i>Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarLimpeza() {
            const totalTransacoes = <?php echo $total_transacoes; ?>;
            const totalCompras = <?php echo $total_compras; ?>;
            
            if (totalTransacoes === 0 && totalCompras === 0) {
                alert('Não há dados para limpar!');
                return false;
            }
            
            const mensagem = `ATENÇÃO: Esta ação irá remover PERMANENTEMENTE:\n\n` +
                           `• ${totalTransacoes} transações de compra\n` +
                           `• ${totalCompras} compras registradas\n` +
                           `• Todos os itens de compra relacionados\n\n` +
                           `Esta ação NÃO PODE SER DESFEITA!\n\n` +
                           `Tem certeza que deseja continuar?`;
            
            return confirm(mensagem);
        }
    </script>
</body>
</html>
