<?php
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Produto.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);
$produto = new Produto($db);

// Verificar autenticação
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
$grupo_id = $current_user['grupo_id'];

$produto_id = $_GET['id'] ?? 0;

if($produto_id == 0) {
    echo '<div class="alert alert-danger">ID do produto inválido</div>';
    exit;
}

// Verificar se o produto pertence ao grupo do usuário
$stmt = $produto->read($grupo_id);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$produto_atual = null;

foreach($produtos as $p) {
    if($p['id'] == $produto_id) {
        $produto_atual = $p;
        break;
    }
}

if(!$produto_atual) {
    echo '<div class="alert alert-danger">Produto não encontrado</div>';
    exit;
}

// Obter estatísticas do produto
$produto->id = $produto_id;
$stats = $produto->getStats();

// Obter histórico de compras
$historico = $produto->getHistoricoCompras(20);
$compras = $historico->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-3">
            <i class="fas fa-box me-2"></i>
            <?= htmlspecialchars($produto_atual['nome']) ?>
            <?php if($produto_atual['codigo']): ?>
                <small class="text-muted">(<?= htmlspecialchars($produto_atual['codigo']) ?>)</small>
            <?php endif; ?>
        </h5>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary"><?= $stats['total_compras'] ?? 0 ?></h4>
                <small class="text-muted">Total de Compras</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success"><?= number_format($stats['quantidade_total_comprada'] ?? 0, 0, ',', '.') ?></h4>
                <small class="text-muted">Quantidade Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info">R$ <?= number_format($stats['valor_total_gasto'] ?? 0, 2, ',', '.') ?></h4>
                <small class="text-muted">Valor Total Gasto</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-warning">R$ <?= number_format($stats['preco_medio_historico'] ?? 0, 2, ',', '.') ?></h4>
                <small class="text-muted">Preço Médio</small>
            </div>
        </div>
    </div>
</div>

<!-- Análise de Preços -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-chart-line me-2"></i>Análise de Preços
                </h6>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-success fw-bold">R$ <?= number_format($stats['preco_mais_barato'] ?? 0, 2, ',', '.') ?></div>
                        <small class="text-muted">Preço Mais Barato</small>
                    </div>
                    <div class="col-6">
                        <div class="text-danger fw-bold">R$ <?= number_format($stats['preco_mais_caro'] ?? 0, 2, ',', '.') ?></div>
                        <small class="text-muted">Preço Mais Caro</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-calendar me-2"></i>Período de Compras
                </h6>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="fw-bold"><?= $stats['primeira_compra'] ? date('d/m/Y', strtotime($stats['primeira_compra'])) : 'N/A' ?></div>
                        <small class="text-muted">Primeira Compra</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold"><?= $stats['ultima_compra'] ? date('d/m/Y', strtotime($stats['ultima_compra'])) : 'N/A' ?></div>
                        <small class="text-muted">Última Compra</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Compras -->
<div class="row">
    <div class="col-12">
        <h6 class="mb-3">
            <i class="fas fa-history me-2"></i>Histórico de Compras
        </h6>
        
        <?php if(empty($compras)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Nenhuma compra encontrada para este produto.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Nota Fiscal</th>
                            <th>Quantidade</th>
                            <th>Preço Unitário</th>
                            <th>Preço Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($compra['data_compra'])) ?></td>
                                <td><?= htmlspecialchars($compra['fornecedor_nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($compra['numero_nota'] ?? 'N/A') ?></td>
                                <td><?= number_format($compra['quantidade'], 2, ',', '.') ?></td>
                                <td class="text-success">R$ <?= number_format($compra['preco_unitario'], 2, ',', '.') ?></td>
                                <td class="fw-bold">R$ <?= number_format($compra['preco_total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
